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
        $user =$this->currentUser();

        $lots = Lot::with('seller')
            ->filter(
                type: $request->query('type'),
                search: $request->query('search')
            )
            ->sortBy($request->query('sort'))
            ->get();

        return view('marketplace.index', [
            'user' => $user,
            'lots' => $lots,
            'myPurchases' => Order::with(['lot', 'seller'])
                ->where('buyer_id', $user->id)
                ->latest()
                ->get(),
            'mySales' => Order::with(['lot', 'buyer'])
                ->where('seller_id', $user->id)
                ->latest()
                ->get(),
            'myTransactions' => WalletTransaction::where('user_id', $user->id)
                ->latest()
                ->limit(15)
                ->get(),
        ]);
    }

    public function switchUser(Request $request): RedirectResponse
    {
        $user = User::findOrFail($request->integer('user_id'));
        Auth::login($user);

        return redirect()->route('home')->with('success', "Вы переключились на аккаунт {$user->username}");
    }
}