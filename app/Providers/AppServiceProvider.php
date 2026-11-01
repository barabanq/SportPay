<?php

declare(strict_types=1);

namespace App\Providers;

use App\Models\Message;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void {}

    public function boot(): void
    {
        View::composer('layouts.app', function ($view): void {
            if (!Auth::check()) {
                $defaultUser = User::where('email', 'buyer@sportpay.local')->first() ?? User::first();
                if ($defaultUser) {
                    Auth::login($defaultUser);
                }
            }

            /** @var User|null $user */
            $user = Auth::user();

            $unreadMessagesCount = 0;
            if ($user !== null) {
                $unreadMessagesCount = Message::query()
                    ->whereNull('read_at')
                    ->where('user_id', '!=', $user->id)
                    ->whereHas('order', function ($q) use ($user): void {
                        $q->where('buyer_id', $user->id)
                          ->orWhere('seller_id', $user->id);
                    })
                    ->count();
            }

            $view->with([
                'user' => $user,
                'allUsers' => User::orderBy('id')->get(),
                'unreadMessagesCount' => $unreadMessagesCount,
            ]);
        });
    }
}