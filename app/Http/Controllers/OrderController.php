<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Requests\ConfirmOrderRequest;
use App\Models\Lot;
use App\Models\Message;
use App\Models\Order;
use App\Models\User;
use App\Services\EscrowService;
use DomainException;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use App\Http\Requests\OpenDisputeRequest;

class OrderController extends Controller
{
    public function __construct(
        private readonly EscrowService $escrowService
    ) {}

    public function show(Order $order): View
    {
        $user = $this->currentUser();
        $this->authorizeAction('view', $order);

        $order->load(['lot', 'buyer', 'seller', 'review', 'messages.user']);

        Message::where('order_id', $order->id)
            ->where('user_id', '!=', $user->id)
            ->whereNull('read_at')
            ->update(['read_at' => now()]);

        return view('orders.show', [
            'user' => $user,
            'order' => $order,
        ]);
    }

    public function purchase(Lot $lot): RedirectResponse
    {
        try {
            $order =$this->escrowService->purchaseLot($this->currentUser()->id,$lot->id);
            return redirect()
                ->route('orders.show', $order)
                ->with('success', "Заказ #{$order->id} оплачен! Обсудите детали передачи с продавцом в чате.");
        } catch (DomainException $e) {
            return redirect()->route('home')->with('error', $e->getMessage());
        }
    }

    public function confirm(ConfirmOrderRequest $request, Order$order): RedirectResponse
    {
        try {
            $this->escrowService->confirmOrder(
                $order->id,$this->currentUser()->id,
                $request->integer('rating', 5),$request->input('comment')
            );
            return redirect()
                ->route('orders.show', $order)
                ->with('success', "Заказ #{$order->id} подтвержден! Деньги переведены продавцу.");
        } catch (DomainException $e) {
            return back()->with('error', $e->getMessage());
        }
    }

    public function cancel(Order $order): RedirectResponse
    {
        try {
            $this->escrowService->cancelAndRefundOrder($order->id,$this->currentUser()->id);
            return redirect()
                ->route('orders.show', $order)
                ->with('success', "Заказ #{$order->id} отменен. Средства возвращены покупателю.");
        } catch (DomainException $e) {
            return back()->with('error', $e->getMessage());
        }
    }
    public function dispute(OpenDisputeRequest $request, Order $order): RedirectResponse
    {
        try {
            $this->escrowService->openDispute(
                $order->id,
                $this->currentUser()->id,
                (string) $request->validated('reason')
            );

            return redirect()
                ->route('orders.show', $order)
                ->with('error', "По заказу #{$order->id} открыт спор! Арбитр SportPay уведомлен.");
        } catch (DomainException $e) {
            return back()->with('error', $e->getMessage());
        }
    }
}