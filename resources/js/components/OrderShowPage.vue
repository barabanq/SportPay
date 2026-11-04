<template>
    <div class="bg-slate-900 border border-slate-800 rounded-2xl flex flex-col justify-between h-[65vh] sm:h-[74vh] overflow-hidden">
        <div class="p-3.5 sm:p-4 border-b border-slate-800 flex items-center justify-between bg-slate-900/60">
            <h2 class="font-extrabold text-white text-sm flex items-center gap-2">
                💬 {{ title }} #{{ orderId }}
                <span class="w-2 h-2 rounded-full bg-emerald-400 animate-pulse"></span>
            </h2>
        </div>

        <div ref="messagesBox" class="p-3.5 sm:p-5 space-y-3 flex-1 overflow-y-auto">
            <div v-if="messages.length === 0" class="text-center text-xs text-slate-500 py-12">{{ emptyText }}</div>
            <template v-for="msg in messages" :key="msg.id">
                <div v-if="msg.is_system" class="p-3 rounded-xl bg-amber-500/10 border border-amber-500/20 text-center text-xs text-amber-300">
                    🛡️ {{ msg.body }}
                </div>
                <div v-else class="flex flex-col" :class="Number(msg.user_id) === currentUserId ? 'items-end' : 'items-start'">
                    <div class="text-[11px] text-slate-400 mb-1">{{ msg.username }} • {{ msg.time }}</div>
                    <div class="max-w-[85%] sm:max-w-md px-4 py-2.5 rounded-2xl text-sm"
                         :class="Number(msg.user_id) === currentUserId ? 'bg-emerald-500 text-slate-950 font-medium' : 'bg-slate-800 text-slate-100 border border-slate-700'">
                        {{ msg.body }}
                    </div>
                </div>
            </template>
        </div>

        <form @submit.prevent="sendMessage" class="p-3 sm:p-4 border-t border-slate-800 flex gap-2 bg-slate-900/60">
            <input type="text" v-model="newBody" required autocomplete="off" :placeholder="placeholder"
                   class="flex-1 bg-slate-800 border border-slate-700 rounded-xl px-3.5 py-2.5 text-sm text-white focus:outline-none focus:border-emerald-500">
            <button type="submit" class="px-5 py-2.5 rounded-xl bg-emerald-500 hover:bg-emerald-400 text-slate-950 font-extrabold text-xs transition">
                {{ sendLabel }}
            </button>
        </form>
    </div>
</template>

<script setup>
import { ref, onMounted, nextTick } from 'vue';

const props = defineProps({
    orderId: { type: Number, required: true },
    currentUserId: { type: Number, required: true },
    initialMessages: { type: Array, default: () => [] },
    postUrl: { type: String, required: true },
    pollUrl: { type: String, required: true },
    csrfToken: { type: String, required: true },
    title: { type: String, default: 'Order Chat' },
    emptyText: { type: String, default: 'No messages yet' },
    placeholder: { type: String, default: 'Type message...' },
    sendLabel: { type: String, default: 'Send' },
});

const messages = ref([...props.initialMessages]);
const newBody = ref('');
const messagesBox = ref(null);

function scrollToBottom() {
    nextTick(() => {
        if (messagesBox.value) {
            messagesBox.value.scrollTop = messagesBox.value.scrollHeight + 9999;
        }
    });
}

function appendMessage(msg) {
    if (!messages.value.some(m => Number(m.id) === Number(msg.id))) {
        messages.value.push(msg);
        scrollToBottom();
    }
}

async function sendMessage() {
    const body = newBody.value.trim();
    if (!body) return;
    newBody.value = '';

    const res = await fetch(props.postUrl, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': props.csrfToken,
            Accept: 'application/json',
        },
        body: JSON.stringify({ body }),
    });
    if (res.ok) {
        const data = await res.json();
        if (data.message) appendMessage(data.message);
    }
}

onMounted(() => {
    scrollToBottom();
    setInterval(async () => {
        const lastId = messages.value.length ? messages.value[messages.value.length - 1].id : 0;
        const res = await fetch(`${props.pollUrl}?after_id=${lastId}`, {
            headers: { Accept: 'application/json' },
        });
        if (res.ok) {
            const data = await res.json();
            (data.messages || []).forEach(appendMessage);
        }
    }, 2000);
});
</script>
