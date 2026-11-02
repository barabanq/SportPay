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
        $this->escrowService = $this->app->make(EscrowService::class);
    }

    public function test_purchase_lot_holds_buyer_funds_decrements_stock_and_logs_system_message(): void
    {
        // Arrange
        $seller = $this->createUser('Coach_Alex', balance: 0.00);
        $buyer = $this->createUser('Runner_Max', balance: 5000.00);
        $lot = $this->createLot($seller, price: 2000.00, stock: 3);

        // Act
        $order = $this->escrowService->purchaseLot($buyer->id, $lot->id, quantity: 1);

        // Assert
        $this->assertSame(OrderStatus::Paid, $order->status);
        $this->assertSame(3000.00, (float) $buyer->fresh()->wallet_balance);
        $this->assertSame(0.00, (float) $seller->fresh()->wallet_balance); // Деньги в холде, а не у продавца
        $this->assertSame(2, $lot->fresh()->stock_quantity);

        $this->assertDatabaseHas('wallet_transactions', [
            'user_id' => $buyer->id,
            'order_id' => $order->id,
            'type' => TransactionType::Purchase->value,
            'amount' => -2000.00,
            'balance_after' => 3000.00,
        ]);

        $this->assertDatabaseHas('messages', [
            'order_id' => $order->id,
            'is_system' => true,
        ]);
    }

    public function test_confirm_order_pays_seller_minus_5_percent_platform_fee_and_updates_rating(): void
    {
        // Arrange
        $seller = $this->createUser('Coach_Alex', balance: 1000.00);
        $buyer = $this->createUser('Runner_Max', balance: 5000.00);
        $lot = $this->createLot($seller, price: 2000.00, stock: 5);

        $order = $this->escrowService->purchaseLot($buyer->id, $lot->id);

        // Act: Покупатель подтверждает заказ и ставит оценку 4
        $confirmedOrder = $this->escrowService->confirmOrder(
            orderId: $order->id,
            buyerId: $buyer->id,
            rating: 4,
            comment: 'Отличный план подготовки!'
        );

        // Assert: 2000 - 5% = 1900 ₽ выплаты продавцу (1000 + 1900 = 2900 ₽)
        $this->assertSame(OrderStatus::Completed, $confirmedOrder->status);
        $this->assertSame(2900.00, (float) $seller->fresh()->wallet_balance);
        $this->assertSame(4.00, (float) $seller->fresh()->rating);

        $this->assertDatabaseHas('wallet_transactions', [
            'user_id' => $seller->id,
            'order_id' => $order->id,
            'type' => TransactionType::Payout->value,
            'amount' => 1900.00,
            'balance_after' => 2900.00,
        ]);

        $this->assertDatabaseHas('reviews', [
            'order_id' => $order->id,
            'seller_id' => $seller->id,
            'rating' => 4,
        ]);
    }

    public function test_buyer_cannot_cancel_paid_order_directly_without_dispute(): void
    {
        // Arrange
        $seller = $this->createUser('Coach_Alex', balance: 0.00);
        $buyer = $this->createUser('Runner_Max', balance: 3000.00);
        $lot = $this->createLot($seller, price: 1500.00, stock: 1);

        $order = $this->escrowService->purchaseLot($buyer->id, $lot->id);

        // Assert & Act
        $this->expectException(DomainException::class);
        $this->expectExceptionMessage('Покупатель не может отменить оплаченный заказ напрямую.');

        $this->escrowService->cancelAndRefundOrder($order->id, $buyer->id);
    }

    public function test_seller_can_cancel_order_and_full_amount_is_refunded_to_buyer_with_stock_restored(): void
    {
        // Arrange
        $seller = $this->createUser('Coach_Alex', balance: 0.00);
        $buyer = $this->createUser('Runner_Max', balance: 3000.00);
        $lot = $this->createLot($seller, price: 1500.00, stock: 1);

        $order = $this->escrowService->purchaseLot($buyer->id, $lot->id);
        $this->assertSame(0, $lot->fresh()->stock_quantity);

        // Act: Продавец делает возврат
        $cancelledOrder = $this->escrowService->cancelAndRefundOrder($order->id, $seller->id);

        // Assert: Деньги (100% без удержания комиссии) вернулись покупателю, остаток лота восстановился
        $this->assertSame(OrderStatus::Cancelled, $cancelledOrder->status);
        $this->assertSame(3000.00, (float) $buyer->fresh()->wallet_balance);
        $this->assertSame(1, $lot->fresh()->stock_quantity);
    }

    public function test_dispute_can_be_opened_by_buyer_and_then_refunded(): void
    {
        // Arrange
        $seller = $this->createUser('Coach_Alex', balance: 0.00);
        $buyer = $this->createUser('Runner_Max', balance: 4000.00);
        $lot = $this->createLot($seller, price: 2500.00, stock: 2);

        $order = $this->escrowService->purchaseLot($buyer->id, $lot->id);

        // Act 1: Открываем арбитражный спор
        $disputedOrder = $this->escrowService->openDispute(
            orderId: $order->id,
            actorId: $buyer->id,
            reason: 'Тренер не выходит на связь 24 часа'
        );

        // Assert 1
        $this->assertSame(OrderStatus::Disputed, $disputedOrder->status);

        // Act 2: При открытом споре возврат средств разрешен
        $refundedOrder = $this->escrowService->cancelAndRefundOrder($order->id, $buyer->id);

        // Assert 2
        $this->assertSame(OrderStatus::Cancelled, $refundedOrder->status);
        $this->assertSame(4000.00, (float) $buyer->fresh()->wallet_balance);
    }

    public function test_purchase_lot_with_auto_delivery_immediately_marks_order_shipped_and_sends_secret_payload(): void
    {
        // Arrange
        $seller = $this->createUser('Coach_Auto', balance: 0.00);
        $buyer = $this->createUser('Runner_Fast', balance: 3000.00);

        $lot = Lot::create([
            'seller_id' => $seller->id,
            'title' => 'PDF-гайд по спортивному питанию',
            'product_type' => 'training_plan',
            'description' => 'Мгновенная выдача ссылки после оплаты',
            'auto_delivery_text' => 'https://sportpay.local/secret-pdf-link-777',
            'price' => 1200.00,
            'stock_quantity' => 5,
            'status' => LotStatus::Active,
        ]);

        // Act
        $order = $this->escrowService->purchaseLot($buyer->id, $lot->id);

        // Assert
        $this->assertSame(OrderStatus::Shipped, $order->status);
        $this->assertSame(1800.00, (float) $buyer->fresh()->wallet_balance);
        $this->assertSame(0.00, (float) $seller->fresh()->wallet_balance); // Деньги всё ещё в холде до подтверждения

        $this->assertDatabaseHas('messages', [
            'order_id' => $order->id,
            'user_id' => $seller->id,
            'body' => '⚡ АВТОВЫДАЧА ТОВАРА: https://sportpay.local/secret-pdf-link-777',
            'is_system' => true,
        ]);
    }

    private function createUser(string $username, float $balance): User
    {
        return User::create([
            'username' => $username . '_' . uniqid(),
            'email' => uniqid() . '@sportpay.local',
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
            'title' => 'План подготовки к марафону',
            'product_type' => 'training_plan',
            'description' => 'Индивидуальная программа на 12 недель',
            'price' => $price,
            'stock_quantity' => $stock,
            'status' => LotStatus::Active,
        ]);
    }
}