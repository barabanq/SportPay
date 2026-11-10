<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\Message;
use App\Models\Order;
use App\Models\User;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class ChatController extends Controller
{
    public function index(Request $request): View
    {
        $user = $this->currentUser();
        $user->update(['last_seen_at' => now()]);

        $myOrders = Order::with(['lot', 'buyer', 'seller'])
            ->where('buyer_id', $user->id)
            ->orWhere('seller_id', $user->id)
            ->latest('id')
            ->get();

        $partnerIds = $myOrders->map(function (Order $order) use ($user) {
            return (int) $order->buyer_id === (int) $user->id
                ? (int) $order->seller_id
                : (int) $order->buyer_id;
        })->unique()->reject(fn (int $id) => $id === (int) $user->id)->values();

        $requestedPartnerId = (int) ($request->query('partner') ?: $request->query('user') ?: 0);
        if ($requestedPartnerId > 0 && $requestedPartnerId !== (int) $user->id && !$partnerIds->contains($requestedPartnerId)) {
            $partnerIds->prepend($requestedPartnerId);
        }

        $partnersMap = User::whereIn('id', $partnerIds)->get()->keyBy('id');
        $partners = $partnerIds->map(fn (int $id) => $partnersMap->get($id))->filter()->values();

        $activePartner = null;
        if ($requestedPartnerId > 0 && $partnersMap->has($requestedPartnerId)) {
            $activePartner = $partnersMap->get($requestedPartnerId);
        } elseif ($partners->isNotEmpty()) {
            $activePartner = $partners->first();
        }

        $sharedOrders = collect();
        $messages = collect();

        if ($activePartner) {
            $sharedOrders = Order::with('lot')
                ->where(function ($q) use ($user, $activePartner) {
                    $q->where('buyer_id', $user->id)->where('seller_id', $activePartner->id);
                })
                ->orWhere(function ($q) use ($user, $activePartner) {
                    $q->where('buyer_id', $activePartner->id)->where('seller_id', $user->id);
                })
                ->latest('id')
                ->get();

            if ($sharedOrders->isNotEmpty()) {
                $messages = Message::with(['user', 'order.lot'])
                    ->whereIn('order_id', $sharedOrders->pluck('id'))
                    ->orderBy('id')
                    ->get();
            }
        }

        return view('chat.index', [
            'user' => $user,
            'partners' => $partners,
            'activePartner' => $activePartner,
            'sharedOrders' => $sharedOrders,
            'messages' => $messages,
        ]);
    }

    /**
     * Отправка сообщения внутри конкретной сделки (маршрут orders.messages.store)
     */
    public function store(Request $request, Order $order): RedirectResponse|JsonResponse
    {
        $this->authorizeAction('view', $order);

        $user = $this->currentUser();
        $user->update(['last_seen_at' => now()]);

        $data = $request->validate([
            'body' => ['required', 'string', 'max:2000'],
        ]);

        $message = Message::create([
            'order_id' => $order->id,
            'user_id' => $user->id,
            'body' => trim((string) $data['body']),
            'is_system' => false,
        ]);

        if ($request->wantsJson()) {
            return response()->json([
                'ok' => true,
                'message' => [
                    'id' => $message->id,
                    'order_id' => $order->id,
                    'sender_name' => $user->username,
                    'body' => $message->body,
                    'is_mine' => true,
                    'is_system' => false,
                    'time' => $message->created_at?->format('H:i') ?? now()->format('H:i'),
                ],
            ]);
        }

        return redirect()->back()->with('success', __('Сообщение отправлено!'));
    }

    /**
     * Отправка сообщения со страницы /messages (маршрут chat.send)
     */
    public function send(Request $request): RedirectResponse|JsonResponse
    {
        $user = $this->currentUser();
        $user->update(['last_seen_at' => now()]);

        $data = $request->validate([
            'order_id' => ['nullable', 'integer', 'exists:orders,id'],
            'partner_id' => ['nullable', 'integer', 'exists:users,id'],
            'body' => ['required', 'string', 'max:2000'],
        ]);

        $order = null;
        if (!empty($data['order_id'])) {
            $order = Order::findOrFail((int) $data['order_id']);
        } elseif (!empty($data['partner_id'])) {
            $partnerId = (int) $data['partner_id'];
            $order = Order::where(function ($q) use ($user, $partnerId) {
                $q->where('buyer_id', $user->id)->where('seller_id', $partnerId);
            })->orWhere(function ($q) use ($user, $partnerId) {
                $q->where('buyer_id', $partnerId)->where('seller_id', $user->id);
            })->latest('id')->first();
        }

        if (!$order) {
            return redirect()->back()->withErrors([
                'body' => __('Чтобы начать диалог, выберите или оформите сделку с этим пользователем.'),
            ]);
        }

        $this->authorizeAction('view', $order);

        $message = Message::create([
            'order_id' => $order->id,
            'user_id' => $user->id,
            'body' => trim((string) $data['body']),
            'is_system' => false,
        ]);

        $partnerId = (int) $order->buyer_id === (int) $user->id
            ? (int) $order->seller_id
            : (int) $order->buyer_id;

        if ($request->wantsJson()) {
            return response()->json([
                'ok' => true,
                'message' => [
                    'id' => $message->id,
                    'order_id' => $order->id,
                    'sender_name' => $user->username,
                    'body' => $message->body,
                    'is_mine' => true,
                    'is_system' => false,
                    'time' => $message->created_at?->format('H:i') ?? now()->format('H:i'),
                ],
            ]);
        }

        return redirect()->route('chat.index', ['partner' => $partnerId]);
    }

    /**
     * Легкий JSON-поллинг для звуковых уведомлений и автоподгрузки чата
     */
    public function poll(Request $request): JsonResponse
    {
        $user = $this->currentUser();
        $partnerId = (int) $request->query('partner_id', 0);
        $afterId = (int) $request->query('after_id', 0);

        $myOrderIds = Order::where('buyer_id', $user->id)
            ->orWhere('seller_id', $user->id)
            ->pluck('id');

        $latestIncomingMessage = null;
        if ($myOrderIds->isNotEmpty()) {
            $latestIncomingMessage = Message::with(['user', 'order'])
                ->whereIn('order_id', $myOrderIds)
                ->where('user_id', '!=', $user->id)
                ->latest('id')
                ->first();
        }

        $latestSellerOrder = Order::with(['lot', 'buyer'])
            ->where('seller_id', $user->id)
            ->latest('id')
            ->first();

        $chatMessages = [];
        if ($partnerId > 0) {
            $sharedOrderIds = Order::where(function ($q) use ($user, $partnerId) {
                $q->where('buyer_id', $user->id)->where('seller_id', $partnerId);
            })->orWhere(function ($q) use ($user, $partnerId) {
                $q->where('buyer_id', $partnerId)->where('seller_id', $user->id);
            })->pluck('id');

            if ($sharedOrderIds->isNotEmpty()) {
                $query = Message::with('user')
                    ->whereIn('order_id', $sharedOrderIds)
                    ->orderBy('id');

                if ($afterId > 0) {
                    $query->where('id', '>', $afterId);
                }

                $chatMessages = $query->get()->map(fn (Message $m) => [
                    'id' => $m->id,
                    'order_id' => $m->order_id,
                    'sender_name' => $m->is_system ? '⚡ SportPay' : ($m->user?->username ?? 'User'),
                    'body' => $m->body,
                    'is_mine' => (int) $m->user_id === (int) $user->id,
                    'is_system' => (bool) $m->is_system,
                    'time' => $m->created_at?->format('H:i') ?? '',
                ])->values()->all();
            }
        }

        return response()->json([
            'ok' => true,
            'latest_incoming_message' => $latestIncomingMessage ? [
                'id' => $latestIncomingMessage->id,
                'sender_name' => $latestIncomingMessage->user?->username ?? 'SportPay',
                'body' => $latestIncomingMessage->body,
                'partner_id' => $latestIncomingMessage->user_id,
                'order_id' => $latestIncomingMessage->order_id,
            ] : null,
            'latest_seller_order' => $latestSellerOrder ? [
                'id' => $latestSellerOrder->id,
                'title' => $latestSellerOrder->lot?->title ?? 'Лот',
                'buyer' => $latestSellerOrder->buyer?->username ?? 'Покупатель',
            ] : null,
            'chat_messages' => $chatMessages,
        ]);
    }
}
