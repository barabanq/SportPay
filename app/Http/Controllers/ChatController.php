<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Events\OrderMessageSent;
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

        $orders = Order::with(['lot', 'buyer', 'seller', 'latestMessage'])
            ->where(function ($query) use ($user): void {
                $query->where('buyer_id', $user->id)
                    ->orWhere('seller_id', $user->id);
            })
            ->withCount([
                'messages as unread_count' => function ($query) use ($user): void {
                    $query->where('user_id', '!=', $user->id)->whereNull('read_at');
                },
            ])
            ->latest('updated_at')
            ->get();

        $activeOrder = null;

        if ($request->filled('order_id')) {
            $activeOrder = Order::with(['lot', 'buyer', 'seller', 'messages.user'])
                    ->findOrFail((int) $request->integer('order_id'));

            $this->authorizeAction('view', $activeOrder);
        } elseif ($orders->isNotEmpty()) {
            $activeOrder = Order::with(['lot', 'buyer', 'seller', 'messages.user'])
                    ->find($orders->first()->id);
        }

        if ($activeOrder !== null) {
            Message::where('order_id', $activeOrder->id)
                ->where('user_id', '!=', $user->id)
                ->whereNull('read_at')
                ->update(['read_at' => now()]);
        }

        return view('chat.index', [
            'user' => $user,
            'allUsers' => User::orderBy('id')->get(),
            'orders' => $orders,
            'activeOrder' => $activeOrder,
        ]);
    }

    public function messages(Request $request, Order$order): JsonResponse
    {
        $user = $this->currentUser();
        $this->authorizeAction('view', $order);

        $afterId = (int)$request->query('after_id', 0);

        $messages = Message::with('user')
            ->where('order_id', $order->id)
            ->where('id', '>', $afterId)
            ->oldest('id')
            ->get();

        if ($messages->isNotEmpty()) {
            Message::where('order_id', $order->id)
                ->where('user_id', '!=', $user->id)
                ->whereNull('read_at')
                ->update(['read_at' => now()]);
        }

        return response()->json([
            'messages' => $messages->map(fn (Message$msg): array => [
                'id' => $msg->id,
                'order_id' => $msg->order_id,
                'user_id' => $msg->user_id,
                'username' => $msg->user->username ?? 'Система',
                'body' => $msg->body,
                'is_system' => (bool) $msg->is_system,
                'time' => $msg->created_at?->format('H:i') ?? now()->format('H:i'),
            ]),
        ]);
    }

    public function store(Request $request, Order$order): JsonResponse|RedirectResponse
    {
        $user = $this->currentUser();
        $this->authorizeAction('sendMessage', $order);

        $data = $request->validate([
            'body' => ['required', 'string', 'max:2000'],
        ]);

        $message = Message::create([
            'order_id' => $order->id,
            'user_id' => $user->id,
            'body' => $data['body'],
            'is_system' => false,
        ]);

        $message->load('user');
        $order->touch();

        try {
            broadcast(new OrderMessageSent($message))->toOthers();
        } catch (\Throwable) {
            // Игнорируем ошибку сокета, если Reverb не запущен — отработает фоновый пуллинг
        }

        $payload = [
            'id' => $message->id,
            'order_id' => $order->id,
            'user_id' => $user->id,
            'username' => $user->username,
            'body' => $message->body,
            'is_system' => false,
            'time' => $message->created_at?->format('H:i') ?? now()->format('H:i'),
        ];

        if ($request->wantsJson()) {
            return response()->json([
                'status' => 'success',
                'message' => $payload,
            ]);
        }

        return back();
    }
}
