@extends('layouts.app')

@section('title', 'Сообщения — SportPay')

@section('content')
<div class="bg-slate-900 border border-slate-800 rounded-2xl overflow-hidden grid grid-cols-1 lg:grid-cols-12 min-h-[640px]">

    <!-- ЛЕВАЯ КОЛОНКА: Список всех диалогов (как на FunPay) -->
    <div class="lg:col-span-4 border-r border-slate-800 flex flex-col">
        <div class="p-4 border-b border-slate-800 flex items-center justify-between">
            <h1 class="text-xl font-extrabold text-white">Сообщения</h1>
            <span class="text-xs text-slate-400">{{ $dialogs->count() }} диалогов</span>
        </div>

        <div class="divide-y divide-slate-800/70 overflow-y-auto max-h-[580px]">
            @forelse($dialogs as $dialog)
                @php
                    $companion = $dialog->counterpartyFor($user);
                    $isSelected = $activeOrder && $activeOrder->id === $dialog->id;
                    $lastMsg = $dialog->latestMessage;
                @endphp
                <a href="{{ route('chat.index', ['order_id' => $dialog->id]) }}"
                   class="flex items-start gap-3.5 p-4 transition {{ $isSelected ? 'bg-slate-800/90 border-l-4 border-emerald-400' : 'hover:bg-slate-800/40' }}">

                    <!-- Аватарка собеседника -->
                    <div class="w-11 h-11 rounded-full bg-slate-800 border border-slate-700 flex items-center justify-center font-extrabold text-emerald-400 shrink-0">
                        {{ strtoupper(substr($companion->username ?? 'U', 0, 1)) }}
                    </div>

                    <div class="flex-1 min-w-0">
                        <div class="flex items-center justify-between gap-2">
                            <span class="font-bold text-sm text-white truncate">
                                {{ $companion->username ?? 'Пользователь' }}
                            </span>
                            <span class="text-[11px] text-slate-400 shrink-0">
                                {{ ($lastMsg?->created_at ?? $dialog->updated_at)?->format('d.m H:i') }}
                            </span>
                        </div>

                        <div class="text-[11px] text-emerald-400/90 truncate mt-0.5">
                            Сделка #{{ $dialog->id }}: {{ $dialog->lot->title ?? 'Лот' }}
                        </div>

                        <div class="flex items-center justify-between gap-2 mt-1">
                            <p class="text-xs {{ $dialog->unread_count > 0 ? 'text-white font-semibold' : 'text-slate-400' }} truncate">
                                {{ $lastMsg ? $lastMsg->body : 'Заказ создан. Начните диалог...' }}
                            </p>
                            @if($dialog->unread_count > 0)
                                <span class="px-1.5 py-0.5 text-[10px] font-extrabold rounded-full bg-rose-500 text-white shrink-0">
                                    {{ $dialog->unread_count }}
                                </span>
                            @endif
                        </div>
                    </div>
                </a>
            @empty
                <div class="p-8 text-center text-sm text-slate-500">
                    У вас пока нет активных диалогов по сделкам.
                </div>
            @endforelse
        </div>
    </div>

    <!-- ПРАВАЯ КОЛОНКА: Активный чат или заглушка "Выберите диалог слева" -->
    <div class="lg:col-span-8 flex flex-col justify-between bg-slate-950/40">
        @if($activeOrder)
            @php $companion = $activeOrder->counterpartyFor($user); @endphp

            <!-- Верхняя плашка сделки внутри чата -->
            <div class="p-4 border-b border-slate-800 bg-slate-900/60 flex flex-wrap items-center justify-between gap-3">
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 rounded-full bg-emerald-500/20 border border-emerald-500/40 flex items-center justify-center font-bold text-emerald-400">
                        {{ strtoupper(substr($companion->username ?? 'U', 0, 1)) }}
                    </div>
                    <div>
                        <div class="flex items-center gap-2">
                            <a href="{{ route('users.show', $companion) }}" class="font-bold text-white hover:text-emerald-400 transition">
                                {{ $companion->username }}
                            </a>
                            <span class="px-2 py-0.5 rounded text-[11px] font-bold {{ $activeOrder->status->badgeClasses() }}">
                                {{ $activeOrder->status->label() }}
                            </span>
                        </div>
                        <div class="text-xs text-slate-400">
                            Заказ #{{ $activeOrder->id }} • {{ $activeOrder->lot->title ?? 'Лот' }} •
                            <span class="text-emerald-400 font-bold">{{ number_format((float) $activeOrder->total_price, 0, '.', ' ') }} ₽</span>
                        </div>
                    </div>
                </div>

                <div class="flex items-center gap-2">
                    @if($activeOrder->status->isConfirmable() && $user->id === $activeOrder->buyer_id)
                        <form action="{{ route('orders.confirm', $activeOrder) }}" method="POST">
                            @csrf
                            <input type="hidden" name="rating" value="5">
                            <button type="submit" class="px-3 py-1.5 rounded-lg bg-emerald-500 hover:bg-emerald-400 text-slate-950 font-extrabold text-xs">
                                ✓ Подтвердить заказ
                            </button>
                        </form>
                    @endif
                    <a href="{{ route('orders.show', $activeOrder) }}" class="px-3 py-1.5 rounded-lg bg-slate-800 hover:bg-slate-700 text-slate-200 text-xs font-semibold">
                        Карточка сделки →
                    </a>
                </div>
            </div>

            <!-- Лента сообщений -->
            <div class="flex-1 p-5 space-y-3 overflow-y-auto max-h-[450px]">
                @forelse($activeOrder->messages as $msg)
                    @if($msg->is_system)
                        <div class="p-3 rounded-xl bg-amber-500/10 border border-amber-500/20 text-center text-xs text-amber-300">
                            🛡️ {{ $msg->body }}
                        </div>
                    @else
                        @php $isMe = $msg->user_id === $user->id; @endphp
                        <div class="flex flex-col {{ $isMe ? 'items-end' : 'items-start' }}">
                            <div class="text-[11px] text-slate-400 mb-1">
                                <span class="font-bold text-slate-300">{{ $msg->user->username ?? 'Юзер' }}</span>
                                • {{ $msg->created_at?->format('d.m H:i') }}
                            </div>
                            <div class="max-w-lg px-4 py-2.5 rounded-2xl text-sm {{ $isMe ? 'bg-emerald-500 text-slate-950 font-medium' : 'bg-slate-800 text-slate-100 border border-slate-700' }}">
                                {{ $msg->body }}
                            </div>
                        </div>
                    @endif
                @empty
                    <div class="text-center text-sm text-slate-500 py-16">
                        В этом заказе еще нет сообщений. Напишите первым!
                    </div>
                @endforelse
            </div>

            <!-- Форма отправки сообщения -->
            <form action="{{ route('orders.messages.store', $activeOrder) }}" method="POST" class="p-4 border-t border-slate-800 bg-slate-900/60 flex gap-3">
                @csrf
                <input type="text" name="body" required autofocus placeholder="Написать сообщение..." class="flex-1 bg-slate-800 border border-slate-700 rounded-xl px-4 py-2.5 text-sm text-white focus:outline-none focus:border-emerald-500">
                <button type="submit" class="px-6 py-2.5 rounded-xl bg-emerald-500 hover:bg-emerald-400 text-slate-950 font-extrabold text-xs">
                    Отправить
                </button>
            </form>
        @else
            <!-- Заглушка точь-в-точь как на скриншоте FunPay -->
            <div class="flex-1 flex flex-col items-center justify-center text-center p-8 select-none">
                <div class="w-20 h-20 rounded-full bg-slate-800/80 flex items-center justify-center text-4xl mb-4 text-slate-500">
                    💬
                </div>
                <div class="text-base font-semibold text-slate-400">Выберите диалог слева.</div>
                <div class="text-sm text-slate-500 mt-1">Уверены, у вас всё получится.</div>
            </div>
        @endif
    </div>

</div>
@endsection