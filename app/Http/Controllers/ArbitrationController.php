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
    ) {
    }

    public function index(): View
    {
        $disputedOrders = Order::with(['lot', 'buyer', 'seller', 'latestMessage'])
            ->where('status', OrderStatus::Disputed)
            ->latest()
            ->get();

        return view('arbitration.index', [
            'user' => $this->currentUser(),
            'disputedOrders' => $disputedOrders,
        ]);
    }

    public function resolve(Request $request, Order $order): RedirectResponse
    {
        $this->authorizeAction('resolve', $order);

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

            return back()->with('success', "Спор по сделке #{$order->id} успешно разрешен арбитром.");
        } catch (DomainException $e) {
            return back()->with('error', $e->getMessage());
        }
    }
}
