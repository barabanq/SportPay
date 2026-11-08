<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class TelegramBotService
{
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
        ])->save();

        $this->sendMessage(
            $chatId,
            "✅ <b>Аккаунт {$user->username} успешно привязан к SportPay!</b>\n\nТеперь сюда будут приходить уведомления."
        );

        return true;
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
