<?php

namespace App\Services;

use App\Models\Lot;
use App\Models\Order;
use App\Models\Review;
use App\Models\User;
use App\Models\WalletTransaction;
use DomainException;
use Illuminate\Support\Facades\DB;

class EscrowService
{
    public function purchaseLot(int $buyerId, int $lotId, int $quantity = 1): Order
    {
        return DB::transaction(function () use ($buyerId, $lotId, $quantity) {
            $lot = Lot::where('id', $lotId)->lockForUpdate()->firstOrFail();
            $buyer = User::where('id', $buyerId)->lockForUpdate()->firstOrFail();

            if ($lot->seller_id === $buyer->id) {
                throw new DomainException('Нельзя купить собственный лот.');
            }

            if ($lot->stock_quantity < $quantity) {
                throw new DomainException('Товара нет в нужном количестве.');
            }

            $totalPrice = round($lot->price * $quantity, 2);

            if ($buyer->wallet_balance < $totalPrice) {
                throw new DomainException('Недостаточно средств на кошельке. Пополните баланс.');
            }

            $lot->decrement('stock_quantity', $quantity);

            $buyer->wallet_balance -= $totalPrice;
            $buyer->save();

            $order = Order::create([
                'lot_id' => $lot->id,
                'buyer_id' => $buyer->id,
                'seller_id' => $lot->seller_id,
                'quantity' => $quantity,
                'unit_price' => $lot->price,
                'total_price' => $totalPrice,
                'status' => 'paid',
            ]);

            WalletTransaction::create([
                'user_id' => $buyer->id,
                'order_id' => $order->id,
                'amount' => -$totalPrice,
                'type' => 'purchase',
                'balance_after' => $buyer->wallet_balance,
                'status' => 'success',
            ]);

            return $order;
        });
    }

    public function confirmOrder(int $orderId, int $buyerId, ?int $rating = null, ?string $comment = null): Order
    {
        return DB::transaction(function () use ($orderId, $buyerId, $rating, $comment) {
            $order = Order::where('id', $orderId)->lockForUpdate()->firstOrFail();

            if ($order->buyer_id !== $buyerId) {
                throw new DomainException('Только покупатель может подтвердить выполнение заказа.');
            }

            $statusValue = is_object($order->status) ? $order->status->value : $order->status;
            if (!in_array($statusValue, ['paid', 'shipped'], true)) {
                throw new DomainException('Заказ уже закрыт или отменен.');
            }

            $seller = User::where('id', $order->seller_id)->lockForUpdate()->firstOrFail();
            $seller->wallet_balance += $order->total_price;
            $seller->save();

            $order->update(['status' => 'completed']);

            WalletTransaction::create([
                'user_id' => $seller->id,
                'order_id' => $order->id,
                'amount' => $order->total_price,
                'type' => 'payout',
                'balance_after' => $seller->wallet_balance,
                'status' => 'success',
            ]);

            if ($rating !== null) {
                Review::create([
                    'order_id' => $order->id,
                    'author_id' => $buyerId,
                    'seller_id' => $seller->id,
                    'lot_id' => $order->lot_id,
                    'rating' => $rating,
                    'comment' => $comment,
                ]);

                $avgRating = Review::where('seller_id', $seller->id)->avg('rating') ?? 5.00;
                $seller->update(['rating' => round($avgRating, 2)]);
            }

            return $order;
        });
    }

    public function cancelAndRefundOrder(int $orderId): Order
    {
        return DB::transaction(function () use ($orderId) {
            $order = Order::where('id', $orderId)->lockForUpdate()->firstOrFail();

            $statusValue = is_object($order->status) ? $order->status->value : $order->status;
            if (!in_array($statusValue, ['paid', 'shipped'], true)) {
                throw new DomainException('Невозможно вернуть средства по завершенному заказу.');
            }

            $buyer = User::where('id', $order->buyer_id)->lockForUpdate()->firstOrFail();
            $buyer->wallet_balance += $order->total_price;
            $buyer->save();

            Lot::where('id', $order->lot_id)->increment('stock_quantity', $order->quantity);
            $order->update(['status' => 'cancelled']);

            WalletTransaction::create([
                'user_id' => $buyer->id,
                'order_id' => $order->id,
                'amount' => $order->total_price,
                'type' => 'deposit',
                'balance_after' => $buyer->wallet_balance,
                'status' => 'success',
            ]);

            return $order;
        });
    }
}