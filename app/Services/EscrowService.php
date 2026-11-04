<?php

declare(strict_types=1);

namespace App\Services;

use App\Enums\LotStatus;
use App\Enums\OrderStatus;
use App\Enums\TransactionType;
use App\Models\Lot;
use App\Models\Message;
use App\Models\Order;
use App\Models\Review;
use App\Models\User;
use App\Models\WalletTransaction;
use DomainException;
use Illuminate\Support\Facades\DB;

class EscrowService
{
    public const PLATFORM_FEE_RATE = 0.05;

    public function purchaseLot(User|int $buyerId, Lot|int $lotId, int $quantity = 1): Order
    {
        if ($quantity < 1) {
            throw new DomainException('Количество должно быть не менее 1 шт.');
        }

        $resolvedBuyerId = $buyerId instanceof User ? $buyerId->id : $buyerId;
        $resolvedLotId = $lotId instanceof Lot ? $lotId->id : $lotId;

        return DB::transaction(function () use ($resolvedBuyerId, $resolvedLotId, $quantity): Order {
            /** @var Lot $lockedLot */
            $lockedLot = Lot::whereKey($resolvedLotId)->lockForUpdate()->firstOrFail();
            /** @var User $lockedBuyer */
            $lockedBuyer = User::whereKey($resolvedBuyerId)->lockForUpdate()->firstOrFail();

            if ($lockedBuyer->id === $lockedLot->seller_id) {
                throw new DomainException('Вы не можете купить собственный лот.');
            }

            if (! $lockedBuyer->status->canTrade()) {
                throw new DomainException('Ваш аккаунт не может совершать покупки.');
            }

            if ($lockedLot->status !== LotStatus::Active || $lockedLot->stock_quantity < $quantity) {
                throw new DomainException("Недостаточно товара в наличии (доступно: {$lockedLot->stock_quantity} шт.).");
            }

            $unitPrice = (float) $lockedLot->price;
            $totalPrice = round($unitPrice * $quantity, 2);

            if ((float) $lockedBuyer->wallet_balance < $totalPrice) {
                throw new DomainException('Недостаточно средств на балансе кошелька.');
            }

            $newBalance = round((float) $lockedBuyer->wallet_balance - $totalPrice, 2);
            $lockedBuyer->update(['wallet_balance' => $newBalance]);

            $newStock = $lockedLot->stock_quantity - $quantity;
            $lockedLot->update([
                'stock_quantity' => $newStock,
                'status' => $newStock === 0 ? LotStatus::Sold : LotStatus::Active,
            ]);

            $hasAutoDelivery = $lockedLot->hasAutoDelivery();

            /** @var Order $order */
            $order = Order::create([
                'lot_id' => $lockedLot->id,
                'buyer_id' => $lockedBuyer->id,
                'seller_id' => $lockedLot->seller_id,
                'quantity' => $quantity,
                'unit_price' => $unitPrice,
                'total_price' => $totalPrice,
                'status' => $hasAutoDelivery ? OrderStatus::Shipped : OrderStatus::Paid,
            ]);

            WalletTransaction::create([
                'user_id' => $lockedBuyer->id,
                'order_id' => $order->id,
                'type' => TransactionType::Purchase,
                'amount' => -$totalPrice,
                'balance_after' => $newBalance,
            ]);

            Message::create([
                'order_id' => $order->id,
                'user_id' => $lockedBuyer->id,
                'body' => "Сделка #{$order->id} оплачена ({$quantity} шт., {$totalPrice} ₽ заморожено в эскроу SportPay).",
                'is_system' => true,
            ]);

            if ($hasAutoDelivery) {
                Message::create([
                    'order_id' => $order->id,
                    'user_id' => $lockedLot->seller_id,
                    'body' => "⚡ АВТОВЫДАЧА SportPay: {$lockedLot->auto_delivery_text}",
                    'is_system' => true,
                ]);
            }

            return $order;
        });
    }

