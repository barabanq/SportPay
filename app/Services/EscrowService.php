<?php

declare(strict_types=1);

namespace App\Services;

use App\Enums\OrderStatus;
use App\Enums\TransactionType;
use App\Models\Lot;
use App\Models\Order;
use App\Models\Review;
use App\Models\User;
use DomainException;
use Illuminate\Support\Facades\DB;

class EscrowService
{
    private const PLATFORM_FEE_RATE = 0.05; // Комиссия биржи SportPay 5%

    public function __construct(
        private readonly WalletService $walletService
    ) {}

    public function purchaseLot(int $buyerId, int $lotId, int $quantity = 1): Order
    {
        return DB::transaction(function () use ($buyerId, $lotId, $quantity): Order {
            $lot = Lot::where('id', $lotId)->lockForUpdate()->firstOrFail();
            $buyer = User::where('id', $buyerId)->lockForUpdate()->firstOrFail();

            if ($lot->seller_id === $buyer->id) {
                throw new DomainException('Нельзя купить собственный лот.');
            }

            if ($lot->stock_quantity < $quantity) {
                throw new DomainException('Товара нет в нужном количестве.');
            }

            $totalPrice = round((float) $lot->price * $quantity, 2);

            $lot->decrement('stock_quantity', $quantity);

            $order = Order::create([
                'lot_id' => $lot->id,
                'buyer_id' => $buyer->id,
                'seller_id' => $lot->seller_id,
                'quantity' => $quantity,
                'unit_price' => $lot->price,
                'total_price' => $totalPrice,
                'status' => OrderStatus::Paid,
            ]);

            $this->walletService->applyBalanceChange(
                $buyer,
                -$totalPrice,
                TransactionType::Purchase,
                $order->id
            );

            return $order;
        });
    }

    public function confirmOrder(int $orderId, int $buyerId, ?int $rating = null, ?string $comment = null): Order
    {
        return DB::transaction(function () use ($orderId, $buyerId, $rating, $comment): Order {
            $order = Order::where('id', $orderId)->lockForUpdate()->firstOrFail();

            if ($order->buyer_id !== $buyerId) {
                throw new DomainException('Только покупатель может подтвердить выполнение заказа.');
            }

            if (!$order->status->isConfirmable()) {
                throw new DomainException('Заказ уже закрыт или отменен.');
            }

            $seller = User::where('id', $order->seller_id)->lockForUpdate()->firstOrFail();

            // Выплата продавцу за вычетом 5% комиссии платформы
            $sellerPayout = round((float) $order->total_price * (1 - self::PLATFORM_FEE_RATE), 2);

            $order->update(['status' => OrderStatus::Completed]);

            $this->walletService->applyBalanceChange(
                $seller,
                $sellerPayout,
                TransactionType::Payout,
                $order->id
            );

            if ($rating !== null) {
                $this->recordReview($order, $buyerId, $seller, $rating, $comment);
            }

            return $order;
        });
    }

    public function cancelAndRefundOrder(int $orderId): Order
    {
        return DB::transaction(function () use ($orderId): Order {
            $order = Order::where('id', $orderId)->lockForUpdate()->firstOrFail();

            if (!$order->status->isConfirmable()) {
                throw new DomainException('Невозможно вернуть средства по завершенному заказу.');
            }

            $buyer = User::where('id', $order->buyer_id)->lockForUpdate()->firstOrFail();

            Lot::where('id', $order->lot_id)->increment('stock_quantity', $order->quantity);
            $order->update(['status' => OrderStatus::Cancelled]);

            $this->walletService->applyBalanceChange(
                $buyer,
                (float) $order->total_price,
                TransactionType::Deposit,
                $order->id
            );

            return $order;
        });
    }

    private function recordReview(Order $order, int $buyerId, User $seller, int $rating, ?string $comment): void
    {
        Review::create([
            'order_id' => $order->id,
            'author_id' => $buyerId,
            'seller_id' => $seller->id,
            'lot_id' => $order->lot_id,
            'rating' => $rating,
            'comment' => $comment ?: 'Сделка прошла отлично!',
        ]);

        $avgRating = Review::where('seller_id', $seller->id)->avg('rating') ?? 5.00;
        $seller->update(['rating' => round((float) $avgRating, 2)]);
    }
}