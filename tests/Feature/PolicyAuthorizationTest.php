<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Enums\LotStatus;
use App\Enums\UserStatus;
use App\Models\Lot;
use App\Models\User;
use App\Services\EscrowService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Gate;
use Tests\TestCase;

class PolicyAuthorizationTest extends TestCase
{
    use RefreshDatabase;

    public function test_lot_policy_allows_only_seller_to_update_or_delete_lot(): void
    {
        $seller = $this->createUser('Coach_Owner', 'owner@sportpay.local');
        $stranger = $this->createUser('Runner_Stranger', 'stranger@sportpay.local');
        $arbiter = $this->createUser('Arbiter_Judge', 'arbiter@sportpay.local');

        $lot = $this->createLot($seller);

        // Владелец может редактировать и удалять
        $this->assertTrue(Gate::forUser($seller)->allows('update', $lot));
        $this->assertTrue(Gate::forUser($seller)->allows('delete', $lot));
        $this->assertFalse(Gate::forUser($seller)->allows('purchase', $lot));

        // Посторонний юзер не может редактировать или удалять чужой лот, но может купить
        $this->assertFalse(Gate::forUser($stranger)->allows('update', $lot));
        $this->assertFalse(Gate::forUser($stranger)->allows('delete', $lot));
        $this->assertTrue(Gate::forUser($stranger)->allows('purchase', $lot));

        // Арбитр может удалить нарушающий правила лот
        $this->assertTrue(Gate::forUser($arbiter)->allows('delete', $lot));
    }

    public function test_order_policy_restricts_view_and_actions_to_participants_and_arbiter(): void
    {
        $seller = $this->createUser('Coach_Seller', 'seller@sportpay.local');
        $buyer = $this->createUser('Runner_Buyer', 'buyer@sportpay.local', balance: 5000.00);
        $hacker = $this->createUser('Outsider_User', 'outsider@sportpay.local');
        $arbiter = $this->createUser('Arbiter_Judge', 'arbiter@sportpay.local');

        $lot = $this->createLot($seller);

        /** @var EscrowService $escrow */
        $escrow = $this->app->make(EscrowService::class);
        $order = $escrow->purchaseLot($buyer->id, $lot->id);

        // Посторонний пользователь получает 403 при попытке открыть чужую сделку или написать в чат
        $this->actingAs($hacker)->get(route('orders.show', $order))->assertForbidden();
        $this->actingAs($hacker)->post(route('orders.messages.store', $order), ['body' => 'Привет'])->assertForbidden();

        // Покупатель может подтвердить заказ, но не может отменить его напрямую
        $this->assertTrue(Gate::forUser($buyer)->allows('confirm', $order));
        $this->assertFalse(Gate::forUser($buyer)->allows('cancel', $order));

        // Продавец может отменить заказ (сделать возврат), но не может подтвердить за покупателя
        $this->assertTrue(Gate::forUser($seller)->allows('cancel', $order));
        $this->assertFalse(Gate::forUser($seller)->allows('confirm', $order));

        // Арбитр имеет доступ к просмотру сделки и вынесению вердикта
        $this->assertTrue(Gate::forUser($arbiter)->allows('view', $order));
        $this->assertTrue(Gate::forUser($arbiter)->allows('resolve', $order));
        $this->assertFalse(Gate::forUser($buyer)->allows('resolve', $order));
    }

    private function createUser(string $username, string $email, float $balance = 1000.00): User
    {
        return User::create([
            'username' => $username,
            'email' => $email,
            'password' => 'secret123',
            'wallet_balance' => $balance,
            'rating' => 5.00,
            'status' => UserStatus::Active,
        ]);
    }

    private function createLot(User $seller): Lot
    {
        return Lot::create([
            'seller_id' => $seller->id,
            'title' => 'Индивидуальный план бега',
            'product_type' => 'training_plan',
            'description' => 'План на 8 недель',
            'price' => 1500.00,
            'stock_quantity' => 5,
            'status' => LotStatus::Active,
        ]);
    }
}