    public function confirmOrder(
        Order|int $orderId,
        User|int $actorId,
        int $rating = 5,
        ?string $comment = null
    ): Order {
        $resolvedOrderId = $orderId instanceof Order ? $orderId->id : $orderId;
        $resolvedActorId = $actorId instanceof User ? $actorId->id : $actorId;

        return DB::transaction(function () use ($resolvedOrderId, $resolvedActorId, $rating, $comment): Order {
            /** @var Order $lockedOrder */
            $lockedOrder = Order::whereKey($resolvedOrderId)->lockForUpdate()->firstOrFail();
            /** @var User $actor */
            $actor = User::whereKey($resolvedActorId)->firstOrFail();

            if ($lockedOrder->buyer_id !== $actor->id && ! $actor->isArbiter()) {
                throw new DomainException('Подтвердить выполнение заказа может только покупатель или арбитр.');
            }

            if (! $lockedOrder->status->isConfirmable()) {
                throw new DomainException('Заказ уже завершен или отменен.');
            }

            /** @var User $seller */
            $seller = User::whereKey($lockedOrder->seller_id)->lockForUpdate()->firstOrFail();

            $totalPrice = (float) $lockedOrder->total_price;
            $fee = round($totalPrice * self::PLATFORM_FEE_RATE, 2);
            $payout = round($totalPrice - $fee, 2);

            $newSellerBalance = round((float) $seller->wallet_balance + $payout, 2);
            $seller->update(['wallet_balance' => $newSellerBalance]);

            $lockedOrder->update(['status' => OrderStatus::Completed]);

            WalletTransaction::create([
                'user_id' => $seller->id,
                'order_id' => $lockedOrder->id,
                'type' => TransactionType::Payout,
                'amount' => $payout,
                'balance_after' => $newSellerBalance,
            ]);

            if ($lockedOrder->buyer_id === $actor->id) {
                $clampedRating = max(1, min(5, $rating));

                Review::updateOrCreate(
                    ['order_id' => $lockedOrder->id],
                    [
                        'lot_id' => $lockedOrder->lot_id,
                        'author_id' => $actor->id,
                        'seller_id' => $seller->id,
                        'rating' => $clampedRating,
                        'comment' => filled($comment) ? trim((string) $comment) : 'Сделка прошла отлично!',
                    ]
                );

                $avgRating = Review::where('seller_id', $seller->id)->avg('rating');
                if ($avgRating !== null) {
                    $seller->update(['rating' => round((float) $avgRating, 2)]);
                }
            }

            Message::create([
                'order_id' => $lockedOrder->id,
                'user_id' => $actor->id,
                'body' => "Заказ #{$lockedOrder->id} подтвержден! Продавцу перечислено {$payout} ₽ (комиссия биржи 5%: {$fee} ₽).",
                'is_system' => true,
            ]);

            return $lockedOrder;
        });
    }

    public function openDispute(Order|int $orderId, User|int $actorId, string $reason = 'Открыт спор'): Order
    {
        $resolvedOrderId = $orderId instanceof Order ? $orderId->id : $orderId;
        $resolvedActorId = $actorId instanceof User ? $actorId->id : $actorId;

        return DB::transaction(function () use ($resolvedOrderId, $resolvedActorId, $reason): Order {
            /** @var Order $lockedOrder */
            $lockedOrder = Order::whereKey($resolvedOrderId)->lockForUpdate()->firstOrFail();
            /** @var User $actor */
            $actor = User::whereKey($resolvedActorId)->firstOrFail();

            if ($lockedOrder->buyer_id !== $actor->id && $lockedOrder->seller_id !== $actor->id) {
                throw new DomainException('Только участники сделки могут открыть спор.');
            }

            if (! $lockedOrder->status->isDisputable()) {
                throw new DomainException('По данному заказу нельзя открыть спор.');
            }

            $lockedOrder->update(['status' => OrderStatus::Disputed]);

            Message::create([
                'order_id' => $lockedOrder->id,
                'user_id' => $actor->id,
                'body' => "⚠️ Открыт арбитражный спор пользователем {$actor->username}. Причина: {$reason}",
                'is_system' => true,
            ]);

            return $lockedOrder;
        });
    }

