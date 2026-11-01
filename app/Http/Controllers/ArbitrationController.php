<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Enums\OrderStatus;
use App\Models\Order;
use App\Services\EscrowService;
use DomainException;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class ArbitrationController extends Controller
{
    public function __construct(
        private readonly EscrowService $escrowService
    ) {}

    public function index(): View
    {
        $user = $this->currentUser();

        $disputedOrders = Order::with(['lot', 'buyer', 'seller', 'latestMessage'])
            ->where('status', OrderStatus::Disputed)
            ->latest('updated_at')
            ->get();

        return view('arbitration.index', [
            'user' => $user,
            'disputedOrders' => $disputedOrders,
        ]);
    }

    public function resolve(Request $request, Order $order): RedirectResponse
    {
        $data = $request->validate([
            'decision' => ['required', 'string', 'in:refund_buyer,release_seller'],
            'comment' => ['nullable', 'string', 'max:1000'],
        ]);

        try {
            $this->escrowService->resolveDispute(
                orderId: $order->id,
                arbiterId: $this->currentUser()->id,
                decision: $data['decision'],
                comment: $data['comment'] ?? null
            );

            return redirect()
                ->route('orders.show', $order)
                ->with('success', "Спор по заказу #{$order->id} успешно разрешен арбитром!");
        } catch (DomainException $e) {
            return back()->with('error', $e->getMessage());
        }
    }
}