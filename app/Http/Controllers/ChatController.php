<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\Message;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use DomainException;

class ChatController extends Controller
{
    public function store(Request $request, Order $order): RedirectResponse
    {
        $user = $this->currentUser();

        // Проверяем, что текущий юзер — участник сделки (покупатель или продавец)
        if ($order->buyer_id !== $user->id && $order->seller_id !== $user->id) {
            abort(403, 'У вас нет доступа к чату этой сделки.');
        }

        $data = $request->validate([
            'body' => ['required', 'string', 'max:2000'],
        ]);

        Message::create([
            'order_id' => $order->id,
            'user_id' => $user->id,
            'body' => $data['body'],
            'is_system' => false,
        ]);

        return back()->with('success', 'Сообщение отправлено в чат сделки.');
    }
}