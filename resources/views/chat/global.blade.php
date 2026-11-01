@extends('layouts.app')

@section('title', 'Общий чат — SportPay')

@section('content')
<div class="max-w-4xl mx-auto bg-slate-900 border border-slate-800 rounded-2xl overflow-hidden flex flex-col h-[70vh]">
    <div class="p-4 border-b border-slate-800 bg-slate-900/80 flex items-center justify-between">
        <h1 class="text-base font-extrabold text-white flex items-center gap-2">
            🌍 Общий спортивный чат SportPay
        </h1>
        <span class="text-xs text-slate-400">Живое общение участников биржи</span>
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
                        <a href="{{ route('users.show', $msg->user_id) }}" class="font-bold hover:underline text-slate-300">
                            {{ $msg->user->username ?? 'Юзер' }}
                        </a>
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

    // Прокрутка чата вниз при загрузке
    chatMessages.scrollTop = chatMessages.scrollHeight;

    function appendMessage(username, body, time, isMe, userId) {
        const noMsg = document.getElementById('no-messages');
        if (noMsg) noMsg.remove();

        const wrapper = document.createElement('div');
        wrapper.className = `flex flex-col ${isMe ? 'items-end' : 'items-start'}`;

        wrapper.innerHTML = `
            <div class="text-[11px] text-slate-400 mb-1">
                <span class="font-bold text-slate-300">${username}</span> • ${time}
            </div>
            <div class="max-w-lg px-4 py-2.5 rounded-2xl text-sm ${isMe ? 'bg-emerald-500 text-slate-950 font-medium' : 'bg-slate-800 text-slate-100 border border-slate-700'}">
                ${body}
            </div>
        `;

        chatMessages.appendChild(wrapper);
        chatMessages.scrollTop = chatMessages.scrollHeight;
    }

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
                appendMessage(data.message.user.username, data.message.body, data.message.created_at, true, data.message.user.id);
            }
        } catch (err) {
            console.error('Ошибка отправки сообщения:', err);
        }
    });
});
</script>
@endsection