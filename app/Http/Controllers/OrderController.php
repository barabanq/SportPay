<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\Lot;
use App\Models\Order;
use App\Services\EscrowService;
use DomainException;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class OrderController extends Controller
{
    public function __construct(
        private readonly EscrowService $escrowService
    ) {
    }

    public function show(Order $order): View
    {
        $this->authorizeAction('view', $order);

        $user = $this->currentUser();

        $order->messages()
            ->where('user_id', '!=', $user->id)
            ->whereNull('read_at')
            ->update(['read_at' => now()]);

        $order->load(['lot', 'buyer', 'seller', 'messages.user', 'review']);

        $partnerId = $user->id === $order->buyer_id ? $order->seller_id : $order->buyer_id;
        if ($partnerId) {
            app(\App\Services\TelegramBotService::class)->clearChatCooldown($user->id, (int) $partnerId);
        }

        return view('orders.show', [
            'user' => $user,
            'order' => $order,
        ]);
    }

    public function purchase(Request $request, Lot $lot): RedirectResponse
    {
        $data = $request->validate([
            'quantity' => ['nullable', 'integer', 'min:1', 'max:1000'],
        ]);

        $quantity = (int) ($data['quantity'] ?? 1);

        try {
            $order = $this->escrowService->purchaseLot(
                buyerId: $this->currentUser()->id,
                lotId: $lot->id,
                quantity: $quantity
            );

            
            app(\App\Services\TelegramBotService::class)->notifyOrderCreated($order);
return redirect()
                ->route('orders.show', $order)
                ->with('success', "Сделка #{$order->id} успешно создана! Средства зарезервированы в эскроу.");
        } catch (DomainException $e) {
            return redirect()
                ->route('lots.show', $lot)
                ->with('error', $e->getMessage());
        }
    }

    public function confirm(Request $request, Order $order): RedirectResponse
    {
        $this->authorizeAction('confirm', $order);

        $data = $request->validate([
            'rating' => ['nullable', 'integer', 'min:1', 'max:5'],
            'comment' => ['nullable', 'string', 'max:1000'],
        ]);

        try {
            $this->escrowService->confirmOrder(
                orderId: $order->id,
                actorId: $this->currentUser()->id,
                rating: (int) ($data['rating'] ?? 5),
                comment: $data['comment'] ?? null
            );

            return back()->with('success', 'Выполнение заказа подтверждено! Средства переведены продавцу.');
        } catch (DomainException $e) {
            return back()->with('error', $e->getMessage());
        }
    }

    public function cancel(Order $order): RedirectResponse
    {
        $this->authorizeAction('cancel', $order);

        try {
            $this->escrowService->cancelOrder(
                orderId: $order->id,
                actorId: $this->currentUser()->id
            );

            return back()->with('success', 'Заказ отменен. 100% средств возвращено покупателю, товар восстановлен на складе.');
        } catch (DomainException $e) {
            return back()->with('error', $e->getMessage());
        }
    }

    public function dispute(Request $request, Order $order): RedirectResponse
    {
        $this->authorizeAction('dispute', $order);

        $data = $request->validate([
            'reason' => ['required', 'string', 'min:5', 'max:1000'],
        ]);

        try {
            $this->escrowService->openDispute(
                orderId: $order->id,
                actorId: $this->currentUser()->id,
                reason: $data['reason']
            );

            return back()->with('success', 'Открыт арбитражный спор. Официальный арбитр подключен к сделке.');
        } catch (DomainException $e) {
            return back()->with('error', $e->getMessage());
        }
    }
}
