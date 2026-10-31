<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\Lot;
use App\Models\Order;
use App\Models\User;
use App\Models\WalletTransaction;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class MarketplaceController extends Controller
{
    public function index(Request $request): View
    {
        $user = $this->resolveCurrentUser();

        $lots = Lot::with('seller')
            ->where('stock_quantity', '>', 0)
            ->when($request->filled('type'), fn ($q) => $q->where('product_type', $request->string('type')))
            ->latest()
            ->get();

        return view('marketplace.index', [
            'user' => $user,
            'allUsers' => User::orderBy('id')->get(),
            'lots' => $lots,
            'myPurchases' => $user ? Order::with(['lot', 'seller', 'review'])->where('buyer_id', $user->id)->latest()->get() : collect(),
            'mySales' => $user ? Order::with(['lot', 'buyer', 'review'])->where('seller_id', $user->id)->latest()->get() : collect(),
            'myTransactions' => $user ? WalletTransaction::where('user_id', $user->id)->latest()->limit(15)->get() : collect(),
        ]);
    }

    public function switchUser(Request $request): RedirectResponse
    {
        $user = User::findOrFail($request->integer('user_id'));
        Auth::login($user);

        return back()->with('success', "Вы переключились на аккаунт: {$user->username}");
    }

    private function resolveCurrentUser(): ?User
    {
        if (!Auth::check()) {
            $defaultUser = User::where('email', 'buyer@sportpay.local')->first() ?? User::first();
            if ($defaultUser) {
                Auth::login($defaultUser);
            }
        }

        return Auth::user();
    }
}