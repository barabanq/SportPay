@extends('layouts.app')

@section('title', __('Сделка') . " #{$order->id} — SportPay")

@section('content')
<div class="mb-2 flex flex-wrap items-center justify-between gap-2">
    <a href="{{ route('home') }}" class="text-xs text-slate-400 hover:text-emerald-400 transition">
        {{ __('← На главную витрину') }}
    </a>
    <a href="{{ route('chat.index', ['order_id' => $order->id]) }}" class="text-xs text-indigo-400 hover:underline font-semibold">
        {{ __('💬 Открыть в разделе всех диалогов →') }}
    </a>
</div>

<div class="grid grid-cols-1 lg:grid-cols-3 gap-5 sm:gap-6">
    <!-- Левая колонка: Детали сделки и управление эскроу -->
    <div class="space-y-5 sm:space-y-6">
        <div class="bg-slate-900 border border-slate-800 rounded-2xl p-4 sm:p-6 space-y-4">
            <div class="flex items-center justify-between">
                <span class="text-xs text-slate-400 font-mono">{{ __('Сделка') }} #{{ $order->id }}</span>
                <span class="text-xs font-extrabold px-2.5 py-1 rounded {{ $order->status->badgeClasses() }}">
                    {{ $order->status->label() }}
                </span>
            </div>

            <div>
                <a href="{{ route('lots.show', $order->lot_id) }}" class="text-base sm:text-lg font-extrabold text-white hover:text-emerald-400 transition">
                    {{ $order->lot->title ?? 'Lot' }}
                </a>
                <div class="text-xs text-slate-400 mt-1">
                    {{ __('Количество:') }} {{ $order->quantity }} {{ __('шт.') }}
                </div>
            </div>

            <div class="p-3.5 rounded-xl bg-slate-950 border border-slate-800 flex items-center justify-between">
                <span class="text-xs text-slate-400">{{ __('Сумма в эскроу') }}</span>
                <span class="text-lg font-extrabold text-emerald-400">
                    {{ number_format((float) $order->total_price, 2, '.', ' ') }} ₽
                </span>
            </div>

            <div class="space-y-2 text-xs pt-2 border-t border-slate-800">
                <div class="flex justify-between">
                    <span class="text-slate-400">{{ __('Покупатель:') }}</span>
                    <a href="{{ route('users.show', $order->buyer_id) }}" class="font-bold text-indigo-400 hover:underline">
                        {{ $order->buyer->username ?? '-' }}
                    </a>
                </div>
                <div class="flex justify-between">
                    <span class="text-slate-400">{{ __('Продавец:') }}</span>
                    <a href="{{ route('users.show', $order->seller_id) }}" class="font-bold text-emerald-400 hover:underline">
                        {{ $order->seller->username ?? '-' }}
                    </a>
                </div>
            </div>
        </div>

        <!-- Подтверждение заказа покупателем -->
        @if($order->buyer_id === $user->id && $order->status->isConfirmable())
            <div class="bg-slate-900 border border-emerald-500/30 rounded-2xl p-4 sm:p-6 space-y-4">
                <h2 class="text-sm font-extrabold uppercase tracking-wider text-emerald-400">
                    {{ __('Подтвердить выполнение') }}
                </h2>
                <p class="text-xs text-slate-400">
                    {{ __('Нажмите кнопку ниже только после того, как продавец полностью передал товар или оказал услугу.') }}
                </p>
                <form action="{{ route('orders.confirm', $order) }}" method="POST" class="space-y-3">
                    @csrf
                    <div>
                        <label class="block text-xs text-slate-400 mb-1">{{ __('Оценка продавцу') }}</label>
                        <select name="rating" class="w-full bg-slate-950 border border-slate-800 rounded-xl px-3 py-2 text-xs text-amber-400 font-bold">
                            <option value="5">★★★★★ (5)</option>
                            <option value="4">★★★★☆ (4)</option>
                            <option value="3">★★★☆☆ (3)</option>
                            <option value="2">★★☆☆☆ (2)</option>
                            <option value="1">★☆☆☆☆ (1)</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-xs text-slate-400 mb-1">{{ __('Отзыв о сделке') }}</label>
                        <textarea name="comment" rows="2" placeholder="{{ __('Все прошло быстро и качественно!') }}" class="w-full bg-slate-950 border border-slate-800 rounded-xl px-3 py-2 text-xs text-white"></textarea>
                    </div>
                    <button type="submit" class="w-full py-2.5 rounded-xl bg-emerald-500 hover:bg-emerald-400 text-slate-950 font-extrabold text-xs transition">
                        {{ __('✓ Подтвердить получение и перевести средства') }}
                    </button>
                </form>
            </div>
        @endif

        <!-- Возврат средств продавцом -->
        @if(($order->seller_id === $user->id || $user->isArbiter()) && $order->status->isConfirmable())
            <div class="bg-slate-900 border border-slate-800 rounded-2xl p-4 sm:p-5 space-y-3">
                <h3 class="text-xs font-extrabold uppercase tracking-wider text-slate-300">{{ __('Управление продавца') }}</h3>
                <form action="{{ route('orders.cancel', $order) }}" method="POST" onsubmit="return confirm('Cancel order and refund 100% to buyer?');">
                    @csrf
                    <button type="submit" class="w-full py-2.5 rounded-xl bg-rose-500/15 hover:bg-rose-500/25 border border-rose-500/30 text-rose-300 font-bold text-xs transition">
                        {{ __('↺ Отменить заказ и вернуть деньги покупателю') }}
                    </button>
                </form>
            </div>
        @endif

        <!-- Открытие арбитражного спора -->
        @if(($order->buyer_id === $user->id || $order->seller_id === $user->id) && $order->status->isDisputable())
            <div class="bg-slate-900 border border-slate-800 rounded-2xl p-4 sm:p-5 space-y-3">
                <h3 class="text-xs font-extrabold uppercase tracking-wider text-amber-400">{{ __('Проблема со сделкой?') }}</h3>
                <form action="{{ route('orders.dispute', $order) }}" method="POST" class="space-y-2.5">
                    @csrf
                    <input type="text" name="reason" required minlength="5" placeholder="{{ __('Опишите причину спора (мин. 5 символов)...') }}" class="w-full bg-slate-950 border border-slate-800 rounded-xl px-3 py-2 text-xs text-white">
                    <button type="submit" class="w-full py-2 rounded-xl bg-amber-500/20 hover:bg-amber-500/30 border border-amber-500/40 text-amber-300 font-bold text-xs transition">
                        {{ __('⚠️ Открыть спор (вызвать Арбитра)') }}
                    </button>
                </form>
            </div>
        @endif

        <!-- Панель решения спора для Арбитра -->
        @if($user->isArbiter() && $order->status === \App\Enums\OrderStatus::Disputed)
            <div class="bg-slate-900 border border-rose-500/40 rounded-2xl p-4 sm:p-5 space-y-3">
                <h3 class="text-xs font-extrabold uppercase tracking-wider text-rose-300">{{ __('⚖️ Решение Арбитра SportPay') }}</h3>
                <form action="{{ route('orders.resolve', $order) }}" method="POST" class="space-y-2.5">
                    @csrf
                    <input type="text" name="comment" placeholder="{{ __('Комментарий арбитра...') }}" class="w-full bg-slate-950 border border-slate-800 rounded-xl px-3 py-2 text-xs text-white">
                    <div class="grid grid-cols-1 gap-2">
                        <button type="submit" name="decision" value="refund_buyer" class="w-full py-2 rounded-xl bg-rose-500 hover:bg-rose-400 text-white font-extrabold text-xs">
                            {{ __('↺ Вернуть 100% покупателю') }}
                        </button>
                        <button type="submit" name="decision" value="release_seller" class="w-full py-2 rounded-xl bg-emerald-500 hover:bg-emerald-400 text-slate-950 font-extrabold text-xs">
                            {{ __('✓ Выплатить продавцу (-5%)') }}
                        </button>
                    </div>
                </form>
            </div>
        @endif
    </div>

    <!-- Правая колонка: Чат по сделке (адаптивная высота на смартфонах) -->
    <div class="lg:col-span-2 bg-slate-900 border border-slate-800 rounded-2xl flex flex-col justify-between h-[65vh] sm:h-[74vh] overflow-hidden">
        <div class="p-3.5 sm:p-4 border-b border-slate-800 flex items-center justify-between bg-slate-900/60">
            <div>
                <h2 class="font-extrabold text-white text-sm flex items-center gap-2">
                    💬 {{ __('Чат по сделке') }} #{{ $order->id }}
                    <span class="w-2 h-2 rounded-full bg-emerald-400 animate-pulse"></span>
                </h2>
                <p class="text-[11px] text-slate-400">{{ __('Все сообщения защищены и доступны арбитру при возникновении спора') }}</p>
            </div>
        </div>

        <div id="order-messages-box" style="overflow-y: auto; scroll-behavior: auto;" class="p-3.5 sm:p-5 space-y-3 flex-1 overflow-y-auto">
            @if($order->messages->isEmpty())
                <div id="empty-order-chat" class="text-center text-xs text-slate-500 py-12">
                    {{ __('Сообщений пока нет. Напишите продавцу!') }}
                </div>
            @else
                @foreach ($order->messages as $msg)
                    @if($msg->is_system)
                        <div class="p-3 rounded-xl bg-amber-500/10 border border-amber-500/20 text-center text-xs text-amber-300" data-msg-id="{{ $msg->id }}">
                            🛡️ {{ $msg->body }}
                        </div>
                    @else
                        @php $isMe = $msg->user_id === $user->id; @endphp
                        <div class="flex flex-col {{ $isMe ? 'items-end' : 'items-start' }}" data-msg-id="{{ $msg->id }}">
                            <div class="text-[11px] text-slate-400 mb-1">
                                {{ $msg->user->username ?? 'User' }} • {{ $msg->created_at?->format('H:i') }}
                            </div>
                            <div class="max-w-[85%] sm:max-w-md px-3.5 sm:px-4 py-2.5 rounded-2xl text-sm {{ $isMe ? 'bg-emerald-500 text-slate-950 font-medium' : 'bg-slate-800 text-slate-100 border border-slate-700' }}">
                                {{ $msg->body }}
                            </div>
                        </div>
                    @endif
                @endforeach
            @endif
        </div>

        <form id="order-chat-form" action="{{ route('orders.messages.store', $order) }}" method="POST" class="p-3 sm:p-4 border-t border-slate-800 flex gap-2 bg-slate-900/60">
            @csrf
            <input type="text" id="order-chat-input" name="body" required autocomplete="off" placeholder="{{ __('Написать сообщение в чат сделки...') }}" class="flex-1 bg-slate-800 border border-slate-700 rounded-xl px-3.5 py-2.5 text-sm text-white focus:outline-none focus:border-emerald-500">
            <button type="submit" class="px-4 sm:px-5 py-2.5 rounded-xl bg-emerald-500 hover:bg-emerald-400 text-slate-950 font-extrabold text-xs transition shrink-0">
                {{ __('Отправить') }}
            </button>
        </form>
    </div>
