<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Support\Facades\Auth;

abstract class Controller
{
    protected function currentUser(): User
    {
        if (!Auth::check()) {
            $user = User::where('email', 'buyer@sportpay.local')->first() ?? User::firstOrFail();
            Auth::login($user);
        }

        /** @var User $authenticated */
        $authenticated = Auth::user();

        return $authenticated;
    }
}