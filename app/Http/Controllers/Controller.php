<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;

abstract class Controller
{
    protected function currentUser(): User
    {
        /** @var User|null $user */
        $user = Auth::user();

        if ($user === null) {
            $user = User::where('email', 'buyer@sportpay.local')->first() ?? User::firstOrFail();
            Auth::login($user);
        }

        return $user;
    }

    protected function authorizeAction(string $ability, mixed $arguments): void
    {
        Gate::forUser($this->currentUser())->authorize($ability, $arguments);
    }
}