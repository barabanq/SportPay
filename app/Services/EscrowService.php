<?php

declare(strict_types=1);

namespace App\Services;

use App\Enums\OrderStatus;
use App\Enums\TransactionType;
use App\Models\Lot;
use App\Models\Message;
use App\Models\Order;
use App\Models\Review;
use App\Models\User;
use DomainException;
use Illuminate\Support\Facades\DB;

class EscrowService
{
    private const PLATFORM_FEE_RATE = 0.05;

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

            $this->logSystemMessage(
                $order,
                $buyer->id,
                "Покупатель {$buyer->username} оплатил заказ. Средства ({$totalPrice} ₽) заморожены гарантом SportPay."
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

            $this->logSystemMessage(
                $order,
                $buyerId,
                "Покупатель подтвердил выполнение заказа. Продавцу выплачено {$sellerPayout} ₽ (комиссия биржи 5%)."
            );

            return $order;
        });
    }

    public function openDispute(int $orderId, int $actorId, string $reason): Order
    {
        return DB::transaction(function () use ($orderId, $actorId, $reason): Order {
            $order = Order::where('id', $orderId)->lockForUpdate()->firstOrFail();
            $actor = User::findOrFail($actorId);

            if (!$order->isParticipant($actor)) {
                throw new DomainException('Только участник сделки может открыть спор.');
            }

            if (!$order->status->isDisputable()) {
                throw new DomainException('По данному заказу спор уже открыт или сделка завершена.');
            }

            $order->update(['status' => OrderStatus::Disputed]);

            $this->logSystemMessage(
                $order,
                $actor->id,
                "⚠️ {$actor->username} открыл арбитражный спор! Причина: «{$reason}». Выплата заморожена, к сделке подключен арбитр SportPay."
            );

            return $order;
        });
    }

    public function resolveDispute(int $orderId, int $arbiterId, string $decision, ?string $comment = null): Order
    {
        return DB::transaction(function () use ($orderId, $arbiterId, $decision, $comment): Order {
            $arbiter = User::findOrFail($arbiterId);

            if (!$arbiter->isArbiter()) {
                throw new DomainException('Только официальный арбитр SportPay может выносить решение по спору.');
            }

            $order = Order::where('id', $orderId)->lockForUpdate()->firstOrFail();

            if ($order->status !== OrderStatus::Disputed) {
                throw new DomainException('Решение можно вынести только по заказу в статусе спора.');
            }

            $verdictNote = $comment ? " Комментарий арбитра: «{$comment}»." : '';

            if ($decision === 'refund_buyer') {
                $buyer = User::where('id', $order->buyer_id)->lockForUpdate()->firstOrFail();

                Lot::where('id', $order->lot_id)->increment('stock_quantity', $order->quantity);
                $order->update(['status' => OrderStatus::Cancelled]);

                $this->walletService->applyBalanceChange(
                    $buyer,
                    (float) $order->total_price,
                    TransactionType::Deposit,
                    $order->id
                );

                $this->logSystemMessage(
                    $order,
                    $arbiter->id,
                    "⚖️ Арбитр {$arbiter->username} решил спор в пользу покупателя. Средства ({$order->total_price} ₽) возвращены покупателю.{$verdictNote}"
                );

                return $order;
            }

            if ($decision === 'release_seller') {
                $seller = User::where('id', $order->seller_id)->lockForUpdate()->firstOrFail();
                $sellerPayout = round((float) $order->total_price * (1 - self::PLATFORM_FEE_RATE), 2);

                $order->update(['status' => OrderStatus::Completed]);

                $this->walletService->applyBalanceChange(
                    $seller,
                    $sellerPayout,
                    TransactionType::Payout,
                    $order->id
                );

                $this->logSystemMessage(
                    $order,
                    $arbiter->id,
                    "⚖️ Арбитр {$arbiter->username} решил спор в пользу продавца. Выплата ({$sellerPayout} ₽) переведена продавцу.{$verdictNote}"
                );

                return $order;
            }

            throw new DomainException('Неизвестный тип решения арбитража.');
        });
    }

    public function cancelAndRefundOrder(int $orderId, int $actorId): Order
    {
        return DB::transaction(function () use ($orderId, $actorId): Order {
            $order = Order::where('id', $orderId)->lockForUpdate()->firstOrFail();

            if ($order->buyer_id === $actorId && $order->status !== OrderStatus::Disputed) {
                throw new DomainException('Покупатель не может отменить оплаченный заказ напрямую. Откройте спор или попросите продавца сделать возврат.');
            }

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

            $this->logSystemMessage(
                $order,
                $actorId,
                "Заказ отменен продавцом. Средства ({$order->total_price} ₽) полностью возвращены покупателю."
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

    private function logSystemMessage(Order $order, int $userId, string $text): void
    {
        Message::create([
            'order_id' => $order->id,
            'user_id' => $userId,
            'body' => $text,
            'is_system' => true,
        ]);
    }
}