<template>
    <div class="max-w-4xl mx-auto bg-slate-900 border border-slate-800 rounded-2xl overflow-hidden flex flex-col h-[70vh] sm:h-[74vh]">
        <div class="p-3.5 sm:p-4 border-b border-slate-800 bg-slate-900/80 flex items-center justify-between">
            <h1 class="text-sm sm:text-base font-extrabold text-white flex items-center gap-2">🌍 {{ title }}</h1>
            <div class="flex items-center gap-2">
                <span class="w-2.5 h-2.5 rounded-full bg-emerald-400 animate-pulse"></span>
                <span class="text-xs text-slate-400">{{ liveLabel }}</span>
            </div>
        </div>

        <div ref="chatBox" class="flex-1 p-3.5 sm:p-4 space-y-3 overflow-y-auto">
            <div v-for="msg in messages" :key="msg.id" class="flex flex-col" :class="Number(msg.user_id) === currentUserId ? 'items-end' : 'items-start'">
                <div class="text-[11px] text-slate-400 mb-1">
                    <span class="font-bold text-slate-300">{{ msg.username }}</span> • {{ msg.time }}
                </div>
                <div class="max-w-[85%] sm:max-w-lg px-4 py-2.5 rounded-2xl text-sm"
                     :class="Number(msg.user_id) === currentUserId ? 'bg-emerald-500 text-slate-950 font-medium' : 'bg-slate-800 text-slate-100 border border-slate-700'">
                    {{ msg.body }}
                </div>
            </div>
        </div>

        <form @submit.prevent="sendGlobal" class="p-3 border-t border-slate-800 bg-slate-900/80 flex gap-2">
            <input type="text" v-model="body" required autocomplete="off" :placeholder="placeholder"
                   class="flex-1 bg-slate-800 border border-slate-700 rounded-xl px-4 py-2.5 text-sm text-white focus:outline-none focus:border-emerald-500">
            <button type="submit" class="px-6 py-2.5 rounded-xl bg-emerald-500 hover:bg-emerald-400 text-slate-950 font-extrabold text-xs">
                {{ sendLabel }}
            </button>
        </form>
    </div>
</template>

<script setup>
import { ref, onMounted, nextTick } from 'vue';

const props = defineProps({
    currentUserId: { type: Number, required: true },
    initialMessages: { type: Array, default: () => [] },
    postUrl: { type: String, required: true },
    pollUrl: { type: String, required: true },
    csrfToken: { type: String, required: true },
    title: { type: String, required: true },
    liveLabel: { type: String, required: true },
    placeholder: { type: String, required: true },
    sendLabel: { type: String, required: true },
});

const messages = ref([...props.initialMessages]);
const body = ref('');
const chatBox = ref(null);

function scrollBottom() {
    nextTick(() => {
        if (chatBox.value) chatBox.value.scrollTop = chatBox.value.scrollHeight + 9999;
    });
}

function append(msg) {
    if (!messages.value.some(m => Number(m.id) === Number(msg.id))) {
        messages.value.push(msg);
        scrollBottom();
    }
}

async function sendGlobal() {
    const text = body.value.trim();
    if (!text) return;
    body.value = '';
    const res = await fetch(props.postUrl, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': props.csrfToken,
            Accept: 'application/json',
        },
        body: JSON.stringify({ body: text }),
    });
    if (res.ok) {
        const data = await res.json();
        if (data.message) append(data.message);
    }
}

onMounted(() => {
    scrollBottom();
    setInterval(async () => {
        const lastId = messages.value.length ? messages.value[messages.value.length - 1].id : 0;
        const res = await fetch(`${props.pollUrl}?after_id=${lastId}`, { headers: { Accept: 'application/json' } });
        if (res.ok) {
            const data = await res.json();
            (data.messages || []).forEach(append);
        }
    }, 2000);
});
</script>
