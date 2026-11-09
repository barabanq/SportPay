<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Lot;
use App\Models\Message;
use App\Models\Order;
use App\Models\User;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class TelegramBotService
{
    public const CHAT_COOLDOWN_SECONDS = 180; // 3 минуты

    public function ensureLinkToken(User $user): string
    {
        if (empty($user->telegram_link_token)) {
            $user->forceFill([
                'telegram_link_token' => Str::random(24),
            ])->save();
        }

        return (string) $user->telegram_link_token;
    }

    public function generateLinkToken(User $user): string
    {
        return $this->ensureLinkToken($user);
    }

    public function resolveBotUsername(): string
    {
        $configured = ltrim((string) config('services.telegram.bot_username', ''), '@');
        $botToken = (string) config('services.telegram.bot_token', '');

        if ($botToken !== '' && ($configured === '' || $configured === 'SportPayBot')) {
            return Cache::remember('telegram_bot_username_' . md5($botToken), 3600, function () use ($botToken, $configured) {
                try {
                    $res = Http::timeout(4)->get("https://api.telegram.org/bot{$botToken}/getMe");
                    if ($res->successful() && !empty($res->json('result.username'))) {
                        return (string) $res->json('result.username');
                    }
                } catch (\Throwable $e) {
                    // ignore
                }
                return $configured ?: 'SportPayBot';
            });
        }

        return $configured ?: 'SportPayBot';
    }

    public function getDeepLink(User $user): string
    {
        $token = $this->ensureLinkToken($user);
        $botUsername = $this->resolveBotUsername();

        return "https://t.me/{$botUsername}?start={$token}";
    }

    public function handleUpdate(array $payload): bool
    {
        $message = $payload['message'] ?? null;
        if (!is_array($message)) {
            return false;
        }

        $text = trim((string) ($message['text'] ?? ''));
        $chatId = (string) ($message['chat']['id'] ?? '');
        $username = $message['from']['username'] ?? $message['chat']['username'] ?? null;

        if ($chatId === '') {
            return false;
        }

        $token = '';
        if (str_starts_with($text, '/start')) {
            $parts = preg_split('/\s+/', $text, 2);
            $token = trim($parts[1] ?? '');
        } elseif (preg_match('/^[a-zA-Z0-9]{16,64}$/', $text)) {
            $token = $text;
        } else {
            return false;
        }

        if ($token === '') {
            $this->sendMessage(
                $chatId,
                "⚡ <b>SportPay Bot</b>\n\nЧтобы привязать аккаунт, скопируйте команду <code>/start ТОКЕН</code> на сайте SportPay и отправьте её сюда."
            );
            return false;
        }

        $user = User::where('telegram_link_token', $token)->first();

        if (!$user) {
            $this->sendMessage($chatId, "❌ Токен привязки не найден или уже использован. Обновите страницу на сайте SportPay.");
            return false;
        }

        User::where('telegram_chat_id', $chatId)
            ->where('id', '!=', $user->id)
            ->update([
                'telegram_chat_id' => null,
                'telegram_username' => null,
            ]);

        $user->forceFill([
            'telegram_chat_id' => $chatId,
            'telegram_username' => $username ? (string) $username : null,
            'telegram_link_token' => null,
            'tg_notify_messages' => true,
        ])->save();

        $this->sendMessage(
            $chatId,
            "✅ <b>Аккаунт {$user->username} успешно привязан к SportPay!</b>\n\nТеперь сюда будут приходить уведомления о покупках, сообщениях и новых лотах из выбранных вами разделов."
        );

        return true;
    }

    /**
     * Мгновенное уведомление продавцу о новой покупке лота (без кулдауна)
     */
    public function notifyOrderCreated(Order $order): bool
    {
        $order->loadMissing(['seller', 'buyer', 'lot']);
        $seller = $order->seller;

        if (!$seller || !$seller->telegram_chat_id || !$seller->tg_notify_messages) {
            return false;
        }

        $lotTitle = e($order->lot?->title ?? 'Лот #' . $order->lot_id);
        $buyerName = e($order->buyer?->username ?? 'Покупатель');
        $priceText = ((float) $order->total_amount <= 0)
            ? 'Бесплатно (0 ₽)'
            : number_format((float) $order->total_amount, 0, '.', ' ') . ' ₽';
        $orderUrl = route('orders.show', $order);

        $text = "💰 <b>Новая покупка на SportPay!</b>\n\n"
            . "📦 Лот: <b>{$lotTitle}</b>\n"
            . "👤 Покупатель: <b>{$buyerName}</b>\n"
            . "💳 Сумма в холде: <b>{$priceText}</b>\n\n"
            . "👉 <a href=\"{$orderUrl}\">Открыть сделку #{$order->id}</a>";

        return $this->notifyUser($seller, $text);
    }

    /**
     * Уведомление о личном сообщении с антиспам-кулдауном в кэше (tg_chat_notify:{recipient_id}:{sender_id})
     */
    public function notifyDirectMessage(User $recipient, User $sender, string $body, ?int $orderId = null): bool
    {
        if (
            $recipient->id === $sender->id ||
            empty($recipient->telegram_chat_id) ||
            !$recipient->tg_notify_messages
        ) {
            return false;
        }

        $cacheKey = $this->chatCooldownKey($recipient->id, $sender->id);

        if (Cache::has($cacheKey)) {
            return false;
        }

        Cache::put($cacheKey, true, now()->addSeconds(self::CHAT_COOLDOWN_SECONDS));

        $senderName = e($sender->username);
        $preview = e(Str::limit(trim($body), 160));
        $link = $orderId
            ? route('orders.show', $orderId)
            : route('chat.index', ['user' => $sender->id]);

        $contextLabel = $orderId ? "по заказу #{$orderId}" : 'в личном чате';

        $text = "💬 <b>Новое сообщение {$contextLabel}</b>\n"
            . "От: <b>{$senderName}</b>\n\n"
            . "«{$preview}»\n\n"
            . "👉 <a href=\"{$link}\">Ответить в чате SportPay</a>";

        return $this->notifyUser($recipient, $text);
    }

    /**
     * Сброс кулдауна при открытии чата получателем
     */
    public function clearChatCooldown(int $recipientId, int $senderId): void
    {
        Cache::forget($this->chatCooldownKey($recipientId, $senderId));
    }

    public function chatCooldownKey(int $recipientId, int $senderId): string
    {
        return "tg_chat_notify:{$recipientId}:{$senderId}";
    }

    /**
     * Рассылка уведомлений подписчикам раздела и гео-радара при публикации нового лота
     */
    public function notifySubscribersAboutNewLot(Lot $lot): int
    {
        $lot->loadMissing('seller');

        $subscribers = User::query()
            ->whereNotNull('telegram_chat_id')
            ->where('id', '!=', $lot->seller_id)
            ->whereNotNull('tg_subscribed_categories')
            ->get();

        $sentCount = 0;
        $categoryLabels = self::categoryLabels();
        $categoryName = $categoryLabels[$lot->product_type] ?? $lot->product_type;
        $priceText = ((float) $lot->price <= 0 || $lot->product_type === 'free_listing')
            ? 'Бесплатно'
            : number_format((float) $lot->price, 0, '.', ' ') . ' ₽';
        $lotUrl = route('lots.show', $lot);

        foreach ($subscribers as $subscriber) {
            $categories = is_array($subscriber->tg_subscribed_categories)
                ? $subscriber->tg_subscribed_categories
                : [];

            if (!in_array($lot->product_type, $categories, true)) {
                continue;
            }

            $distanceInfo = '';

            // Проверяем гео-радар, если подписчик его настроил
            if (
                $subscriber->tg_radar_lat !== null &&
                $subscriber->tg_radar_lng !== null &&
                $subscriber->tg_radar_radius_km !== null &&
                $subscriber->tg_radar_radius_km > 0
            ) {
                if ($lot->latitude === null || $lot->longitude === null) {
                    continue;
                }

                $distanceKm = $this->calculateDistanceKm(
                    (float) $subscriber->tg_radar_lat,
                    (float) $subscriber->tg_radar_lng,
                    (float) $lot->latitude,
                    (float) $lot->longitude
                );

                if ($distanceKm > (float) $subscriber->tg_radar_radius_km) {
                    continue;
                }

                $distanceInfo = ' (' . round($distanceKm, 1) . ' км от вас)';
            }

            $locationLine = $lot->location_name
                ? "\n📍 Площадка: <b>" . e($lot->location_name) . "</b>{$distanceInfo}"
                : ($distanceInfo ? "\n📍 Расстояние: <b>{$distanceInfo}</b>" : '');

            $text = "📡 <b>Новый лот в разделе «{$categoryName}»!</b>\n\n"
                . "🏷 <b>" . e($lot->title) . "</b>\n"
                . "💰 Цена: <b>{$priceText}</b>"
                . $locationLine . "\n"
                . "👤 Организатор: " . e($lot->seller?->username ?? '-') . "\n\n"
                . "👉 <a href=\"{$lotUrl}\">Открыть лот на SportPay</a>";

            if ($this->notifyUser($subscriber, $text)) {
                $sentCount++;
            }
        }

        return $sentCount;
    }

    public function calculateDistanceKm(float $lat1, float $lng1, float $lat2, float $lng2): float
    {
        $earthRadius = 6371.0;
        $dLat = deg2rad($lat2 - $lat1);
        $dLng = deg2rad($lng2 - $lng1);

        $a = sin($dLat / 2) ** 2
            + cos(deg2rad($lat1)) * cos(deg2rad($lat2)) * sin($dLng / 2) ** 2;

        return 2 * $earthRadius * asin(min(1.0, sqrt($a)));
    }

    public static function categoryLabels(): array
    {
        return [
            'team_game' => __('Поиск плюса на командную игру'),
            'training_plan' => __('Программы'),
            'coaching' => __('Тренерство'),
            'gym_membership' => __('Перепродажа абонемента в зал'),
            'event_ticket' => __('Билет на спортивное мероприятие'),
            'free_listing' => __('Бесплатные объявления'),
            'equipment' => __('Экипировка'),
        ];
    }

    public function sendMessage(string $chatId, string $text): bool
    {
        $botToken = (string) config('services.telegram.bot_token');
        if ($botToken === '' || $chatId === '') {
            return false;
        }

        try {
            $response = Http::timeout(5)
                ->asForm()
                ->post("https://api.telegram.org/bot{$botToken}/sendMessage", [
                    'chat_id' => $chatId,
                    'text' => $text,
                    'parse_mode' => 'HTML',
                    'disable_web_page_preview' => true,
                ]);

            return $response->successful();
        } catch (\Throwable $e) {
            Log::warning('Telegram sendMessage failed: ' . $e->getMessage());
            return false;
        }
    }

    public function notifyUser(User $user, string $text): bool
    {
        if (!$user->telegram_chat_id) {
            return false;
        }

        return $this->sendMessage((string) $user->telegram_chat_id, $text);
    }
}
