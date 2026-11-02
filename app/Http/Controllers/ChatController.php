<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\Message;
use App\Models\Order;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class ChatController extends Controller
{
    public function index(Request $request): View
    {
        $user = $this->currentUser();$activeOrder = null;

        if ($request->filled('order_id')) {$activeOrder = Order::with(['lot', 'buyer', 'seller', 'review', 'messages.user'])
                ->findOrFail($request->integer('order_id'));

                $this->authorizeAction('view', $activeOrder);

            Message::where('order_id', $activeOrder->id)
                ->where('user_id', '!=', $user->id)
                ->whereNull('read_at')
                ->update(['read_at' => now()]);
        }

        $dialogs = Order::with(['lot', 'buyer', 'seller', 'latestMessage'])
            ->withCount([
                'messages as unread_count' => function ($query) use ($user): void {
                    $query->where('user_id', '!=',$user->id)->whereNull('read_at');
                },
            ])
            ->where(function ($query) use ($user): void {
                $query->where('buyer_id',$user->id)
                      ->orWhere('seller_id', $user->id);
            })
            ->latest('updated_at')
            ->get();

            return view('chat.index', [
                'user' => $user,
                'dialogs' => $dialogs,
                'activeOrder' => $activeOrder,
            ]);
    }

    public function store(Request $request, Order $order): \Illuminate\Http\JsonResponse|RedirectResponse
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

        broadcast(new \App\Events\OrderMessageSent($message))->toOthers();

        if ($request->wantsJson()) {
            return response()->json([
                'status' => 'success',
                'message' => [
                    'id' => $message->id,
                    'order_id' => $order->id,
                    'user_id' => $user->id,
                    'username' => $user->username,
                    'body' => $message->body,
                    'is_system' => false,
                    'time' => $message->created_at?->format('H:i'),
                ],
            ]);
        }

        return back()->with('success', 'Сообщение отправлено.');
    }
}