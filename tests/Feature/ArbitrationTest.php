<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Enums\LotStatus;
use App\Enums\OrderStatus;
use App\Enums\UserStatus;
use App\Models\Lot;
use App\Models\Order;
use App\Models\User;
use App\Services\EscrowService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ArbitrationTest extends TestCase
{
    use RefreshDatabase;

    private function createUser(string $username, float $balance = 0.0, ?string $email = null): User
    {
        return User::create([
            'username' => $username,
            'email' => $email ?? strtolower($username).'_'.uniqid().'@sportpay.local',
            'password' => 'secret123',
            'wallet_balance' => $balance,
            'rating' => 5.00,
            'status' => UserStatus::Active,
        ]);
    }

    private function createDisputedOrder(User $buyer, User $seller, float $price = 2000.00): Order
    {
        $lot = Lot::create([
            'seller_id' => $seller->id,
            'title' => 'Спорный слот',
            'product_type' => 'race_slot',
            'price' => $price,
            'stock_quantity' => 2,
            'status' => LotStatus::Active,
        ]);

        $escrow = app(EscrowService::class);
        $order = $escrow->purchaseLot($buyer, $lot, 1);

        return $escrow->openDispute($order, $buyer, 'Причина спора для арбитража');
    }

    public function test_arbiter_can_resolve_dispute_in_favor_of_buyer_with_full_refund(): void
    {
        $buyer = $this->createUser('Buyer_Disp', 3000.00);
        $seller = $this->createUser('Seller_Disp', 0.00);
        $arbiter = $this->createUser('Arbiter_Judge', 0.00, 'arbiter@sportpay.local');

        $order = $this->createDisputedOrder($buyer, $seller, 2000.00);

        $this->actingAs($arbiter)
            ->post(route('orders.resolve', $order), [
                'decision' => 'refund_buyer',
                'comment' => 'Продавец не предоставил слот',
            ])
            ->assertRedirect();

        $this->assertSame(OrderStatus::Cancelled, $order->fresh()->status);
        $this->assertEquals(3000.00, (float) $buyer->fresh()->wallet_balance);
        $this->assertEquals(0.00, (float) $seller->fresh()->wallet_balance);
    }

    public function test_arbiter_can_resolve_dispute_in_favor_of_seller_with_payout(): void
    {
        $buyer = $this->createUser('Buyer_Disp2', 3000.00);
        $seller = $this->createUser('Seller_Disp2', 0.00);
        $arbiter = $this->createUser('Arbiter_Judge', 0.00, 'arbiter@sportpay.local');

        $order = $this->createDisputedOrder($buyer, $seller, 2000.00);

        $this->actingAs($arbiter)
            ->post(route('orders.resolve', $order), [
                'decision' => 'release_seller',
                'comment' => 'Услуга оказана полностью',
            ])
            ->assertRedirect();

        $this->assertSame(OrderStatus::Completed, $order->fresh()->status);
        $this->assertEquals(1900.00, (float) $seller->fresh()->wallet_balance);
    }

    public function test_non_arbiter_cannot_resolve_dispute(): void
    {
        $buyer = $this->createUser('Buyer_Disp3', 3000.00);
        $seller = $this->createUser('Seller_Disp3', 0.00);

        $order = $this->createDisputedOrder($buyer, $seller, 2000.00);

        $this->actingAs($buyer)
            ->post(route('orders.resolve', $order), [
                'decision' => 'refund_buyer',
            ])
            ->assertForbidden();

        $this->assertSame(OrderStatus::Disputed, $order->fresh()->status);
    }
}
