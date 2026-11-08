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

        $type = $request->query('type');
        $search = $request->query('search');
        $sort = $request->query('sort');
        $minPrice = $request->filled('min_price') ? (float) $request->query('min_price') : null;
        $maxPrice = $request->filled('max_price') ? (float) $request->query('max_price') : null;
        $seller = $request->query('seller');
        $onlineOnly = $request->boolean('online_only');

        $userLat = $request->filled('lat') && is_numeric($request->query('lat'))
            ? (float) $request->query('lat')
            : null;
        $userLng = $request->filled('lng') && is_numeric($request->query('lng'))
            ? (float) $request->query('lng')
            : null;
        $radiusKm = $request->filled('radius_km') && is_numeric($request->query('radius_km'))
            ? (float) $request->query('radius_km')
            : null;

        $lots = Lot::with('seller')
            ->filter(
                type: $type,
                search: $search,
                minPrice: $minPrice,
                maxPrice: $maxPrice,
                seller: $seller,
                onlineOnly: $onlineOnly,
                userLat: $userLat,
                userLng: $userLng,
                radiusKm: $radiusKm
            )
            ->sortBy($sort, $userLat, $userLng)
            ->paginate(20)
            ->withQueryString();

        $categoryCounts = Lot::query()
            ->where('stock_quantity', '>', 0)
            ->where('status', LotStatus::Active)
            ->selectRaw('product_type, count(*) as total')
            ->groupBy('product_type')
            ->pluck('total', 'product_type')
            ->all();

        $sellers = User::whereHas('lots', function ($q): void {
            $q->where('stock_quantity', '>', 0)->where('status', LotStatus::Active);
        })->orderByDesc('rating')->get();

        $myTransactions = WalletTransaction::with('order.lot')
            ->where('user_id', $user->id)
            ->latest('id')
            ->limit(10)
            ->get();

        $myPurchases = Order::with(['lot', 'seller'])
            ->where('buyer_id', $user->id)
            ->latest('id')
            ->limit(10)
            ->get();

        $mySales = Order::with(['lot', 'buyer'])
            ->where('seller_id', $user->id)
            ->latest('id')
            ->limit(10)
            ->get();

        return view('marketplace.index', [
            'user' => $user,
            'allUsers' => User::orderBy('id')->get(),
            'lots' => $lots,
            'categoryCounts' => $categoryCounts,
            'sellers' => $sellers,
            'myTransactions' => $myTransactions,
            'myPurchases' => $myPurchases,
            'mySales' => $mySales,
            'userLat' => $userLat,
            'userLng' => $userLng,
            'radiusKm' => $radiusKm,
        ]);
    }

    public function switchUser(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'user_id' => ['required', 'integer', 'exists:users,id'],
        ]);

        $targetUser = User::findOrFail((int) $data['user_id']);
        Auth::login($targetUser);
        $request->session()->forget('logged_out');

        return back()->with('success', "Вы переключились на аккаунт {$targetUser->username}");
    }

    public function switchLocale(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'locale' => ['required', 'string', 'in:ru,en'],
        ]);

        $request->session()->put('locale', $data['locale']);
        App::setLocale($data['locale']);

        return back();
    }
}