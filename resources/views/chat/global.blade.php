@extends('layouts.app')

@section('title', 'Общий чат — SportPay')

@section('content')
<div class="max-w-4xl mx-auto bg-slate-900 border border-slate-800 rounded-2xl overflow-hidden flex flex-col h-[70vh]">
    <div class="p-4 border-b border-slate-800 bg-slate-900/80 flex items-center justify-between">
        <h1 class="text-base font-extrabold text-white flex items-center gap-2">
            🌍 Общий спортивный чат SportPay
        </h1>
        <div class="flex items-center gap-2">
            <span id="ws-indicator" class="w-2.5 h-2.5 rounded-full bg-rose-500" title="WebSocket отключен"></span>
            <span id="ws-text" class="text-xs text-slate-400">Подключение к сокету...</span>
        </div>
    </div>

    <!-- Лента сообщений -->
    <div id="chat-messages" class="flex-1 p-4 space-y-3 overflow-y-auto">
        @if($messages->isEmpty())
            <div id="no-messages" class="text-center text-sm text-slate-500 py-12">
                Сообщений пока нет. Напишите первым!
            </div>
        @else
            @foreach($messages as $msg)
                @php $isMe = $msg->user_id === $user->id; @endphp
                <div class="flex flex-col {{ $isMe ? 'items-end' : 'items-start' }}">
                    <div class="text-[11px] text-slate-400 mb-1">
                        <span class="font-bold text-slate-300">{{ $msg->user->username ?? 'Юзер' }}</span>
                        • {{ $msg->created_at->format('H:i') }}
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
document.addEventListener('DOMContentLoaded', function () {
    const chatMessages = document.getElementById('chat-messages');
    const chatForm = document.getElementById('chat-form');
    const chatInput = document.getElementById('chat-input');
    const wsIndicator = document.getElementById('ws-indicator');
    const wsText = document.getElementById('ws-text');
    const currentUserId = {{ (int) $user->id }};

    // Жесткая прокрутка вниз через анимационный фрейм браузера
    function scrollToBottom() {
        requestAnimationFrame(() => {
            chatMessages.scrollTop = chatMessages.scrollHeight;
        });
    }

    // Скроллим при старте
    scrollToBottom();

    // Индикатор статуса WebSocket
    if (window.Echo) {
        window.Echo.connector.pusher.connection.bind('connected', () => {
            wsIndicator.className = 'w-2.5 h-2.5 rounded-full bg-emerald-400 animate-pulse';
            wsText.textContent = 'Reverb активен';
        });
        window.Echo.connector.pusher.connection.bind('disconnected', () => {
            wsIndicator.className = 'w-2.5 h-2.5 rounded-full bg-amber-500';
            wsText.textContent = 'Соединение потеряно';
        });
    }

    function appendMessage(username, body, time, isMe) {
        const noMsg = document.getElementById('no-messages');
        if (noMsg) noMsg.remove();

        const wrapper = document.createElement('div');
        wrapper.className = `flex flex-col ${isMe ? 'items-end' : 'items-start'}`;

        wrapper.innerHTML = `
            <div class="text-[11px] text-slate-400 mb-1">
                <span class="font-bold text-slate-300">${username}</span> • ${time}
            </div>
            <div class="max-w-lg px-4 py-2.5 rounded-2xl text-sm ${isMe ? 'bg-emerald-500 text-slate-950 font-medium' : 'bg-slate-800 text-slate-100 border border-slate-700'}`}>
                ${body}
            </div>
        `;

        chatMessages.appendChild(wrapper);
        scrollToBottom(); // Всегда вниз при новом сообщении
    }

    // Отправка через AJAX
    chatForm.addEventListener('submit', async function (e) {
        e.preventDefault();
        const body = chatInput.value.trim();
        if (!body) return;

        chatInput.value = '';

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

            const data = await response.json();
            if (data.status === 'success') {
                appendMessage(data.message.user.username, data.message.body, data.message.time, true);
            }
        } catch (err) {
            console.error('Ошибка отправки:', err);
        }
    });

    // Получение сокетов
    if (window.Echo) {
        window.Echo.channel('public-chat')
            .listen('.message.sent', (data) => {
                if (Number(data.user_id) !== currentUserId) {
                    appendMessage(data.username, data.body, data.time, false);
                }
            });
    }
});
</script>
@endsection