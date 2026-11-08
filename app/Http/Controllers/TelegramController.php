<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Services\TelegramBotService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class TelegramController extends Controller
{
    public function connect(TelegramBotService $telegram): RedirectResponse
    {
        $user = $this->currentUser();
        $deepLink = $telegram->getDeepLink($user);

        return redirect()->back()->with([
            'success' => __('Ссылка для привязки Telegram сгенерирована! Нажмите «Открыть бота» или отправьте команду боту.'),
            'telegram_deep_link' => $deepLink,
        ]);
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
            return redirect()->back()->with('success', __('Аккаунт привязан (в локальном режиме проверьте TELEGRAM_BOT_TOKEN в .env для реальной отправки).'));
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