    public function resolveDispute(
        Order|int $orderId,
        User|int $arbiterId,
        string $decision,
        ?string $comment = null
    ): Order {
        $resolvedOrderId = $orderId instanceof Order ? $orderId->id : $orderId;
        $resolvedArbiterId = $arbiterId instanceof User ? $arbiterId->id : $arbiterId;

        /** @var Order $order */
        $order = Order::findOrFail($resolvedOrderId);
        /** @var User $arbiter */
        $arbiter = User::findOrFail($resolvedArbiterId);

        if (! $arbiter->isArbiter()) {
            throw new DomainException('Только официальный арбитр может выносить решение по спору.');
        }

        if ($order->status !== OrderStatus::Disputed) {
            throw new DomainException('Сделка не находится в статусе спора.');
        }

        $note = filled($comment) ? " Комментарий арбитра: {$comment}" : '';

        if ($decision === 'refund_buyer') {
            $resolved = $this->cancelOrder($order, $arbiter);
            Message::create([
                'order_id' => $resolved->id,
                'user_id' => $arbiter->id,
                'body' => "⚖️ Решение Арбитра: возврат 100% средств покупателю.{$note}",
                'is_system' => true,
            ]);

            return $resolved;
        }

        if ($decision === 'release_seller') {
            $resolved = $this->confirmOrder($order, $arbiter);
            Message::create([
                'order_id' => $resolved->id,
                'user_id' => $arbiter->id,
                'body' => "⚖️ Решение Арбитра: выплата средств продавцу.{$note}",
                'is_system' => true,
            ]);

            return $resolved;
        }

        throw new DomainException('Неизвестное решение арбитра.');
    }

    public function cancelOrder(Order|int $orderId, User|int $actorId): Order
    {
        $resolvedOrderId = $orderId instanceof Order ? $orderId->id : $orderId;
        $resolvedActorId = $actorId instanceof User ? $actorId->id : $actorId;

        return DB::transaction(function () use ($resolvedOrderId, $resolvedActorId): Order {
            /** @var Order $lockedOrder */
            $lockedOrder = Order::whereKey($resolvedOrderId)->lockForUpdate()->firstOrFail();
            /** @var User $actor */
            $actor = User::whereKey($resolvedActorId)->firstOrFail();

            if ($lockedOrder->seller_id !== $actor->id && ! $actor->isArbiter()) {
                throw new DomainException('Отменить оплаченный заказ и вернуть средства может только продавец или арбитр.');
            }

            if (! $lockedOrder->status->isConfirmable()) {
                throw new DomainException('Заказ уже завершен или отменен.');
            }

            /** @var User $buyer */
            $buyer = User::whereKey($lockedOrder->buyer_id)->lockForUpdate()->firstOrFail();
            /** @var Lot|null $lot */
            $lot = Lot::whereKey($lockedOrder->lot_id)->lockForUpdate()->first();

            $refundAmount = (float) $lockedOrder->total_price;
            $newBuyerBalance = round((float) $buyer->wallet_balance + $refundAmount, 2);
            $buyer->update(['wallet_balance' => $newBuyerBalance]);

            if ($lot !== null) {
                $restoredStock = $lot->stock_quantity + $lockedOrder->quantity;
                $lot->update([
                    'stock_quantity' => $restoredStock,
                    'status' => LotStatus::Active,
                ]);
            }

            $lockedOrder->update(['status' => OrderStatus::Cancelled]);

            WalletTransaction::create([
                'user_id' => $buyer->id,
                'order_id' => $lockedOrder->id,
                'type' => TransactionType::Deposit,
                'amount' => $refundAmount,
                'balance_after' => $newBuyerBalance,
            ]);

            Message::create([
                'order_id' => $lockedOrder->id,
                'user_id' => $actor->id,
                'body' => "Заказ #{$lockedOrder->id} отменен. Покупателю возвращено {$refundAmount} ₽, товар ({$lockedOrder->quantity} шт.) возвращен на склад.",
                'is_system' => true,
            ]);

            return $lockedOrder;
        });
    }
}
