<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Enums\UserStatus;
use App\Models\User;
use App\Services\TelegramBotService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class TelegramBotLinkTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Http::fake([
            'api.telegram.org/*' => Http::response(['ok' => true, 'result' => ['username' => 'SportPayBot']], 200),
        ]);

        config([
            'services.telegram.bot_token' => '123456:TEST_TOKEN',
            'services.telegram.bot_username' => 'SportPayBot',
            'services.telegram.webhook_secret' => 'test_secret',
        ]);
    }

    public function test_user_can_generate_link_token_and_bind_telegram_via_webhook(): void
    {
        $user = User::create([
            'username' => 'Athlete_TG',
            'email' => 'tg@sportpay.local',
            'password' => 'secret123',
            'wallet_balance' => 1000,
            'rating' => 5.00,
            'status' => UserStatus::Active,
        ]);

        $this->actingAs($user)
            ->post(route('telegram.connect'))
            ->assertRedirect();

        $user->refresh();
        $this->assertNotNull($user->telegram_link_token);
        $token = $user->telegram_link_token;

        $webhookPayload = [
            'update_id' => 10001,
            'message' => [
                'message_id' => 1,
                'from' => ['id' => 987654321, 'username' => 'sergei_athlete'],
                'chat' => ['id' => 987654321, 'username' => 'sergei_athlete', 'type' => 'private'],
                'text' => "/start {$token}",
            ],
        ];

        $this->withHeaders([
            'X-Telegram-Bot-Api-Secret-Token' => 'test_secret',
        ])->postJson(route('telegram.webhook'), $webhookPayload)
            ->assertOk()
            ->assertJson(['ok' => true, 'linked' => true]);

        $user->refresh();
        $this->assertSame('987654321', $user->telegram_chat_id);
        $this->assertSame('sergei_athlete', $user->telegram_username);
        $this->assertNull($user->telegram_link_token);
    }

    public function test_category_radar_with_geo_radius_notifies_only_matching_subscribers(): void
    {
        $seller = User::create([
            'username' => 'Organizer_NN',
            'email' => 'org@sportpay.local',
            'password' => 'secret123',
            'wallet_balance' => 1000,
            'rating' => 5.00,
            'status' => UserStatus::Active,
        ]);

        $nnSubscriber = User::create([
            'username' => 'Player_NN',
            'email' => 'nn@sportpay.local',
            'password' => 'secret123',
            'wallet_balance' => 1000,
            'rating' => 5.00,
            'status' => UserStatus::Active,
            'telegram_chat_id' => '111222333',
        ]);

        $this->actingAs($nnSubscriber)->post(route('telegram.preferences'), [
            'tg_notify_messages' => 1,
            'categories' => ['team_game', 'gym_membership'],
            'tg_radar_lat' => 56.3269,
            'tg_radar_lng' => 44.0059,
            'tg_radar_radius_km' => 25,
        ])->assertRedirect();

        $this->actingAs($seller)->post(route('lots.store'), [
            'title' => 'Волейбол в ФОК Мещерский (+1 игрок)',
            'product_type' => 'team_game',
            'price' => 500,
            'stock_quantity' => 6,
            'location_name' => 'Нижний Новгород, ФОК Мещерский',
            'latitude' => 56.3399,
            'longitude' => 43.9332,
        ])->assertRedirect(route('home'));

        Http::assertSent(function ($request) {
            return str_contains($request->url(), 'sendMessage')
                && $request['chat_id'] === '111222333'
                && str_contains($request['text'], 'Волейбол в ФОК Мещерский');
        });

        $this->actingAs($seller)->post(route('lots.store'), [
            'title' => 'Футбол в Лужниках',
            'product_type' => 'team_game',
            'price' => 800,
            'stock_quantity' => 10,
            'location_name' => 'Москва, Лужники',
            'latitude' => 55.7158,
            'longitude' => 37.5515,
        ])->assertRedirect(route('home'));

        Http::assertNotSent(function ($request) {
            return str_contains($request->url(), 'sendMessage')
                && $request['chat_id'] === '111222333'
                && str_contains($request['text'], 'Футбол в Лужниках');
        });
    }

    public function test_chat_notification_respects_cooldown_and_resets_when_chat_is_read(): void
    {
        Cache::flush();

        $recipient = User::create([
            'username' => 'Coach_Recipient',
            'email' => 'coach@sportpay.local',
            'password' => 'secret123',
            'wallet_balance' => 1000,
            'rating' => 5.00,
            'status' => UserStatus::Active,
            'telegram_chat_id' => '555666777',
            'tg_notify_messages' => true,
        ]);

        $sender = User::create([
            'username' => 'Buyer_Sender',
            'email' => 'buyer@sportpay.local',
            'password' => 'secret123',
            'wallet_balance' => 5000,
            'rating' => 5.00,
            'status' => UserStatus::Active,
        ]);

        $tgService = app(TelegramBotService::class);

        $firstSent = $tgService->notifyDirectMessage($recipient, $sender, 'Привет! Есть место на тренировку?');
        $this->assertTrue($firstSent);
        $this->assertTrue(Cache::has("tg_chat_notify:{$recipient->id}:{$sender->id}"));

        $secondSent = $tgService->notifyDirectMessage($recipient, $sender, 'Я готов оплатить прямо сейчас');
        $this->assertFalse($secondSent);

        // Явно вызываем сброс кулдауна при открытии чата (эмулируем логику контроллера)
        $tgService->clearChatCooldown($recipient->id, $sender->id);
        $this->assertFalse(Cache::has("tg_chat_notify:{$recipient->id}:{$sender->id}"));

        $thirdSent = $tgService->notifyDirectMessage($recipient, $sender, 'Отлично, жду ответа!');
        $this->assertTrue($thirdSent);
    }

    public function test_seller_receives_telegram_push_when_buyer_purchases_lot(): void
    {
        $seller = User::create([
            'username' => 'Seller_Push',
            'email' => 'seller_push@sportpay.local',
            'password' => 'secret123',
            'wallet_balance' => 1000,
            'rating' => 5.00,
            'status' => UserStatus::Active,
            'telegram_chat_id' => '999888777',
            'tg_notify_messages' => true,
        ]);

        $buyer = User::create([
            'username' => 'Buyer_Push',
            'email' => 'buyer_push@sportpay.local',
            'password' => 'secret123',
            'wallet_balance' => 5000,
            'rating' => 5.00,
            'status' => UserStatus::Active,
        ]);

        $this->actingAs($seller)->post(route('lots.store'), [
            'title' => 'Абонемент в фитнес-клуб на 6 месяцев',
            'product_type' => 'gym_membership',
            'price' => 1500,
            'stock_quantity' => 1,
        ]);

        $lot = \App\Models\Lot::firstOrFail();

        $this->actingAs($buyer)->post(route('lots.purchase', $lot), [
            'quantity' => 1,
        ])->assertRedirect();

        Http::assertSent(function ($request) {
            return str_contains($request->url(), 'sendMessage')
                && $request['chat_id'] === '999888777'
                && str_contains($request['text'], 'Новая покупка на SportPay!')
                && str_contains($request['text'], 'Абонемент в фитнес-клуб');
        });
    }
}
