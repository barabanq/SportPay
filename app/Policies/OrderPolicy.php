<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\Order;
use App\Models\User;

class OrderPolicy
{
    public function view(User $user, Order $order): bool
    {
        return $user->id === $order->buyer_id
            || $user->id === $order->seller_id
            || $user->isArbiter();
    }

    public function message(User $user, Order $order): bool
    {
        return $this->view($user, $order);
    }

    public function confirm(User $user, Order $order): bool
    {
        return ($user->id === $order->buyer_id || $user->isArbiter())
            && $order->status->isConfirmable();
    }

    public function cancel(User $user, Order $order): bool
    {
        return ($user->id === $order->seller_id || $user->isArbiter())
            && $order->status->isConfirmable();
    }

    public function dispute(User $user, Order $order): bool
    {
        return ($user->id === $order->buyer_id || $user->id === $order->seller_id)
            && $order->status->isDisputable();
    }

    public function resolve(User $user, Order $order): bool
    {
        return $user->isArbiter() && $order->status->isConfirmable();
    }
}
