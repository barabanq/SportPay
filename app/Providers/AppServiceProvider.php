<?php

declare(strict_types=1);

namespace App\Providers;

use App\Enums\OrderStatus;
use App\Enums\UserStatus;
use App\Models\Message;
use App\Models\Order;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void {}

    public function boot(): void
    {
        View::composer('*', function ($view): void {
            if (!Schema::hasTable('users')) {
                return;
            }

            User::firstOrCreate(
                ['email' => 'arbiter@sportpay.local'],
                [
                    'username' => 'Arbiter_Judge',
                    'password' => 'secret123',
                    'wallet_balance' => 0.00,
                    'rating' => 5.00,
                    'status' => UserStatus::Active,
                ]
            );

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

            $disputedOrdersCount = Schema::hasTable('orders')
                ? Order::where('status', OrderStatus::Disputed)->count()
                : 0;

            $view->with([
                'user' => $user,
                'allUsers' => User::orderBy('id')->get(),
                'unreadMessagesCount' => $unreadMessagesCount,
                'disputedOrdersCount' => $disputedOrdersCount,
            ]);
        });
    }
}