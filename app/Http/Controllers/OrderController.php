<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Requests\ConfirmOrderRequest;
use App\Models\Lot;
use App\Models\Order;
use App\Services\EscrowService;
use DomainException;
use Illuminate\Http\RedirectResponse;

class OrderController extends Controller
{
    public function __construct(
        private readonly EscrowService $escrowService
    ) {}

    public function purchase(Lot $lot): RedirectResponse
    {
        try {
            $order = $this->escrowService->purchaseLot($this->currentUser()->id, $lot->id);
            return redirect()->route('home')->with('success', "Заказ #{$order->id} оплачен! Деньги зарезервированы гарантом.");
        } catch (DomainException $e) {
            return redirect()->route('home')->with('error', $e->getMessage());
        }
    }

    public function confirm(ConfirmOrderRequest $request, Order $order): RedirectResponse
    {
        try {
            $this->escrowService->confirmOrder(
                $order->id,
                $this->currentUser()->id,
                $request->integer('rating', 5),
                $request->input('comment')
            );
            return redirect()->route('home')->with('success', "Заказ #{$order->id} подтвержден! Выплата (за вычетом 5% комиссии) отправлена продавцу.");
        } catch (DomainException $e) {
            return redirect()->route('home')->with('error', $e->getMessage());
        }
    }

    public function cancel(Order $order): RedirectResponse
    {
        try {
            $this->escrowService->cancelAndRefundOrder($order->id);
            return redirect()->route('home')->with('success', "Заказ #{$order->id} отменен. Средства возвращены покупателю.");
        } catch (DomainException $e) {
            return redirect()->route('home')->with('error', $e->getMessage());
        }
    }
}