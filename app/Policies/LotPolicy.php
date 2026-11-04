<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\Lot;
use App\Models\User;

class LotPolicy
{
    public function create(User $user): bool
    {
        return $user->status->canTrade();
    }

    public function update(User $user, Lot $lot): bool
    {
        return ($user->id === $lot->seller_id || $user->isArbiter()) && $user->status->canTrade();
    }

    public function delete(User $user, Lot $lot): bool
    {
        return $user->id === $lot->seller_id || $user->isArbiter();
    }

    public function purchase(User $user, Lot $lot): bool
    {
        return $user->id !== $lot->seller_id
            && $user->status->canTrade()
            && $lot->stock_quantity > 0;
    }
}
