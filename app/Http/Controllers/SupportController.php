<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\SupportTicket;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class SupportController extends Controller
{
    public function rules(): View
    {
        return view('support.rules', [
            'user' => $this->currentUser(),
        ]);
    }

    public function helpCenter(): View
    {
        return view('support.help', [
            'user' => $this->currentUser(),
        ]);
    }

    public function createTicket(): View
    {
        $user = $this->currentUser();

        $myOrders = Order::with('lot')
            ->where('buyer_id', $user->id)
            ->orWhere('seller_id', $user->id)
            ->latest()
            ->limit(30)
            ->get();

        return view('support.create', [
            'user' => $user,
            'myOrders' => $myOrders,
        ]);
    }

    public function storeTicket(Request $request): RedirectResponse
    {
        $user = $this->currentUser();

        $data = $request->validate([
            'category' => ['required', 'string', 'in:order_issue,payment,account,violation,other'],
            'order_id' => ['nullable', 'integer', 'exists:orders,id'],
            'subject' => ['required', 'string', 'min:5', 'max:255'],
            'message' => ['required', 'string', 'min:10', 'max:3000'],
        ]);

        SupportTicket::create([
            'user_id' => $user->id,
            'order_id' => $data['order_id'] ?? null,
            'category' => $data['category'],
            'subject' => $data['subject'],
            'message' => $data['message'],
            'status' => 'open',
        ]);

        return redirect()->route('support.tickets.index')->with('success', 'Ваш запрос отправлен в поддержку SportPay!');
    }

    public function myTickets(): View
    {
        $user = $this->currentUser();

        $tickets = $user->isArbiter()
            ? SupportTicket::with(['user', 'order'])->latest()->get()
            : SupportTicket::with(['order'])->where('user_id', $user->id)->latest()->get();

        return view('support.index', [
            'user' => $user,
            'tickets' => $tickets,
        ]);
    }

    public function replyTicket(Request $request, SupportTicket $ticket): RedirectResponse
    {
        $user = $this->currentUser();

        if (!$user->isArbiter()) {
            abort(403, 'Только сотрудник поддержки или арбитр может отвечать на запросы.');
        }

        $data = $request->validate([
            'staff_reply' => ['required', 'string', 'min:3', 'max:3000'],
            'status' => ['required', 'string', 'in:answered,closed'],
        ]);

        $ticket->update([
            'staff_reply' => $data['staff_reply'],
            'status' => $data['status'],
        ]);

        return back()->with('success', "Ответ по запросу #{$ticket->id} сохранен!");
    }
}
