<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Services\TelegramBotService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class TelegramController extends Controller
{
    private const ALLOWED_CATEGORIES = [
        'team_game',
        'training_plan',
        'coaching',
        'gym_membership',
        'event_ticket',
        'free_listing',
        'equipment',
    ];

    public function connect(Request $request, TelegramBotService $telegram): RedirectResponse
    {
        $user = $this->currentUser();
        $deepLink = $telegram->getDeepLink($user);

        if ($request->boolean('redirect_to_bot')) {
            return redirect()->away($deepLink);
        }

        return redirect()->back()->with([
            'success' => __('Скопируйте команду ниже и отправьте её боту в Telegram:') . ' /start ' . $user->telegram_link_token,
            'telegram_deep_link' => $deepLink,
        ]);
    }

    public function updatePreferences(Request $request): RedirectResponse
    {
        $user = $this->currentUser();

        $data = $request->validate([
            'tg_notify_messages' => ['nullable', 'boolean'],
            'categories' => ['nullable', 'array'],
            'categories.*' => ['string', 'in:' . implode(',', self::ALLOWED_CATEGORIES)],
            'tg_radar_lat' => ['nullable', 'numeric', 'between:-90,90'],
            'tg_radar_lng' => ['nullable', 'numeric', 'between:-180,180'],
            'tg_radar_radius_km' => ['nullable', 'integer', 'min:1', 'max:5000'],
        ]);

        $user->forceFill([
            'tg_notify_messages' => $request->boolean('tg_notify_messages'),
            'tg_subscribed_categories' => array_values(array_unique($data['categories'] ?? [])),
            'tg_radar_lat' => $data['tg_radar_lat'] ?? null,
            'tg_radar_lng' => $data['tg_radar_lng'] ?? null,
            'tg_radar_radius_km' => $data['tg_radar_radius_km'] ?? null,
        ])->save();

        return redirect()->back()->with('success', __('Настройки Telegram-уведомлений и радара сохранены!'));
    }

    public function disconnect(): RedirectResponse
    {
        $user = $this->currentUser();
        $user->forceFill([
            'telegram_chat_id' => null,
            'telegram_username' => null,
            'telegram_link_token' => null,
        ])->save();

        return redirect()->back()->with('success', __('Telegram-бот успешно отвязан от аккаунта.'));
    }

    public function sendTest(TelegramBotService $telegram): RedirectResponse
    {
        $user = $this->currentUser();

        if (!$user->telegram_chat_id) {
            return redirect()->back()->withErrors(['telegram' => __('Сначала привяжите Telegram-аккаунт.')]);
        }

        $sent = $telegram->notifyUser(
            $user,
            "🔔 <b>Тестовое уведомление SportPay</b>\nСвязь с аккаунтом <b>{$user->username}</b> работает отлично!"
        );

        if (!$sent) {
            return redirect()->back()->with('success', __('Аккаунт привязан (проверьте TELEGRAM_BOT_TOKEN в .env).'));
        }

        return redirect()->back()->with('success', __('Тестовое уведомление отправлено в ваш Telegram!'));
    }

    public function webhook(Request $request, TelegramBotService $telegram): JsonResponse
    {
        $expectedSecret = (string) config('services.telegram.webhook_secret');
        $headerSecret = (string) $request->header('X-Telegram-Bot-Api-Secret-Token', '');

        if ($expectedSecret !== '' && $headerSecret !== '' && !hash_equals($expectedSecret, $headerSecret)) {
            return response()->json(['ok' => false, 'error' => 'Invalid secret'], 403);
        }

        $linked = $telegram->handleUpdate($request->all());

        return response()->json(['ok' => true, 'linked' => $linked]);
    }
}
