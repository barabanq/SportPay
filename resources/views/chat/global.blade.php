@extends('layouts.app')

@section('title', 'Общий чат — SportPay')

@section('content')
<div class="max-w-4xl mx-auto bg-slate-900 border border-slate-800 rounded-2xl overflow-hidden flex flex-col h-[74vh]">
    <div class="p-4 border-b border-slate-800 bg-slate-900/80 flex items-center justify-between">
        <h1 class="text-base font-extrabold text-white flex items-center gap-2">
            🌍 Общий спортивный чат SportPay
        </h1>
        <div class="flex items-center gap-2">
            <span class="w-2.5 h-2.5 rounded-full bg-emerald-400 animate-pulse"></span>
            <span class="text-xs text-slate-400">Живой эфир</span>
        </div>
    </div>

    <!-- Лента сообщений -->
    <div id="chat-messages" style="overflow-y: auto; scroll-behavior: auto;" class="flex-1 p-4 space-y-3 overflow-y-auto">
        @if($messages->isEmpty())
            <div id="no-messages" class="text-center text-sm text-slate-500 py-12">
                Сообщений пока нет. Напишите первым!
            </div>
        @else
            @foreach($messages as $msg)
                @php $isMe = $msg->user_id === $user->id; @endphp
                <div class="flex flex-col {{ $isMe ? 'items-end' : 'items-start' }}" data-msg-id="{{ $msg->id }}">
                    <div class="text-[11px] text-slate-400 mb-1">
                        <span class="font-bold text-slate-300">{{ $msg->user->username ?? 'Юзер' }}</span>
                        • {{ $msg->created_at?->format('H:i') }}
                    </div>
                    <div class="max-w-lg px-4 py-2.5 rounded-2xl text-sm {{ $isMe ? 'bg-emerald-500 text-slate-950 font-medium' : 'bg-slate-800 text-slate-100 border border-slate-700' }}">
                        {{ $msg->body }}
                    </div>
                </div>
            @endforeach
        @endif
    </div>

    <!-- Форма отправки -->
    <form id="chat-form" class="p-3 border-t border-slate-800 bg-slate-900/80 flex gap-2">
        @csrf
        <input type="text" id="chat-input" name="body" required autocomplete="off" placeholder="Напишите сообщение в общий чат..." class="flex-1 bg-slate-800 border border-slate-700 rounded-xl px-4 py-2.5 text-sm text-white focus:outline-none focus:border-emerald-500">
        <button type="submit" class="px-6 py-2.5 rounded-xl bg-emerald-500 hover:bg-emerald-400 text-slate-950 font-extrabold text-xs transition">
            Отправить
        </button>
    </form>
</div>

<script>
(function () {
    const chatMessages = document.getElementById('chat-messages');
    const chatForm = document.getElementById('chat-form');
    const chatInput = document.getElementById('chat-input');
    const currentUserId = {{ (int) $user->id }};
    const pollUrl = "{{ route('chat.global.messages') }}";

    let lastMessageId = {{ (int) ($messages->last()?->id ?? 0) }};

    function forceScrollBottom() {
        if (!chatMessages) return;
        chatMessages.scrollTop = chatMessages.scrollHeight + 9999;
    }

    forceScrollBottom();
    window.addEventListener('load', () => {
        forceScrollBottom();
        setTimeout(forceScrollBottom, 120);
    });

    function appendGlobalMessage(msg) {
        const msgId = Number(msg.id || 0);
        if (msgId > 0 && chatMessages.querySelector(`[data-msg-id="${msgId}"]`)) {
            return;
        }
        if (msgId > lastMessageId) {
            lastMessageId = msgId;
        }

        const noMsg = document.getElementById('no-messages');
        if (noMsg) noMsg.remove();

        const isMe = Number(msg.user_id) === currentUserId;
        const wrapper = document.createElement('div');
        wrapper.className = `flex flex-col ${isMe ? 'items-end' : 'items-start'}`;
        if (msgId > 0) wrapper.setAttribute('data-msg-id', String(msgId));

        const meta = document.createElement('div');
        meta.className = 'text-[11px] text-slate-400 mb-1';
        meta.innerHTML = `<span class="font-bold text-slate-300"></span> • ${msg.time}`;
        meta.querySelector('span').textContent = msg.username;

        const bubble = document.createElement('div');
        bubble.className = `max-w-lg px-4 py-2.5 rounded-2xl text-sm ${isMe ? 'bg-emerald-500 text-slate-950 font-medium' : 'bg-slate-800 text-slate-100 border border-slate-700'}`;
        bubble.textContent = msg.body;

        wrapper.appendChild(meta);
        wrapper.appendChild(bubble);

        chatMessages.appendChild(wrapper);
        forceScrollBottom();
    }

    chatForm.addEventListener('submit', async function (e) {
        e.preventDefault();
        const body = chatInput.value.trim();
        if (!body) return;

        chatInput.value = '';
        chatInput.focus();

        try {
            const response = await fetch("{{ route('chat.global.store') }}", {
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
                if (data.message) appendGlobalMessage(data.message);
            }
        } catch (err) {
            console.error('Ошибка отправки:', err);
        }
    });

    if (window.Echo) {
        window.Echo.channel('public-chat')
            .listen('.message.sent', (data) => appendGlobalMessage(data));
    }

    setInterval(async () => {
        try {
            const res = await fetch(`${pollUrl}?after_id=${lastMessageId}`, {
                headers: { 'Accept': 'application/json' }
            });
            if (res.ok) {
                const data = await res.json();
                if (Array.isArray(data.messages)) {
                    data.messages.forEach(appendGlobalMessage);
                }
            }
        } catch (e) {}
    }, 2000);
})();
</script>
@endsection