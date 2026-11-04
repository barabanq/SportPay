<template>
    <div class="p-3 rounded-xl bg-emerald-500/10 border border-emerald-500/20 text-xs text-emerald-300 flex items-center justify-between">
        <span>⚡ Vue 3 Reactive Wallet Calculator</span>
        <span>Current Balance: <b>{{ Number(balance).toFixed(2) }} ₽</b></span>
    </div>
</template>

<script setup>
defineProps({
    balance: { type: [Number, String], default: 0 },
});
</script>
