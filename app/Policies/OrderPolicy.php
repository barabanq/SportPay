<?php

declare(strict_types=1);

namespace App\Policies;

use App\Enums\OrderStatus;
use App\Models\Order;
use App\Models\User;
use Illuminate\Auth\Access\Response;

class OrderPolicy
{
    /**
     * Просматривать сделку и читать чат могут покупатель, продавец и арбитр.
     */
    public function view(User $user, Order $order): Response
    {
        return $order->isParticipant($user)
            ? Response::allow()
            : Response::deny('У вас нет доступа к этой сделке.');
    }

    /**
     * Писать в чат сделки могут только участники (покупатель, продавец и арбитр).
     */
    public function sendMessage(User $user, Order $order): Response
    {
        return $order->isParticipant($user)
            ? Response::allow()
            : Response::deny('У вас нет доступа к чату этой сделки.');
    }

    /**
     * Подтвердить выполнение заказа может только покупатель.
     */
    public function confirm(User $user, Order $order): Response
    {
        return $order->buyer_id === $user->id
            ? Response::allow()
            : Response::deny('Только покупатель может подтвердить выполнение заказа.');
    }

    /**
     * Добровольно отменить заказ без спора может только продавец (или арбитр при споре).
     */
    public function cancel(User $user, Order $order): Response
    {
        if ($order->seller_id === $user->id || $user->isArbiter()) {
            return Response::allow();
        }

        if ($order->buyer_id === $user->id && $order->status === OrderStatus::Disputed) {
            return Response::allow();
        }

        return Response::deny('Покупатель не может отменить оплаченный заказ напрямую. Откройте спор.');
    }

    /**
     * Открыть спор может покупатель или продавец.
     */
    public function dispute(User $user, Order $order): Response
    {
        return ($order->buyer_id === $user->id || $order->seller_id === $user->id)
            ? Response::allow()
            : Response::deny('Только участник сделки может открыть спор.');
    }

    /**
     * Вынести решение по спору может только официальный арбитр.
     */
    public function resolve(User $user, Order $order): Response
    {
        return $user->isArbiter()
            ? Response::allow()
            : Response::deny('Только официальный арбитр SportPay может выносить решение по спору.');
    }
}