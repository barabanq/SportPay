@extends('layouts.app')

@section('title', 'Сообщения по сделкам — SportPay')

@section('content')
<div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
    <!-- Левая панель: Список диалогов -->
    <div class="bg-slate-900 border border-slate-800 rounded-2xl overflow-hidden flex flex-col h-[74vh]">
        <div class="p-4 border-b border-slate-800">
            <h1 class="text-sm font-extrabold uppercase tracking-wider text-slate-300">Диалоги по сделкам</h1>
        </div>

        <div class="divide-y divide-slate-800/70 overflow-y-auto flex-1">
            @if($orders->isEmpty())
                <div class="p-8 text-center text-xs text-slate-500">
                    У вас пока нет активных сделок и диалогов.
                </div>
            @else
                @foreach($orders as $dialogOrder)
                    @php
                        $counterparty = $dialogOrder->counterpartyFor($user);
                        $isActive = $activeOrder && $activeOrder->id === $dialogOrder->id;
                    @endphp
                    <a href="{{ route('chat.index', ['order_id' => $dialogOrder->id]) }}"
                       class="block p-4 transition {{ $isActive ? 'bg-slate-800/90 border-l-4 border-emerald-400' : 'hover:bg-slate-800/40' }}">
                        <div class="flex items-center justify-between mb-1">
                            <div class="font-bold text-sm text-white flex items-center gap-2">
                                <span>{{ $counterparty->username }}</span>
                                @if($dialogOrder->unread_count > 0)
                                    <span class="px-1.5 py-0.5 text-[10px] font-extrabold rounded-full bg-rose-500 text-white">
                                        +{{ $dialogOrder->unread_count }}
                                    </span>
                                @endif
                            </div>
                            <span class="text-[11px] font-semibold px-2 py-0.5 rounded {{ $dialogOrder->status->badgeClasses() }}">
                                {{ $dialogOrder->status->label() }}
                            </span>
                        </div>
                        <div class="text-xs text-emerald-400 font-medium truncate">
                            Заказ #{{ $dialogOrder->id }} • {{ $dialogOrder->lot->title ?? 'Лот' }}
                        </div>
                        @if($dialogOrder->latestMessage)
                            <div class="text-xs text-slate-400 truncate mt-1">
                                {{ $dialogOrder->latestMessage->body }}
                            </div>
                        @endif
                    </a>
                @endforeach
            @endif
        </div>
    </div>

    <!-- Правая панель: Активный диалог -->
    <div class="lg:col-span-2 bg-slate-900 border border-slate-800 rounded-2xl flex flex-col justify-between h-[74vh] overflow-hidden">
        @if(!$activeOrder)
            <div class="p-12 text-center text-sm text-slate-500 my-auto">
                Выберите сделку слева, чтобы открыть чат.
            </div>
        @else
            @php $partner = $activeOrder->counterpartyFor($user); @endphp
            <!-- Шапка диалога -->
            <div class="p-4 border-b border-slate-800 flex flex-wrap items-center justify-between gap-3 bg-slate-900/60">
                <div>
                    <div class="flex items-center gap-2">
                        <a href="{{ route('users.show', $partner->id) }}" class="font-extrabold text-white hover:text-emerald-400">
                            {{ $partner->username }}
                        </a>
                        <span class="text-xs px-2 py-0.5 rounded font-bold {{ $activeOrder->status->badgeClasses() }}">
                            {{ $activeOrder->status->label() }}
                        </span>
                        <span class="w-2 h-2 rounded-full bg-emerald-400 animate-pulse" title="Живое обновление активно"></span>
                    </div>
                    <div class="text-xs text-slate-400 mt-0.5">
                        Заказ #{{ $activeOrder->id }} • {{ $activeOrder->lot->title ?? 'Лот' }} ({{ number_format((float) $activeOrder->total_price, 0, '.', ' ') }} ₽)
                    </div>
                </div>

                <a href="{{ route('orders.show', $activeOrder) }}" class="px-3.5 py-2 rounded-xl bg-slate-800 hover:bg-slate-700 text-xs font-bold text-slate-200 transition">
                    Карточка сделки →
                </a>
            </div>

            <!-- Сообщения -->
            <div id="dialog-messages-box" style="overflow-y: auto; scroll-behavior: auto;" class="p-5 space-y-3 flex-1 overflow-y-auto">
                @foreach($activeOrder->messages as $msg)
                    @if($msg->is_system)
                        <div class="p-3 rounded-xl bg-amber-500/10 border border-amber-500/20 text-center text-xs text-amber-300" data-msg-id="{{ $msg->id }}">
                            🛡️ {{ $msg->body }}
                        </div>
                    @else
                        @php $isMe = $msg->user_id === $user->id; @endphp
                        <div class="flex flex-col {{ $isMe ? 'items-end' : 'items-start' }}" data-msg-id="{{ $msg->id }}">
                            <div class="text-[11px] text-slate-400 mb-1">
                                {{ $msg->user->username ?? 'Юзер' }} • {{ $msg->created_at?->format('H:i') }}
                            </div>
                            <div class="max-w-md px-4 py-2.5 rounded-2xl text-sm {{ $isMe ? 'bg-emerald-500 text-slate-950 font-medium' : 'bg-slate-800 text-slate-100 border border-slate-700' }}">
                                {{ $msg->body }}
                            </div>
                        </div>
                    @endif
                @endforeach
            </div>

            <!-- Форма отправки -->
            <form id="dialog-chat-form" action="{{ route('orders.messages.store', $activeOrder) }}" method="POST" class="p-4 border-t border-slate-800 flex gap-2 bg-slate-900/60">
                @csrf
                <input type="text" id="dialog-chat-input" name="body" required autocomplete="off" placeholder="Написать сообщение..." class="flex-1 bg-slate-800 border border-slate-700 rounded-xl px-4 py-2.5 text-sm text-white focus:outline-none focus:border-emerald-500">
                <button type="submit" class="px-5 py-2.5 rounded-xl bg-emerald-500 hover:bg-emerald-400 text-slate-950 font-extrabold text-xs transition">
                    Отправить
                </button>
            </form>

            <script>
            (function () {
                const box = document.getElementById('dialog-messages-box');
                const form = document.getElementById('dialog-chat-form');
                const input = document.getElementById('dialog-chat-input');
                const currentUserId = {{ (int) $user->id }};
                const orderId = {{ (int) $activeOrder->id }};
                const pollUrl = "{{ route('orders.messages.index', $activeOrder) }}";

                let lastMessageId = {{ (int) ($activeOrder->messages->last()?->id ?? 0) }};

                function forceScrollBottom() {
                    if (!box) return;
                    box.scrollTop = box.scrollHeight + 9999;
                }

                // Прокручиваем вниз сразу, после загрузки DOM и после рендера стилей Tailwind CDN
                forceScrollBottom();
                window.addEventListener('load', () => {
                    forceScrollBottom();
                    setTimeout(forceScrollBottom, 120);
                });

                function appendDialogMessage(msg) {
                    const msgId = Number(msg.id || 0);
                    if (msgId > 0 && box.querySelector(`[data-msg-id="${msgId}"]`)) {
                        return;
                    }
                    if (msgId > lastMessageId) {
                        lastMessageId = msgId;
                    }

                    const wrapper = document.createElement('div');
                    if (msgId > 0) wrapper.setAttribute('data-msg-id', String(msgId));

                    if (msg.is_system) {
                        wrapper.className = 'p-3 rounded-xl bg-amber-500/10 border border-amber-500/20 text-center text-xs text-amber-300';
                        wrapper.textContent = '🛡️ ' + msg.body;
                    } else {
                        const isMe = Number(msg.user_id) === currentUserId;
                        wrapper.className = `flex flex-col ${isMe ? 'items-end' : 'items-start'}`;

                        const meta = document.createElement('div');
                        meta.className = 'text-[11px] text-slate-400 mb-1';
                        meta.textContent = `${msg.username} • ${msg.time}`;

                        const bubble = document.createElement('div');
                        bubble.className = `max-w-md px-4 py-2.5 rounded-2xl text-sm ${isMe ? 'bg-emerald-500 text-slate-950 font-medium' : 'bg-slate-800 text-slate-100 border border-slate-700'}`;
                        bubble.textContent = msg.body;

                        wrapper.appendChild(meta);
                        wrapper.appendChild(bubble);
                    }

                    box.appendChild(wrapper);
                    forceScrollBottom();
                }

                // Отправка без перезагрузки страницы
                form.addEventListener('submit', async function (e) {
                    e.preventDefault();
                    const body = input.value.trim();
                    if (!body) return;

                    input.value = '';
                    input.focus();

                    try {
                        const res = await fetch(form.action, {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'X-CSRF-TOKEN': '{{ csrf_token() }}',
                                'Accept': 'application/json'
                            },
                            body: JSON.stringify({ body })
                        });
                        if (res.ok) {
                            const data = await res.json();
                            if (data.message) appendDialogMessage(data.message);
                        }
                    } catch (err) {
                        console.error('Ошибка отправки:', err);
                    }
                });

                // 1. WebSocket (если запущен Reverb)
                if (window.Echo) {
                    window.Echo.channel(`orders.${orderId}`)
                        .listen('.order.message.sent', (data) => appendDialogMessage(data));
                }

                // 2. Надежный фоновый опрос каждые 2 секунды (работает даже без Reverb)
                setInterval(async () => {
                    try {
                        const res = await fetch(`${pollUrl}?after_id=${lastMessageId}`, {
                            headers: { 'Accept': 'application/json' }
                        });
                        if (res.ok) {
                            const data = await res.json();
                            if (Array.isArray(data.messages)) {
                                data.messages.forEach(appendDialogMessage);
                            }
                        }
                    } catch (e) {}
                }, 2000);
            })();
            </script>
        @endif
    </div>
</div>
@endsection