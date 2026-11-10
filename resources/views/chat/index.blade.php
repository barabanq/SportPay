@extends('layouts.app')

@section('content')
<div class="mb-4 p-3.5 rounded-2xl bg-slate-900 border border-slate-800 flex flex-wrap items-center justify-between gap-3 text-xs">
    <div class="flex items-center gap-2 text-slate-300">
        <span class="text-base">💬</span>
        <span class="font-bold text-white">{{ __('Диалоги по сделкам') }}</span>
    </div>
    <div class="flex items-center gap-2">
        <button type="button" onclick="window.SportPaySound && window.SportPaySound.playMessage()" class="px-3 py-1.5 rounded-xl bg-slate-800 hover:bg-slate-700 border border-slate-700 text-emerald-300 font-bold text-[11px] transition">
            🔔 {{ __('Звук сообщения') }}
        </button>
        <button type="button" onclick="window.SportPaySound && window.SportPaySound.playPurchase()" class="px-3 py-1.5 rounded-xl bg-slate-800 hover:bg-slate-700 border border-slate-700 text-amber-300 font-bold text-[11px] transition">
            💰 {{ __('Звук покупки') }}
        </button>
    </div>
</div>

<div class="grid grid-cols-1 lg:grid-cols-12 gap-5 bg-slate-900 border border-slate-800 rounded-2xl overflow-hidden min-h-[540px]">
    <!-- Список собеседников -->
    <div class="lg:col-span-4 border-b lg:border-b-0 lg:border-r border-slate-800 bg-slate-950/50 flex flex-col">
        <div class="p-4 border-b border-slate-800 flex items-center justify-between">
            <h2 class="text-sm font-extrabold text-white">{{ __('Собеседники') }}</h2>
            <span class="px-2 py-0.5 text-[11px] rounded-full bg-slate-800 text-slate-300 font-bold">
                {{ $partners->count() }}
            </span>
        </div>

        <div class="divide-y divide-slate-800/70 overflow-y-auto max-h-[480px]">
            @forelse($partners as $partner)
                @php
                    $isActive = $activePartner && (int) $activePartner->id === (int) $partner->id;
                    $online = $partner->isOnline();
                @endphp
                <a href="{{ route('chat.index', ['partner' => $partner->id]) }}"
                   class="flex items-center justify-between p-3.5 hover:bg-slate-800/50 transition {{ $isActive ? 'bg-emerald-500/10 border-l-4 border-emerald-400' : '' }}">
                    <div class="flex items-center gap-3 min-w-0">
                        <div class="relative shrink-0">
                            <div class="w-9 h-9 rounded-xl bg-slate-800 border border-slate-700 flex items-center justify-center text-xs font-extrabold text-emerald-400">
                                {{ strtoupper(substr($partner->username, 0, 1)) }}
                            </div>
                            <span class="absolute -bottom-0.5 -right-0.5 w-2.5 h-2.5 rounded-full border-2 border-slate-950 {{ $online ? 'bg-emerald-400' : 'bg-slate-600' }}"></span>
                        </div>
                        <div class="min-w-0">
                            <div class="font-bold text-sm text-white truncate">{{ $partner->username }}</div>
                            <div class="text-[11px] {{ $online ? 'text-emerald-400' : 'text-slate-500' }}">
                                {{ $online ? __('Онлайн') : __('Офлайн') }} • ★ {{ number_format((float) $partner->rating, 2) }}
                            </div>
                        </div>
                    </div>
                    <span class="text-xs text-slate-500">→</span>
                </a>
            @empty
                <div class="p-6 text-center text-xs text-slate-500">
                    {{ __('У вас пока нет активных диалогов по сделкам.') }}
                </div>
            @endforelse
        </div>
    </div>

    <!-- Активный диалог -->
    <div class="lg:col-span-8 flex flex-col justify-between bg-slate-900">
        @if($activePartner)
            <div class="p-4 border-b border-slate-800 bg-slate-950/40 flex flex-wrap items-center justify-between gap-3">
                <div class="flex items-center gap-3">
                    <div class="w-9 h-9 rounded-xl bg-slate-800 border border-slate-700 flex items-center justify-center text-xs font-extrabold text-emerald-400">
                        {{ strtoupper(substr($activePartner->username, 0, 1)) }}
                    </div>
                    <div>
                        <div class="font-extrabold text-white text-sm flex items-center gap-2">
                            <span>{{ $activePartner->username }}</span>
                            <span class="text-[11px] font-semibold {{ $activePartner->isOnline() ? 'text-emerald-400' : 'text-slate-400' }}">
                                ● {{ $activePartner->isOnline() ? __('Онлайн') : __('Офлайн') }}
                            </span>
                        </div>
                        <div class="text-[11px] text-amber-400">★ {{ number_format((float) $activePartner->rating, 2) }}</div>
                    </div>
                </div>

                @if($sharedOrders->isNotEmpty())
                    <div class="flex flex-wrap items-center gap-1.5">
                        @foreach($sharedOrders->take(3) as $ord)
                            <a href="{{ route('orders.show', $ord) }}" class="px-2.5 py-1 rounded-lg bg-indigo-500/15 hover:bg-indigo-500/25 border border-indigo-500/30 text-[11px] text-indigo-300 font-bold transition">
                                📦 {{ __('Заказ') }} #{{ $ord->id }}
                            </a>
                        @endforeach
                    </div>
                @endif
            </div>

            <div id="chat-messages-box"
                 data-partner-id="{{ $activePartner->id }}"
                 data-last-id="{{ $messages->last()?->id ?? 0 }}"
                 class="flex-1 p-4 space-y-3 overflow-y-auto max-h-[400px]">
                @forelse($messages as $msg)
                    @php
                        $isMine = (int) $msg->user_id === (int) $user->id;
                    @endphp
                    <div class="chat-msg-item flex {{ $isMine ? 'justify-end' : 'justify-start' }}" data-msg-id="{{ $msg->id }}">
                        <div class="max-w-[80%] rounded-2xl px-3.5 py-2.5 text-xs space-y-1 {{ $msg->is_system ? 'bg-amber-500/15 border border-amber-500/30 text-amber-200' : ($isMine ? 'bg-emerald-500 text-slate-950 font-medium' : 'bg-slate-800 text-slate-100 border border-slate-700') }}">
                            <div class="flex items-center justify-between gap-3 text-[10px] {{ $isMine ? 'text-slate-900/75 font-bold' : 'text-slate-400' }}">
                                <span>{{ $msg->is_system ? '⚡ SportPay' : ($msg->user?->username ?? 'User') }}</span>
                                <span>#{{ $msg->order_id }} • {{ $msg->created_at?->format('H:i') }}</span>
                            </div>
                            <div class="whitespace-pre-line break-words leading-relaxed">{{ $msg->body }}</div>
                        </div>
                    </div>
                @empty
                    <div id="chat-empty-hint" class="py-12 text-center text-xs text-slate-500">
                        {{ __('Сообщений по сделкам с этим пользователем пока нет.') }}
                    </div>
                @endforelse
            </div>

            @if($sharedOrders->isNotEmpty())
                <form id="chat-send-form" action="{{ route('chat.send') }}" method="POST" class="p-3.5 border-t border-slate-800 bg-slate-950/60 flex flex-wrap sm:flex-nowrap items-center gap-2">
                    @csrf
                    <input type="hidden" name="partner_id" value="{{ $activePartner->id }}">
                    @if($sharedOrders->count() > 1)
                        <select name="order_id" class="bg-slate-900 border border-slate-700 rounded-xl px-2.5 py-2.5 text-xs text-slate-200">
                            @foreach($sharedOrders as $ord)
                                <option value="{{ $ord->id }}">#{{ $ord->id }} — {{ \Illuminate\Support\Str::limit($ord->lot?->title ?? 'Заказ', 22) }}</option>
                            @endforeach
                        </select>
                    @else
                        <input type="hidden" name="order_id" value="{{ $sharedOrders->first()->id }}">
                    @endif
                    <input type="text"
                           id="chat-body-input"
                           name="body"
                           required
                           autocomplete="off"
                           placeholder="{{ __('Напишите сообщение по сделке...') }}"
                           class="flex-1 bg-slate-900 border border-slate-700 rounded-xl px-3.5 py-2.5 text-xs text-white focus:outline-none focus:border-emerald-500">
                    <button type="submit" class="px-5 py-2.5 rounded-xl bg-emerald-500 hover:bg-emerald-400 text-slate-950 font-extrabold text-xs transition shrink-0">
                        {{ __('Отправить') }} ➤
                    </button>
                </form>
            @else
                <div class="p-4 border-t border-slate-800 bg-slate-950/60 text-center text-xs text-slate-400">
                    {{ __('Чат открывается автоматически после оформления заказа по любому лоту продавца.') }}
                </div>
            @endif
        @else
            <div class="flex-1 flex items-center justify-center p-8 text-center text-slate-500 text-xs">
                {{ __('Выберите собеседника слева.') }}
            </div>
        @endif
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const box = document.getElementById('chat-messages-box');
    const form = document.getElementById('chat-send-form');
    const input = document.getElementById('chat-body-input');

    if (box) {
        box.scrollTop = box.scrollHeight;
    }

    function appendMessage(m) {
        if (!box || box.querySelector(`[data-msg-id="${m.id}"]`)) return;
        document.getElementById('chat-empty-hint')?.remove();

        const row = document.createElement('div');
        row.className = 'chat-msg-item flex ' + (m.is_mine ? 'justify-end' : 'justify-start');
        row.setAttribute('data-msg-id', m.id);

        const bubbleClass = m.is_system
            ? 'bg-amber-500/15 border border-amber-500/30 text-amber-200'
            : (m.is_mine ? 'bg-emerald-500 text-slate-950 font-medium' : 'bg-slate-800 text-slate-100 border border-slate-700');
        const metaClass = m.is_mine ? 'text-slate-900/75 font-bold' : 'text-slate-400';

        row.innerHTML = `
            <div class="max-w-[80%] rounded-2xl px-3.5 py-2.5 text-xs space-y-1 ${bubbleClass}">
                <div class="flex items-center justify-between gap-3 text-[10px] ${metaClass}">
                    <span>${m.sender_name}</span>
                    <span>#${m.order_id || ''} • ${m.time || ''}</span>
                </div>
                <div class="msg-body whitespace-pre-line break-words leading-relaxed"></div>
            </div>
        `;
        row.querySelector('.msg-body').textContent = m.body;
        box.appendChild(row);
        box.dataset.lastId = String(m.id);
        box.scrollTop = box.scrollHeight;
    }

    if (form && box && input) {
        form.addEventListener('submit', async function (e) {
            e.preventDefault();
            if (!input.value.trim()) return;

            const formData = new FormData(form);
            input.value = '';

            try {
                const res = await fetch(form.action, {
                    method: 'POST',
                    headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
                    body: formData
                });
                if (res.ok) {
                    const data = await res.json();
                    if (data && data.message) appendMessage(data.message);
                } else {
                    form.submit();
                }
            } catch (err) {
                form.submit();
            }
        });
    }

    window.appendLiveChatMessages = function (list) {
        if (!Array.isArray(list)) return;
        list.forEach(m => appendMessage(m));
    };
});
</script>
@endsection
