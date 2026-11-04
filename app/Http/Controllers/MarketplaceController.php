<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Enums\LotStatus;
use App\Models\Lot;
use App\Models\Order;
use App\Models\User;
use App\Models\WalletTransaction;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Auth;

class MarketplaceController extends Controller
{
    public function index(Request $request): View
    {
        $user = $this->currentUser();

        $minPrice = $request->filled('min_price') ? (float) $request->input('min_price') : null;
        $maxPrice = $request->filled('max_price') ? (float) $request->input('max_price') : null;
        $sellerFilter = $request->query('seller');
        $onlineOnly = $request->boolean('online_only');

        $lots = Lot::with('seller')
            ->filter(
                type: $request->query('type'),
                search: $request->query('search'),
                minPrice: $minPrice,
                maxPrice: $maxPrice,
                seller: $sellerFilter,
                onlineOnly: $onlineOnly
            )
            ->sortBy($request->query('sort'))
            ->paginate(20)
            ->withQueryString();

        $categoryCounts = Lot::query()
            ->where('stock_quantity', '>', 0)
            ->where('status', LotStatus::Active)
            ->selectRaw('product_type, count(*) as total')
            ->groupBy('product_type')
            ->pluck('total', 'product_type')
            ->all();

        $sellers = User::whereHas('lots', fn ($q) => $q->where('stock_quantity', '>', 0))
            ->orderBy('username')
            ->get(['id', 'username', 'rating', 'last_seen_at']);

        return view('marketplace.index', [
            'user' => $user,
            'lots' => $lots,
            'categoryCounts' => $categoryCounts,
            'sellers' => $sellers,
            'myPurchases' => Order::with(['lot', 'seller'])
                ->where('buyer_id', $user->id)
                ->latest()
                ->get(),
            'mySales' => Order::with(['lot', 'buyer'])
                ->where('seller_id', $user->id)
                ->latest()
                ->get(),
            'myTransactions' => WalletTransaction::with('order.lot')
                ->where('user_id', $user->id)
                ->latest('id')
                ->limit(15)
                ->get(),
        ]);
    }

    public function switchUser(Request $request): RedirectResponse
    {
        $user = User::findOrFail($request->integer('user_id'));
        $user->update(['last_seen_at' => now()]);
        Auth::login($user);

        return redirect()->route('home')->with('success', "Аккаунт: {$user->username}");
    }

    public function switchLocale(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'locale' => ['required', 'string', 'in:ru,en'],
        ]);

        session(['locale' => $data['locale']]);
        App::setLocale($data['locale']);

        return back();
    }
}
