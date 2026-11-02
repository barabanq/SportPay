<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Enums\OrderStatus;
use App\Models\Order;
use App\Models\User;
use App\Models\WalletTransaction;
use App\Services\WalletService;
use DomainException;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class WalletController extends Controller
{
    public function __construct(
        private readonly WalletService $walletService
    ) {}

    public function index(): View
    {
        $user = $this->currentUser();

        $transactions = WalletTransaction::where('user_id', $user->id)
            ->latest('id')
            ->limit(50)
            ->get();

        $activeStatuses = [
            OrderStatus::Paid,
            OrderStatus::Shipped,
            OrderStatus::Disputed,
        ];

        // Сумма, которую пользователь заморозил как покупатель
        $frozenAsBuyer = (float) Order::where('buyer_id', $user->id)
            ->whereIn('status', $activeStatuses)
            ->sum('total_price');

        // Ожидаемая выплата (минус 5% комиссии) по активным продажам
        $pendingAsSeller = round((float) Order::where('seller_id', $user->id)
            ->whereIn('status', $activeStatuses)
            ->sum('total_price') * 0.95, 2);

        return view('wallet.index', [
            'user' => $user,
            'allUsers' => User::orderBy('id')->get(),
            'transactions' => $transactions,
            'frozenAsBuyer' => $frozenAsBuyer,
            'pendingAsSeller' => $pendingAsSeller,
        ]);
    }

    public function deposit(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'amount' => ['required', 'numeric', 'min:100', 'max:500000'],
        ]);

        try {
            $this->walletService->deposit($this->currentUser()->id, (float) $data['amount']);

            return back()->with('success', 'Баланс успешно пополнен!');
        } catch (DomainException $e) {
            return back()->with('error', $e->getMessage());
        }
    }

    public function withdraw(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'amount' => ['required', 'numeric', 'min:100', 'max:500000'],
        ]);

        try {
            $this->walletService->withdraw($this->currentUser()->id, (float) $data['amount']);

            return back()->with('success', 'Заявка на вывод средств выполнена!');
        } catch (DomainException $e) {
            return back()->with('error', $e->getMessage());
        }
    }
}