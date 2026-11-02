@extends('layouts.app')

@section('title', "Заказ #{$order->id} — SportPay")

@section('content')
<div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
    <!-- Левая колонка: Информация о сделке и управление Эскроу -->
    <div class="space-y-6">
        <div class="bg-slate-900 border border-slate-800 rounded-2xl p-5 space-y-4">
            <div class="flex items-center justify-between">
                <span class="text-xs text-slate-400 font-bold uppercase">Сделка #{{ $order->id }}</span>
                <span id="order-status-badge" class="px-2.5 py-1 rounded text-xs font-bold {{ $order->status->badgeClasses() }}">
                    {{ $order->status->label() }}
                </span>
            </div>

            <div>
                <h1 class="text-lg font-extrabold text-white flex items-center gap-2">
                    <span>{{ $order->lot->title ?? 'Лот' }}</span>
                    @if($order->lot?->hasAutoDelivery())
                        <span class="px-1.5 py-0.5 text-[10px] font-extrabold rounded bg-emerald-500/20 text-emerald-300 border border-emerald-500/30">
                            ⚡ Автовыдача
                        </span>
                    @endif
                </h1>
                <p class="text-xs text-slate-400 mt-1">{{ $order->lot->description ?? '' }}</p>
            </div>

            <div class="p-3.5 rounded-xl bg-slate-950 border border-slate-800 space-y-2 text-sm">
                <div class="flex justify-between">
                    <span class="text-slate-400">Продавец:</span>
                    <a href="{{ route('users.show', $order->seller_id) }}" class="font-bold text-emerald-400 hover:underline">
                        {{ $order->seller->username }}
                    </a>
                </div>
                <div class="flex justify-between">
                    <span class="text-slate-400">Покупатель:</span>
                    <a href="{{ route('users.show', $order->buyer_id) }}" class="font-bold text-indigo-400 hover:underline">
                        {{ $order->buyer->username }}
                    </a>
                </div>
                <div class="flex justify-between">
                    <span class="text-slate-400">Количество:</span>
                    <span class="font-semibold text-slate-200">{{ $order->quantity }} шт.</span>
                </div>
                <div class="flex justify-between pt-2 border-t border-slate-800">
                    <span class="text-slate-400">Заморожено в гаранте:</span>
                    <span class="font-extrabold text-emerald-400">
                        {{ number_format((float) $order->total_price, 2, '.', ' ') }} ₽
                    </span>
                </div>
            </div>

            @if($order->status->isConfirmable())
                @can('confirm', $order)
                    <form action="{{ route('orders.confirm', $order) }}" method="POST" class="space-y-3 pt-2 border-t border-slate-800">
                        @csrf
                        <div class="text-xs text-slate-300 font-semibold">Товар получен? Подтвердите выполнение:</div>
                        <select name="rating" class="w-full bg-slate-800 border border-slate-700 rounded-lg px-3 py-2 text-xs text-amber-400 font-bold">
                            <option value="5">★★★★★ Отлично (5)</option>
                            <option value="4">★★★★☆ Хорошо (4)</option>
                            <option value="3">★★★☆☆ Нормально (3)</option>
                            <option value="2">★★☆☆☆ Плохо (2)</option>
                            <option value="1">★☆☆☆☆ Ужасно (1)</option>
                        </select>
                        <textarea name="comment" rows="2" placeholder="Отзыв продавцу..." class="w-full bg-slate-800 border border-slate-700 rounded-lg px-3 py-2 text-xs text-white"></textarea>
                        <button type="submit" class="w-full py-2.5 rounded-xl bg-emerald-500 hover:bg-emerald-400 text-slate-950 font-extrabold text-xs transition">
                            ✓ Подтвердить и перевести деньги продавцу
                        </button>
                    </form>
                @endcan

                @can('cancel', $order)
                    @if($user->id === $order->seller_id)
                        <div class="p-3 rounded-xl bg-amber-500/10 border border-amber-500/20 text-xs text-amber-300">
                            Вы продавец. Передайте товар покупателю в чате справа.
                        </div>
                    @endif
                    <form action="{{ route('orders.cancel', $order) }}" method="POST" class="pt-2 border-t border-slate-800">
                        @csrf
                        <button type="submit" class="w-full py-2 rounded-xl bg-rose-500/10 hover:bg-rose-500/20 border border-rose-500/30 text-rose-300 font-bold text-xs transition">
                            ↺ Отменить заказ и вернуть деньги покупателю
                        </button>
                    </form>
                @endcan

                @if($order->status->isDisputable())
                    @can('dispute', $order)
                        <form action="{{ route('orders.dispute', $order) }}" method="POST" class="space-y-2 pt-3 border-t border-slate-800">
                            @csrf
                            <div class="text-xs text-rose-300 font-semibold">Возникла проблема со сделкой?</div>
                            <input type="text" name="reason" required placeholder="Укажите причину спора для арбитра..." class="w-full bg-slate-800 border border-slate-700 rounded-lg px-3 py-2 text-xs text-white">
                            <button type="submit" class="w-full py-2 rounded-xl bg-rose-500/20 hover:bg-rose-500/30 border border-rose-500/40 text-rose-200 font-bold text-xs transition">
                                ⚖️ Открыть спор (Вызвать арбитра)
                            </button>
                        </form>
                    @endcan
                @endif

                @if($order->status === \App\Enums\OrderStatus::Disputed)
                    @can('resolve', $order)
                        <form action="{{ route('orders.resolve', $order) }}" method="POST" class="space-y-2 pt-3 border-t border-rose-500/40">
                            @csrf
                            <div class="text-xs text-rose-300 font-extrabold uppercase">⚖️ Вердикт Арбитра SportPay:</div>
                            <input type="text" name="comment" placeholder="Комментарий арбитра..." class="w-full bg-slate-800 border border-slate-700 rounded-lg px-3 py-2 text-xs text-white">
                            <div class="grid grid-cols-2 gap-2">
                                <button type="submit" name="decision" value="refund_buyer" class="py-2 rounded-xl bg-rose-500 hover:bg-rose-400 text-white font-extrabold text-[11px]">
                                    ↺ Возврат покупателю
                                </button>
                                <button type="submit" name="decision" value="release_seller" class="py-2 rounded-xl bg-emerald-500 hover:bg-emerald-400 text-slate-950 font-extrabold text-[11px]">
                                    ✓ Выплата продавцу
                                </button>
                            </div>
                        </form>
                    @endcan
                @endif
            @endif

            @if($order->review)
                <div class="p-3.5 rounded-xl bg-slate-800/70 border border-slate-700 text-xs space-y-1">
                    <div class="text-amber-400 font-bold">
                        Отзыв покупателя: {{ str_repeat('★', $order->review->rating) }}
                    </div>
                    <div class="text-slate-200">«{{ $order->review->comment }}»</div>
                </div>
            @endif
        </div>
    </div>

    <!-- Правая колонка: Real-time Чат по сделке -->
    <div class="lg:col-span-2 bg-slate-900 border border-slate-800 rounded-2xl p-5 flex flex-col justify-between min-h-[540px]">
        <div>
            <div class="flex items-center justify-between pb-3 mb-4 border-b border-slate-800">
                <div class="flex items-center gap-2">
                    <h2 class="text-base font-bold text-white">Чат по сделке #{{ $order->id }}</h2>
                    <span id="ws-status" class="w-2 h-2 rounded-full bg-emerald-400 animate-pulse" title="Real-time подключение активно"></span>
                </div>
                <span class="text-xs text-slate-400">Гарант безопасности SportPay</span>
            </div>

            <div id="order-messages-box" class="space-y-3 max-h-[390px] overflow-y-auto pr-2">
                @if($order->messages->isEmpty())
                    <div id="empty-order-chat" class="text-center text-sm text-slate-500 py-12">Сообщений пока нет.</div>
                @else
                    @foreach($order->messages as $msg)
                        @if($msg->is_system)
                            <div class="p-3 rounded-xl bg-amber-500/10 border border-amber-500/20 text-center text-xs text-amber-300">
                                🛡️ {{ $msg->body }}
                            </div>
                        @else
                            @php $isMe = $msg->user_id === $user->id; @endphp
                            <div class="flex flex-col {{ $isMe ? 'items-end' : 'items-start' }}" data-msg-id="{{ $msg->id }}">
                                <div class="text-[11px] text-slate-400 mb-1">
                                    <a href="{{ route('users.show', $msg->user_id) }}" class="font-bold hover:underline">{{ $msg->user->username ?? 'Юзер' }}</a>
                                    • {{ $msg->created_at?->format('H:i') }}
                                </div>
                                <div class="max-w-md px-4 py-2.5 rounded-2xl text-sm {{ $isMe ? 'bg-emerald-500 text-slate-950 font-medium' : 'bg-slate-800 text-slate-100 border border-slate-700' }}">
                                    {{ $msg->body }}
                                </div>
                            </div>
                        @endif
                    @endforeach
                @endif
            </div>
        </div>

        <form id="order-chat-form" action="{{ route('orders.messages.store', $order) }}" method="POST" class="mt-4 pt-3 border-t border-slate-800 flex gap-2">
            @csrf
            <input type="text" id="order-chat-input" name="body" required autocomplete="off" placeholder="Написать сообщение по сделке..." class="flex-1 bg-slate-800 border border-slate-700 rounded-xl px-4 py-2.5 text-sm text-white focus:outline-none focus:border-emerald-500">
            <button type="submit" class="px-5 py-2.5 rounded-xl bg-emerald-500 hover:bg-emerald-400 text-slate-950 font-extrabold text-xs transition">
                Отправить
            </button>
        </form>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const box = document.getElementById('order-messages-box');
    const form = document.getElementById('order-chat-form');
    const input = document.getElementById('order-chat-input');
    const currentUserId = {{ (int) $user->id }};
    const orderId = {{ (int) $order->id }};

    box.scrollTop = box.scrollHeight;

    function renderOrderMessage(msg) {
        if (msg.id && box.querySelector(`[data-msg-id="${msg.id}"]`)) {
            return;
        }

        const emptyPlaceholder = document.getElementById('empty-order-chat');
        if (emptyPlaceholder) emptyPlaceholder.remove();

        const wrapper = document.createElement('div');

        if (msg.is_system) {
            wrapper.className = 'p-3 rounded-xl bg-amber-500/10 border border-amber-500/20 text-center text-xs text-amber-300';
            wrapper.textContent = '🛡️ ' + msg.body;
        } else {
            const isMe = Number(msg.user_id) === currentUserId;
            wrapper.className = `flex flex-col ${isMe ? 'items-end' : 'items-start'}`;
            if (msg.id) wrapper.setAttribute('data-msg-id', msg.id);

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
        box.scrollTop = box.scrollHeight;
    }

    // Отправка сообщения через AJAX без перезагрузки страницы
    form.addEventListener('submit', async function (e) {
        e.preventDefault();
        const body = input.value.trim();
        if (!body) return;

        input.value = '';

        try {
            const response = await fetch(form.action, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': '{{ csrf_token() }}',
                    'Accept': 'application/json'
                },
                body: JSON.stringify({ body })
            });

            if (response.ok) {
                const data = await response.json();
                if (data.message) {
                    renderOrderMessage(data.message);
                }
            }
        } catch (err) {
            console.error('Ошибка отправки:', err);
        }
    });

    // Подписка на WebSocket-канал заказа (Laravel Reverb / Pusher)
    if (window.Echo) {
        window.Echo.channel(`orders.${orderId}`)
            .listen('.order.message.sent', (data) => {
                renderOrderMessage(data);
            });
    }
});
</script>
@endsection