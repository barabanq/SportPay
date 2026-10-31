<?php

namespace App\Http\Controllers;

use App\Models\Lot;
use App\Models\Order;
use App\Models\Review;
use App\Models\User;
use App\Models\WalletTransaction;
use App\Services\EscrowService;
use App\Services\WalletService;
use DomainException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class MarketplaceController extends Controller
{
    public function index(Request $request)
    {
        if (!Auth::check()) {
            $defaultUser = User::where('email', 'buyer@sportpay.local')->first() ?? User::first();
            if ($defaultUser) {
                Auth::login($defaultUser);
            }
        }

        $user = Auth::user();
        $allUsers = User::orderBy('id')->get();

        $lotsQuery = Lot::with('seller')->where('stock_quantity', '>', 0)->latest();
        if ($request->filled('type')) {
            $lotsQuery->where('product_type', $request->string('type'));
        }
        $lots = $lotsQuery->get();

        $myPurchases = $user
            ? Order::with(['lot', 'seller', 'review'])->where('buyer_id', $user->id)->latest()->get()
            : collect();

        $mySales = $user
            ? Order::with(['lot', 'buyer', 'review'])->where('seller_id', $user->id)->latest()->get()
            : collect();

        $myTransactions = $user
            ? WalletTransaction::where('user_id', $user->id)->latest()->limit(15)->get()
            : collect();

        return view('marketplace.index', compact(
            'user',
            'allUsers',
            'lots',
            'myPurchases',
            'mySales',
            'myTransactions'
        ));
    }

    public function switchUser(Request $request)
    {
        $user = User::findOrFail($request->integer('user_id'));
        Auth::login($user);
        return back()->with('success', "Вы переключились на аккаунт: {$user->username}");
    }

    public function storeLot(Request $request)
    {
        $data = $request->validate([
            'title' => 'required|string|max:255',
            'product_type' => 'required|string|max:100',
            'description' => 'required|string',
            'price' => 'required|numeric|min:10',
            'stock_quantity' => 'required|integer|min:1',
        ]);

        Lot::create([
            'seller_id' => Auth::id(),
            'title' => $data['title'],
            'product_type' => $data['product_type'],
            'description' => $data['description'],
            'price' => $data['price'],
            'stock_quantity' => $data['stock_quantity'],
            'status' => 'active',
        ]);

        return back()->with('success', 'Лот успешно выставлен на витрину SportPay!');
    }

    public function purchase(Lot $lot, EscrowService $escrow)
    {
        try {
            $order = $escrow->purchaseLot(Auth::id(), $lot->id, 1);
            return back()->with('success', "Заказ #{$order->id} оплачен! Деньги заморожены в гаранте SportPay.");
        } catch (DomainException $e) {
            return back()->with('error', $e->getMessage());
        }
    }

    public function confirmOrder(Request $request, Order $order, EscrowService $escrow)
    {
        try {
            $escrow->confirmOrder(
                $order->id,
                Auth::id(),
                $request->filled('rating') ? $request->integer('rating') : 5,
                $request->input('comment', 'Сделка прошла отлично!')
            );
            return back()->with('success', "Заказ #{$order->id} подтвержден! Деньги переведены продавцу.");
        } catch (DomainException $e) {
            return back()->with('error', $e->getMessage());
        }
    }

    public function cancelOrder(Order $order, EscrowService $escrow)
    {
        try {
            $escrow->cancelAndRefundOrder($order->id);
            return back()->with('success', "Заказ #{$order->id} отменен. Средства возвращены покупателю.");
        } catch (DomainException $e) {
            return back()->with('error', $e->getMessage());
        }
    }

    public function deposit(Request $request, WalletService $wallet)
    {
        $request->validate(['amount' => 'required|numeric|min:100']);
        try {
            $wallet->deposit(Auth::id(), (float) $request->input('amount'));
            return back()->with('success', 'Кошелек успешно пополнен!');
        } catch (DomainException $e) {
            return back()->with('error', $e->getMessage());
        }
    }

    public function withdraw(Request $request, WalletService $wallet)
    {
        $request->validate(['amount' => 'required|numeric|min:100']);
        try {
            $wallet->withdraw(Auth::id(), (float) $request->input('amount'));
            return back()->with('success', 'Средства успешно выведены!');
        } catch (DomainException $e) {
            return back()->with('error', $e->getMessage());
        }
    }
}