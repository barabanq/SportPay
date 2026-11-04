<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Enums\LotStatus;
use App\Enums\OrderStatus;
use App\Enums\TransactionType;
use App\Enums\UserStatus;
use App\Models\Lot;
use App\Models\User;
use App\Services\EscrowService;
use DomainException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EscrowServiceTest extends TestCase
{
    use RefreshDatabase;

    private EscrowService $escrowService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->escrowService = app(EscrowService::class);
    }

    private function createUser(string $username, float $balance = 0.0): User
    {
        return User::create([
            'username' => $username,
            'email' => strtolower($username).'_'.uniqid().'@sportpay.local',
            'password' => 'secret123',
            'wallet_balance' => $balance,
            'rating' => 5.00,
            'status' => UserStatus::Active,
        ]);
    }

    public function test_purchase_lot_holds_buyer_funds_decrements_stock_and_logs_system_message(): void
    {
        $seller = $this->createUser('Coach_Pro', 0.00);
        $buyer = $this->createUser('Runner_Buyer', 5000.00);

        $lot = Lot::create([
            'seller_id' => $seller->id,
            'title' => 'План подготовки к марафону',
            'product_type' => 'training_plan',
            'description' => '16 недель',
            'price' => 2000.00,
            'stock_quantity' => 3,
            'status' => LotStatus::Active,
        ]);

        $order = $this->escrowService->purchaseLot($buyer, $lot, 1);

        $this->assertSame(OrderStatus::Paid, $order->status);
        $this->assertEquals(3000.00, (float) $buyer->fresh()->wallet_balance);
        $this->assertEquals(0.00, (float) $seller->fresh()->wallet_balance);
        $this->assertSame(2, $lot->fresh()->stock_quantity);
        $this->assertDatabaseHas('wallet_transactions', [
            'user_id' => $buyer->id,
            'order_id' => $order->id,
            'type' => TransactionType::Purchase->value,
        ]);
    }

    public function test_confirm_order_pays_seller_minus_5_percent_platform_fee_and_updates_rating(): void
    {
        $seller = $this->createUser('Coach_Seller', 0.00);
        $buyer = $this->createUser('Buyer_One', 5000.00);

        $lot = Lot::create([
            'seller_id' => $seller->id,
            'title' => 'Разбор техники бега',
            'product_type' => 'coaching',
            'price' => 2000.00,
            'stock_quantity' => 2,
            'status' => LotStatus::Active,
        ]);

        $order = $this->escrowService->purchaseLot($buyer, $lot, 1);
        $confirmed = $this->escrowService->confirmOrder($order, $buyer, 4, 'Хороший разбор!');

        $this->assertSame(OrderStatus::Completed, $confirmed->status);
        // 2000 - 5% = 1900
        $this->assertEquals(1900.00, (float) $seller->fresh()->wallet_balance);
        $this->assertEquals(4.00, (float) $seller->fresh()->rating);
    }

    public function test_buyer_cannot_cancel_paid_order_directly_without_dispute(): void
    {
        $seller = $this->createUser('Seller_A', 0.00);
        $buyer = $this->createUser('Buyer_A', 3000.00);

        $lot = Lot::create([
            'seller_id' => $seller->id,
            'title' => 'Слот на забег 10 км',
            'product_type' => 'race_slot',
            'price' => 1500.00,
            'stock_quantity' => 1,
            'status' => LotStatus::Active,
        ]);

        $order = $this->escrowService->purchaseLot($buyer, $lot, 1);

        $this->expectException(DomainException::class);
        $this->escrowService->cancelOrder($order, $buyer);
    }

    public function test_seller_can_cancel_order_and_full_amount_is_refunded_to_buyer_with_stock_restored(): void
    {
        $seller = $this->createUser('Seller_Cancel', 0.00);
        $buyer = $this->createUser('Buyer_Refund', 3000.00);

        $lot = Lot::create([
            'seller_id' => $seller->id,
            'title' => 'Часы пульсометр',
            'product_type' => 'equipment',
            'price' => 2500.00,
            'stock_quantity' => 1,
            'status' => LotStatus::Active,
        ]);

        $order = $this->escrowService->purchaseLot($buyer, $lot, 1);
        $this->assertSame(0, $lot->fresh()->stock_quantity);

        $cancelled = $this->escrowService->cancelOrder($order, $seller);

        $this->assertSame(OrderStatus::Cancelled, $cancelled->status);
        $this->assertEquals(3000.00, (float) $buyer->fresh()->wallet_balance);
        $this->assertSame(1, $lot->fresh()->stock_quantity);
        $this->assertSame(LotStatus::Active, $lot->fresh()->status);
    }

    public function test_dispute_can_be_opened_by_buyer_and_then_refunded(): void
    {
        $seller = $this->createUser('Seller_Dispute', 0.00);
        $buyer = $this->createUser('Buyer_Dispute', 4000.00);
        $arbiter = $this->createUser('Arbiter_Judge', 0.00);

        $lot = Lot::create([
            'seller_id' => $seller->id,
            'title' => 'Слот Триатлон',
            'product_type' => 'race_slot',
            'price' => 3000.00,
            'stock_quantity' => 1,
            'status' => LotStatus::Active,
        ]);

        $order = $this->escrowService->purchaseLot($buyer, $lot, 1);
        $disputed = $this->escrowService->openDispute($order, $buyer, 'Продавец не отвечает');

        $this->assertSame(OrderStatus::Disputed, $disputed->status);

        $resolved = $this->escrowService->resolveDispute($disputed, $arbiter, 'refund_buyer', 'Возврат покупателю');
        $this->assertSame(OrderStatus::Cancelled, $resolved->status);
        $this->assertEquals(4000.00, (float) $buyer->fresh()->wallet_balance);
    }

    public function test_purchase_lot_with_auto_delivery_immediately_marks_order_shipped_and_sends_secret_payload(): void
    {
        $seller = $this->createUser('Auto_Seller', 0.00);
        $buyer = $this->createUser('Auto_Buyer', 2000.00);

        $lot = Lot::create([
            'seller_id' => $seller->id,
            'title' => 'PDF Гайд по питанию',
            'product_type' => 'training_plan',
            'auto_delivery_text' => 'https://sportpay.local/secret-pdf-link',
            'price' => 1000.00,
            'stock_quantity' => 5,
            'status' => LotStatus::Active,
        ]);

        $order = $this->escrowService->purchaseLot($buyer, $lot, 1);

        $this->assertSame(OrderStatus::Shipped, $order->status);
        $this->assertDatabaseHas('messages', [
            'order_id' => $order->id,
            'is_system' => true,
        ]);
    }
}
