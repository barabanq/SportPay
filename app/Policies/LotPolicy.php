<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\Lot;
use App\Models\User;
use Illuminate\Auth\Access\Response;

class LotPolicy
{
    /**
     * Редактировать и обновлять лот может только его владелец (продавец).
     */
    public function update(User $user, Lot $lot): Response
    {
        return $lot->seller_id === $user->id
            ? Response::allow()
            : Response::deny('Вы можете редактировать только свои лоты.');
    }

    /**
     * Удалять лот может только его владелец или арбитр платформы.
     */
    public function delete(User $user, Lot $lot): Response
    {
        return ($lot->seller_id === $user->id || $user->isArbiter())
            ? Response::allow()
            : Response::deny('Вы можете удалять только свои лоты.');
    }

    /**
     * Покупать лот может любой пользователь, кроме самого продавца.
     */
    public function purchase(User $user, Lot $lot): Response
    {
        return $lot->seller_id !== $user->id
            ? Response::allow()
            : Response::deny('Нельзя купить собственный лот.');
    }
}