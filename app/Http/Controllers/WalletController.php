<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Requests\WalletAmountRequest;
use App\Services\WalletService;
use DomainException;
use Illuminate\Http\RedirectResponse;

class WalletController extends Controller
{
    public function __construct(
        private readonly WalletService $walletService
    ) {}

    public function deposit(WalletAmountRequest $request): RedirectResponse
    {
        try {
            $this->walletService->deposit($this->currentUser()->id, $request->amount());
            return redirect()->route('home')->with('success', 'Кошелек успешно пополнен!');
        } catch (DomainException $e) {
            return redirect()->route('home')->with('error', $e->getMessage());
        }
    }

    public function withdraw(WalletAmountRequest $request): RedirectResponse
    {
        try {
            $this->walletService->withdraw($this->currentUser()->id, $request->amount());
            return redirect()->route('home')->with('success', 'Средства успешно выведены!');
        } catch (DomainException $e) {
            return redirect()->route('home')->with('error', $e->getMessage());
        }
    }
}