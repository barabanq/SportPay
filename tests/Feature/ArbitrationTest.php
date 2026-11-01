<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Enums\LotStatus;
use App\Enums\OrderStatus;
use App\Enums\UserStatus;
use App\Models\Lot;
use App\Models\User;
use App\Services\EscrowService;
use DomainException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ArbitrationTest extends TestCase
{
    use RefreshDatabase;

    private EscrowService $escrowService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->escrowService = $this->app->make(EscrowService::class);
    }

    public function test_arbiter_can_resolve_dispute_in_favor_of_buyer_with_full_refund(): void
    {
        $arbiter = $this->createUser('Arbiter_Judge', 'arbiter@sportpay.local', 0.00);
        $seller = $this->createUser('Coach_Alex', 'seller@sportpay.local', 0.00);
        $buyer = $this->createUser('Runner_Max', 'buyer@sportpay.local', 3000.00);
        $lot = $this->createLot($seller, price: 2000.00, stock: 1);

        $order = $this->escrowService->purchaseLot($buyer->id, $lot->id);
        $this->escrowService->openDispute($order->id, $buyer->id, 'Товар не соответствует описанию');

        $resolved = $this->escrowService->resolveDispute(
            orderId: $order->id,
            arbiterId: $arbiter->id,
            decision: 'refund_buyer',
            comment: 'Продавец не предоставил план тренировок'
        );

        $this->assertSame(OrderStatus::Cancelled, $resolved->status);
        $this->assertSame(3000.00, (float) $buyer->fresh()->wallet_balance);
        $this->assertSame(0.00, (float) $seller->fresh()->wallet_balance);
        $this->assertSame(1, $lot->fresh()->stock_quantity);
    }

    public function test_arbiter_can_resolve_dispute_in_favor_of_seller_with_payout(): void
    {
        $arbiter = $this->createUser('Arbiter_Judge', 'arbiter@sportpay.local', 0.00);
        $seller = $this->createUser('Coach_Alex', 'seller@sportpay.local', 500.00);
        $buyer = $this->createUser('Runner_Max', 'buyer@sportpay.local', 3000.00);
        $lot = $this->createLot($seller, price: 2000.00, stock: 2);

        $order = $this->escrowService->purchaseLot($buyer->id, $lot->id);
        $this->escrowService->openDispute($order->id, $buyer->id, 'Хочу вернуть деньги просто так');

        $resolved = $this->escrowService->resolveDispute(
            orderId: $order->id,
            arbiterId: $arbiter->id,
            decision: 'release_seller',
            comment: 'Услуга оказана в полном объеме'
        );

        // 500 + (2000 - 5%) = 2400 ₽
        $this->assertSame(OrderStatus::Completed, $resolved->status);
        $this->assertSame(2400.00, (float) $seller->fresh()->wallet_balance);
        $this->assertSame(1000.00, (float) $buyer->fresh()->wallet_balance);
    }

    public function test_non_arbiter_cannot_resolve_dispute(): void
    {
        $seller = $this->createUser('Coach_Alex', 'seller@sportpay.local', 0.00);
        $buyer = $this->createUser('Runner_Max', 'buyer@sportpay.local', 3000.00);
        $lot = $this->createLot($seller, price: 2000.00, stock: 1);

        $order = $this->escrowService->purchaseLot($buyer->id, $lot->id);
        $this->escrowService->openDispute($order->id, $buyer->id, 'Спор');

        $this->expectException(DomainException::class);
        $this->expectExceptionMessage('Только официальный арбитр SportPay может выносить решение по спору.');

        $this->escrowService->resolveDispute($order->id, $seller->id, 'release_seller');
    }

    private function createUser(string $username, string $email, float $balance): User
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

    private function createLot(User $seller, float $price, int $stock): Lot
    {
        return Lot::create([
            'seller_id' => $seller->id,
            'title' => 'Спортивный лот',
            'product_type' => 'coaching',
            'description' => 'Описание лота',
            'price' => $price,
            'stock_quantity' => $stock,
            'status' => LotStatus::Active,
        ]);
    }
}