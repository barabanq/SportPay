@extends('layouts.app')

@section('title', 'Сообщения — SportPay')

@section('content')
<div class="bg-slate-900 border border-slate-800 rounded-2xl overflow-hidden grid grid-cols-1 lg:grid-cols-12 min-h-[600px]">
    <div class="lg:col-span-4 border-r border-slate-800 flex flex-col">
        <div class="p-4 border-b border-slate-800 flex items-center justify-between">
            <h1 class="text-xl font-extrabold text-white">Сообщения</h1>
            <span class="text-xs text-slate-400">{{ $dialogs->count() }} диалогов</span>
        </div>

        <div class="divide-y divide-slate-800/70 overflow-y-auto max-h-[540px]">
            @if($dialogs->isEmpty())
                <div class="p-8 text-center text-sm text-slate-500">У вас пока нет диалогов по сделкам.</div>
            @else
                @foreach($dialogs as $dialog)
                    @php
                        $companion = $dialog->counterpartyFor($user);
                        $isSelected = $activeOrder && $activeOrder->id === $dialog->id;
                        $lastMsg = $dialog->latestMessage;
                    @endphp
                    <a href="{{ route('chat.index', ['order_id' => $dialog->id]) }}"
                       class="flex items-start gap-3 p-4 transition {{ $isSelected ? 'bg-slate-800/90 border-l-4 border-emerald-400' : 'hover:bg-slate-800/40' }}">
                        <div class="w-10 h-10 rounded-full bg-slate-800 border border-slate-700 flex items-center justify-center font-extrabold text-emerald-400 shrink-0">
                            {{ strtoupper(substr($companion->username ?? 'U', 0, 1)) }}
                        </div>
                        <div class="flex-1 min-w-0">
                            <div class="flex items-center justify-between gap-2">
                                <span class="font-bold text-sm text-white truncate">{{ $companion->username ?? 'Юзер' }}</span>
                                <span class="text-[11px] text-slate-400">{{ ($lastMsg?->created_at ?? $dialog->updated_at)?->format('d.m H:i') }}</span>
                            </div>
                            <div class="text-[11px] text-emerald-400 truncate">Сделка #{{ $dialog->id }}: {{ $dialog->lot->title ?? 'Лот' }}</div>
                            <div class="flex items-center justify-between gap-2 mt-1">
                                <p class="text-xs text-slate-400 truncate">{{ $lastMsg ? $lastMsg->body : 'Заказ создан...' }}</p>
                                @if($dialog->unread_count > 0)
                                    <span class="px-1.5 py-0.5 text-[10px] font-extrabold rounded-full bg-rose-500 text-white">{{ $dialog->unread_count }}</span>
                                @endif
                            </div>
                        </div>
                    </a>
                @endforeach
            @endif
        </div>
    </div>

    <div class="lg:col-span-8 flex flex-col justify-between bg-slate-950/40">
        @if($activeOrder)
            @php $companion = $activeOrder->counterpartyFor($user); @endphp
            <div class="p-4 border-b border-slate-800 bg-slate-900/60 flex items-center justify-between gap-3">
                <div>
                    <a href="{{ route('users.show', $companion) }}" class="font-bold text-white hover:text-emerald-400">{{ $companion->username }}</a>
                    <span class="ml-2 px-2 py-0.5 rounded text-[11px] font-bold {{ $activeOrder->status->badgeClasses() }}">{{ $activeOrder->status->label() }}</span>
                    <div class="text-xs text-slate-400 mt-0.5">Заказ #{{ $activeOrder->id }} • {{ $activeOrder->lot->title ?? 'Лот' }} ({{ number_format((float) $activeOrder->total_price, 0, '.', ' ') }} ₽)</div>
                </div>
                <a href="{{ route('orders.show', $activeOrder) }}" class="px-3 py-1.5 rounded-lg bg-slate-800 hover:bg-slate-700 text-slate-200 text-xs font-semibold">Карточка сделки →</a>
            </div>

            <div class="flex-1 p-5 space-y-3 overflow-y-auto max-h-[420px]">
                @foreach($activeOrder->messages as $msg)
                    @if($msg->is_system)
                        <div class="p-3 rounded-xl bg-amber-500/10 border border-amber-500/20 text-center text-xs text-amber-300">🛡️ {{ $msg->body }}</div>
                    @else
                        @php $isMe = $msg->user_id === $user->id; @endphp
                        <div class="flex flex-col {{ $isMe ? 'items-end' : 'items-start' }}">
                            <div class="text-[11px] text-slate-400 mb-1">{{ $msg->user->username ?? 'Юзер' }} • {{ $msg->created_at?->format('H:i') }}</div>
                            <div class="max-w-lg px-4 py-2 rounded-2xl text-sm {{ $isMe ? 'bg-emerald-500 text-slate-950 font-medium' : 'bg-slate-800 text-slate-100 border border-slate-700' }}">{{ $msg->body }}</div>
                        </div>
                    @endif
                @endforeach
            </div>

            <form action="{{ route('orders.messages.store', $activeOrder) }}" method="POST" class="p-4 border-t border-slate-800 bg-slate-900/60 flex gap-3">
                @csrf
                <input type="text" name="body" required autofocus placeholder="Написать сообщение..." class="flex-1 bg-slate-800 border border-slate-700 rounded-xl px-4 py-2 text-sm text-white">
                <button type="submit" class="px-5 py-2 rounded-xl bg-emerald-500 hover:bg-emerald-400 text-slate-950 font-extrabold text-xs">Отправить</button>
            </form>
        @else
            <div class="flex-1 flex flex-col items-center justify-center text-center p-8">
                <div class="text-4xl mb-3">💬</div>
                <div class="text-base font-semibold text-slate-400">Выберите диалог слева.</div>
                <div class="text-sm text-slate-500 mt-1">Уверены, у вас всё получится.</div>
            </div>
        @endif
    </div>
</div>
@endsection