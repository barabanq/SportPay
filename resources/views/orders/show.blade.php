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
            bubble.className = `max-w-md px-4 py-2.5 rounded-2xl text-sm ${isMe ? 'bg-emerald-500 text-slate-950 font-medium' : 'bg-slate-800 text-slate-100 border border-slate-700'}`;
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
            console.error('Ошибка отправки:', err);
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