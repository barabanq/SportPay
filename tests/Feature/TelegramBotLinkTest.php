<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Enums\UserStatus;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class TelegramBotLinkTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_generate_link_token_and_bind_telegram_via_webhook(): void
    {
        Http::fake([
            'api.telegram.org/*' => Http::response(['ok' => true], 200),
        ]);

        config([
            'services.telegram.bot_token' => '123456:TEST_TOKEN',
            'services.telegram.bot_username' => 'SportPayBot',
            'services.telegram.webhook_secret' => 'test_secret',
        ]);

        $user = User::create([
            'username' => 'Athlete_TG',
            'email' => 'tg@sportpay.local',
            'password' => 'secret123',
            'wallet_balance' => 1000,
            'rating' => 5.00,
            'status' => UserStatus::Active,
        ]);

        // 1. Генерируем токен привязки
        $this->actingAs($user)
            ->post(route('telegram.connect'))
            ->assertRedirect();

        $user->refresh();
        $this->assertNotNull($user->telegram_link_token);
        $token = $user->telegram_link_token;

        // 2. Эмулируем приход вебхука от Telegram с командой /start <token>
        $webhookPayload = [
            'update_id' => 10001,
            'message' => [
                'message_id' => 1,
                'from' => [
                    'id' => 987654321,
                    'username' => 'sergei_athlete',
                ],
                'chat' => [
                    'id' => 987654321,
                    'username' => 'sergei_athlete',
                    'type' => 'private',
                ],
                'text' => "/start {$token}",
            ],
        ];

        $this->withHeaders([
            'X-Telegram-Bot-Api-Secret-Token' => 'test_secret',
        ])->postJson(route('telegram.webhook'), $webhookPayload)
            ->assertOk()
            ->assertJson(['ok' => true, 'linked' => true]);

        // 3. Проверяем, что chat_id и username сохранились, а одноразовый токен сброшен
        $user->refresh();
        $this->assertSame('987654321', $user->telegram_chat_id);
        $this->assertSame('sergei_athlete', $user->telegram_username);
        $this->assertNull($user->telegram_link_token);

        // 4. Отвязываем Telegram
        $this->actingAs($user)
            ->post(route('telegram.disconnect'))
            ->assertRedirect();

        $user->refresh();
        $this->assertNull($user->telegram_chat_id);
        $this->assertNull($user->telegram_username);
    }
}
