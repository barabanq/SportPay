<template>
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-5 sm:gap-6">
        <div class="lg:col-span-2 space-y-5">
            <div class="bg-slate-900 border border-slate-800 rounded-2xl p-4 sm:p-6 space-y-4">
                <div class="flex flex-wrap items-center justify-between gap-2">
                    <span class="text-xs px-2.5 py-1 rounded-lg bg-slate-800 text-slate-300 font-semibold">{{ lot.type_label }}</span>
                    <span class="text-xs text-slate-400">#{{ lot.id }} • {{ lot.stock_quantity }} pcs</span>
                </div>
                <h1 class="text-xl sm:text-2xl font-extrabold text-white">{{ lot.title }}</h1>
                <div v-if="lot.image_url" class="rounded-2xl overflow-hidden border border-slate-800 bg-slate-950">
                    <img :src="lot.image_url" :alt="lot.title" class="w-full max-h-96 object-cover">
                </div>
                <div class="p-4 rounded-xl bg-slate-950/80 border border-slate-800 text-sm text-slate-200 whitespace-pre-line">
                    {{ lot.description || i18n.noDescription }}
                </div>
            </div>
        </div>

        <div class="space-y-5">
            <div class="bg-slate-900 border border-slate-800 rounded-2xl p-4 sm:p-6 space-y-5">
                <div class="flex items-baseline justify-between">
                    <div class="text-2xl sm:text-3xl font-extrabold text-emerald-400">{{ formatPrice(lot.price) }} ₽</div>
                    <div class="text-sm font-bold text-white">{{ lot.stock_quantity }} pcs</div>
                </div>

                <form v-if="canBuy && lot.stock_quantity > 0" :action="purchaseUrl" method="POST" class="space-y-4">
                    <input type="hidden" name="_token" :value="csrfToken">
                    <div class="flex items-center justify-between gap-3">
                        <input type="number" name="quantity" v-model.number="quantity" min="1" :max="lot.stock_quantity"
                               class="w-24 bg-slate-950 border border-slate-700 rounded-xl px-3.5 py-2.5 text-sm font-bold text-white">
                        <div class="text-xs text-slate-400">
                            Total: <b class="text-emerald-400 text-sm">{{ formatPrice(totalPrice) }} ₽</b>
                        </div>
                    </div>
                    <button type="submit" class="w-full py-3 rounded-xl bg-emerald-500 hover:bg-emerald-400 text-slate-950 font-extrabold text-sm transition">
                        {{ i18n.buyBtn }}
                    </button>
                </form>
            </div>
        </div>
    </div>
</template>

<script setup>
import { ref, computed } from 'vue';

const props = defineProps({
    lot: { type: Object, required: true },
    canBuy: { type: Boolean, default: false },
    purchaseUrl: { type: String, required: true },
    csrfToken: { type: String, required: true },
    i18n: { type: Object, required: true },
});

const quantity = ref(1);
const totalPrice = computed(() => {
    const q = Math.max(1, Math.min(props.lot.stock_quantity, Number(quantity.value || 1)));
    return q * Number(props.lot.price);
});

function formatPrice(val) {
    return Math.round(Number(val || 0)).toLocaleString('ru-RU');
}
</script>
