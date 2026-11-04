<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Enums\LotStatus;
use App\Enums\UserStatus;
use App\Models\Lot;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LocalizationAndFilterTest extends TestCase
{
    use RefreshDatabase;

    private function createActiveUser(string $username, float $balance = 0.0): User
    {
        return User::create([
            'username' => $username,
            'email' => strtolower($username) . '@sportpay.local',
            'password' => 'secret123',
            'wallet_balance' => $balance,
            'rating' => 5.00,
            'status' => UserStatus::Active,
        ]);
    }

    public function test_switching_to_english_translates_all_main_pages_and_switching_back_restores_russian(): void
    {
        $user = $this->createActiveUser('Locale_User');

        // Переключаем на английский
        $this->actingAs($user)
            ->post(route('locale.switch'), ['locale' => 'en'])
            ->assertRedirect();

        $this->assertSame('en', session('locale'));

        // 1. Главная витрина и шапка
        $this->actingAs($user)->get(route('home'))
            ->assertOk()
            ->assertSee('Marketplace')
            ->assertSee('Sports Marketplace Lots')
            ->assertSee('All Categories')
            ->assertSee('Create a Lot')
            ->assertDontSee('Витрина спортивных лотов');

        // 2. Диалоги по сделкам (/messages)
        $this->actingAs($user)->get(route('chat.index'))
            ->assertOk()
            ->assertSee('Order Conversations')
            ->assertDontSee('Диалоги по сделкам');

        // 3. Общий чат (/global-chat)
        $this->actingAs($user)->get(route('chat.global'))
            ->assertOk()
            ->assertSee('SportPay Global Sports Chat')
            ->assertSee('Live')
            ->assertDontSee('Общий спортивный чат SportPay');

        // 4. Панель Арбитража (/arbitration)
        $this->actingAs($user)->get(route('arbitration.index'))
            ->assertOk()
            ->assertSee('SportPay Arbitration Panel')
            ->assertSee('No active disputes right now. All deals are running smoothly!')
            ->assertDontSee('Панель Арбитража SportPay');

        // 5. Кошелек (/wallet)
        $this->actingAs($user)->get(route('wallet.index'))
            ->assertOk()
            ->assertSee('Available Balance')
            ->assertSee('Financial Transaction History');

        // Переключаем обратно на русский
        $this->actingAs($user)
            ->post(route('locale.switch'), ['locale' => 'ru'])
            ->assertRedirect();

        $this->actingAs($user)->get(route('home'))
            ->assertOk()
            ->assertSee('Витрина спортивных лотов');

        $this->actingAs($user)->get(route('arbitration.index'))
            ->assertOk()
            ->assertSee('Панель Арбитража SportPay');
    }

    public function test_marketplace_filters_lots_by_min_max_price_and_seller(): void
    {
        $sellerMax = $this->createActiveUser('Runner_Max');
        $sellerOther = $this->createActiveUser('Coach_Olga');

        Lot::create([
            'seller_id' => $sellerMax->id,
            'title' => 'Бюджетный план бега',
            'product_type' => 'training_plan',
            'price' => 400,
            'stock_quantity' => 5,
            'status' => LotStatus::Active,
        ]);

        Lot::create([
            'seller_id' => $sellerMax->id,
            'title' => 'Средний марафонский слот',
            'product_type' => 'race_slot',
            'price' => 1200,
            'stock_quantity' => 5,
            'status' => LotStatus::Active,
        ]);

        Lot::create([
            'seller_id' => $sellerOther->id,
            'title' => 'Премиум велостанок',
            'product_type' => 'equipment',
            'price' => 1200,
            'stock_quantity' => 3,
            'status' => LotStatus::Active,
        ]);

        $response = $this->actingAs($sellerOther)->get(route('home', [
            'min_price' => 500,
            'max_price' => 1500,
            'seller' => 'Runner_Max',
        ]));

        $response->assertOk();
        $response->assertSee('Средний марафонский слот');
        $response->assertDontSee('Бюджетный план бега');
        $response->assertDontSee('Премиум велостанок');
    }

    public function test_seller_modal_json_endpoint_returns_profile_summary(): void
    {
        $seller = $this->createActiveUser('Pro_Trainer');

        Lot::create([
            'seller_id' => $seller->id,
            'title' => 'Персональная тренировка',
            'product_type' => 'coaching',
            'price' => 2500,
            'stock_quantity' => 4,
            'status' => LotStatus::Active,
        ]);

        $response = $this->actingAs($seller)->getJson(route('users.modal', $seller));

        $response->assertOk()
            ->assertJson([
                'id' => $seller->id,
                'username' => 'Pro_Trainer',
                'active_lots_count' => 1,
            ]);
    }
}