</div>

<script>
(function () {
    const box = document.getElementById('order-messages-box');
    const form = document.getElementById('order-chat-form');
    const input = document.getElementById('order-chat-input');
    const currentUserId = {{ (int) $user->id }};
    const orderId = {{ (int) $order->id }};
    const pollUrl = "{{ route('orders.messages.index', $order) }}";

    let lastMessageId = {{ (int) ($order->messages->last()?->id ?? 0) }};

    function forceScrollBottom() {
        if (!box) return;
        box.scrollTop = box.scrollHeight + 9999;
    }

    forceScrollBottom();
    window.addEventListener('load', () => {
        forceScrollBottom();
        setTimeout(forceScrollBottom, 120);
    });

    function renderOrderMessage(msg) {
        const msgId = Number(msg.id || 0);
        if (msgId > 0 && box.querySelector(`[data-msg-id="${msgId}"]`)) {
            return;
        }
        if (msgId > lastMessageId) {
            lastMessageId = msgId;
        }

        const emptyPlaceholder = document.getElementById('empty-order-chat');
        if (emptyPlaceholder) emptyPlaceholder.remove();

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
            bubble.className = `max-w-[85%] sm:max-w-md px-3.5 sm:px-4 py-2.5 rounded-2xl text-sm ${isMe ? 'bg-emerald-500 text-slate-950 font-medium' : 'bg-slate-800 text-slate-100 border border-slate-700'}`;
            bubble.textContent = msg.body;

            wrapper.appendChild(meta);
            wrapper.appendChild(bubble);
        }

        box.appendChild(wrapper);
        forceScrollBottom();
    }

    form.addEventListener('submit', async function (e) {
        e.preventDefault();
        const body = input.value.trim();
        if (!body) return;

        input.value = '';
        input.focus();

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
                if (data.message) renderOrderMessage(data.message);
            }
        } catch (err) {
            console.error('Error:', err);
        }
    });

    if (window.Echo) {
        window.Echo.channel(`orders.${orderId}`)
            .listen('.order.message.sent', (data) => renderOrderMessage(data));
    }

    setInterval(async () => {
        try {
            const res = await fetch(`${pollUrl}?after_id=${lastMessageId}`, {
                headers: { 'Accept': 'application/json' }
            });
            if (res.ok) {
                const data = await res.json();
                if (Array.isArray(data.messages)) {
                    data.messages.forEach(renderOrderMessage);
                }
            }
        } catch (e) {}
    }, 2000);
})();
</script>
@endsection
