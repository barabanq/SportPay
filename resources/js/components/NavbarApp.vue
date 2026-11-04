<template>
    <header class="border-b border-slate-800 bg-slate-900/90 backdrop-blur sticky top-0 z-40">
        <div class="max-w-7xl mx-auto px-3 sm:px-4 py-2.5 sm:py-3">
            <div class="flex items-center justify-between gap-2">
                <!-- Логотип + Переключатель локали RU / EN -->
                <div class="flex items-center gap-2 sm:gap-3">
                    <a :href="routes.home" class="text-xl sm:text-2xl font-extrabold tracking-tight text-emerald-400">
                        SPORT<span class="text-white">PAY</span>
                        <span class="ml-1 text-[10px] px-1.5 py-0.5 rounded bg-emerald-500/15 text-emerald-300 border border-emerald-500/30 font-mono uppercase">Vue 3</span>
                    </a>

                    <form :action="routes.localeSwitch" method="POST" class="inline-flex rounded-xl bg-slate-950 p-0.5 border border-slate-800">
                        <input type="hidden" name="_token" :value="csrfToken">
                        <button type="submit" name="locale" value="ru"
                                :class="locale === 'ru' ? 'bg-emerald-500 text-slate-950' : 'text-slate-400 hover:text-white'"
                                class="px-2 py-1 rounded-lg text-[11px] font-extrabold transition">
                            RU
                        </button>
                        <button type="submit" name="locale" value="en"
                                :class="locale === 'en' ? 'bg-emerald-500 text-slate-950' : 'text-slate-400 hover:text-white'"
                                class="px-2 py-1 rounded-lg text-[11px] font-extrabold transition">
                            EN
                        </button>
                    </form>
                </div>

                <!-- Десктопная навигация -->
                <nav class="hidden lg:flex items-center gap-1.5">
                    <a :href="routes.home" class="text-xs px-3 py-2 rounded-lg text-slate-200 hover:bg-slate-800 transition font-semibold">
                        {{ i18n.marketplace }}
                    </a>
                    <a :href="routes.messages" class="text-xs px-3 py-2 rounded-lg text-slate-200 hover:bg-slate-800 transition flex items-center gap-1.5 font-semibold">
                        <span>💬 {{ i18n.messages }}</span>
                        <span v-if="unreadCount > 0" class="px-1.5 py-0.5 text-[10px] font-extrabold rounded-full bg-rose-500 text-white">
                            +{{ unreadCount }}
                        </span>
                    </a>
                    <a :href="routes.globalChat" class="text-xs px-3 py-2 rounded-lg text-slate-200 hover:bg-slate-800 transition font-semibold">
                        🌍 {{ i18n.globalChat }}
                    </a>
                    <a :href="routes.wallet" class="text-xs px-3 py-2 rounded-lg text-slate-200 hover:bg-slate-800 transition font-semibold">
                        💳 {{ i18n.wallet }}
                    </a>
                    <a :href="routes.arbitration" class="text-xs px-3 py-2 rounded-lg text-slate-200 hover:bg-slate-800 transition flex items-center gap-1.5 font-semibold">
                        <span>⚖️ {{ i18n.arbitration }}</span>
                        <span v-if="disputedCount > 0" class="px-1.5 py-0.5 text-[10px] font-extrabold rounded-full bg-amber-500 text-slate-950">
                            {{ disputedCount }}
                        </span>
                    </a>

                    <!-- Реактивный дропдаун Помощь на Vue -->
                    <div class="relative" ref="dropdownRef">
                        <button type="button" @click="helpOpen = !helpOpen"
                                class="text-xs px-3 py-2 rounded-lg text-slate-200 hover:bg-slate-800 transition flex items-center gap-1 font-semibold">
                            <span>{{ i18n.help }}</span>
                            <svg class="w-3.5 h-3.5 text-slate-400 transition-transform" :class="{ 'rotate-180': helpOpen }" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                            </svg>
                        </button>

                        <div v-if="helpOpen" class="absolute left-0 mt-2 w-48 rounded-xl bg-slate-900 border border-slate-700 shadow-2xl py-1.5 z-50 divide-y divide-slate-800/80 text-xs">
                            <div class="py-1">
                                <a :href="routes.rules" class="block px-4 py-2 text-slate-200 hover:bg-slate-800 hover:text-emerald-400">📜 {{ i18n.rules }}</a>
                                <a :href="routes.helpCenter" class="block px-4 py-2 text-slate-200 hover:bg-slate-800 hover:text-emerald-400">💡 {{ i18n.helpCenter }}</a>
                            </div>
                            <div class="py-1">
                                <a :href="routes.ticketsCreate" class="block px-4 py-2 text-slate-200 hover:bg-slate-800 hover:text-emerald-400">✉️ {{ i18n.submitRequest }}</a>
                                <a :href="routes.ticketsIndex" class="block px-4 py-2 text-slate-200 hover:bg-slate-800 hover:text-emerald-400">📂 {{ i18n.myRequests }}</a>
                            </div>
                        </div>
                    </div>
                </nav>

                <!-- Правый блок: Баланс, профиль, переключение аккаунта и мобильный бургер -->
                <div class="flex items-center gap-2">
                    <template v-if="user">
                        <a :href="routes.wallet" class="bg-slate-800/90 hover:bg-slate-800 px-2.5 sm:px-3.5 py-1.5 rounded-xl border border-slate-700 transition">
                            <div class="text-[9px] sm:text-[10px] text-slate-400 leading-none">{{ i18n.balance }}</div>
                            <div class="font-extrabold text-emerald-400 text-xs mt-0.5">
                                {{ formatPrice(user.wallet_balance) }} ₽
                            </div>
                        </a>

                        <a :href="`/users/${user.id}`" class="hidden sm:inline-flex text-xs text-slate-200 hover:text-emerald-400 font-bold px-3 py-2 rounded-xl bg-slate-800/80 border border-slate-700 transition">
                            👤 {{ user.username }}
                        </a>

                        <form :action="routes.logout" method="POST" class="hidden sm:block">
                            <input type="hidden" name="_token" :value="csrfToken">
                            <button type="submit" class="px-2.5 py-2 rounded-xl bg-slate-800 hover:bg-rose-500/20 border border-slate-700 text-xs text-slate-300 hover:text-rose-300 transition">
                                {{ i18n.logout }}
                            </button>
                        </form>
                    </template>

                    <form v-if="allUsers.length" :action="routes.userSwitch" method="POST" class="hidden xl:flex items-center gap-1.5 bg-slate-950/90 px-2.5 py-1.5 rounded-xl border border-slate-800">
                        <input type="hidden" name="_token" :value="csrfToken">
                        <span class="text-[10px] text-slate-500 uppercase font-bold">Dev:</span>
                        <select name="user_id" @change="$event.target.form.submit()" class="bg-slate-900 border border-slate-700 rounded-lg px-2 py-1 text-[11px] text-slate-200 font-semibold cursor-pointer">
                            <option v-for="u in allUsers" :key="u.id" :value="u.id" :selected="user && user.id === u.id">
                                {{ u.username }} ({{ formatPrice(u.wallet_balance) }} ₽)
                            </option>
                        </select>
                    </form>

                    <!-- Бургер для мобильных экранов -->
                    <button type="button" @click="mobileOpen = !mobileOpen" class="lg:hidden p-2 rounded-xl bg-slate-800 border border-slate-700 text-slate-200 hover:text-emerald-400">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/>
                        </svg>
                    </button>
                </div>
            </div>

            <!-- Раскрывающееся мобильное меню на Vue -->
            <div v-if="mobileOpen" class="lg:hidden mt-3 pt-3 border-t border-slate-800 space-y-3">
                <div class="grid grid-cols-2 sm:grid-cols-3 gap-2 text-xs">
                    <a :href="routes.home" class="px-3 py-2.5 rounded-xl bg-slate-800/80 text-slate-200 font-semibold">🏪 {{ i18n.marketplace }}</a>
                    <a :href="routes.messages" class="px-3 py-2.5 rounded-xl bg-slate-800/80 text-slate-200 font-semibold flex items-center justify-between">
                        <span>💬 {{ i18n.messages }}</span>
                        <span v-if="unreadCount > 0" class="px-1.5 py-0.5 text-[10px] font-extrabold rounded-full bg-rose-500 text-white">+{{ unreadCount }}</span>
                    </a>
                    <a :href="routes.globalChat" class="px-3 py-2.5 rounded-xl bg-slate-800/80 text-slate-200 font-semibold">🌍 {{ i18n.globalChat }}</a>
                    <a :href="routes.wallet" class="px-3 py-2.5 rounded-xl bg-slate-800/80 text-slate-200 font-semibold">💳 {{ i18n.wallet }}</a>
                    <a :href="routes.arbitration" class="px-3 py-2.5 rounded-xl bg-slate-800/80 text-slate-200 font-semibold">⚖️ {{ i18n.arbitration }}</a>
                    <a :href="routes.helpCenter" class="px-3 py-2.5 rounded-xl bg-slate-800/80 text-slate-200 font-semibold">💡 {{ i18n.helpCenter }}</a>
                </div>
            </div>
        </div>
    </header>
</template>

<script setup>
import { ref, onMounted, onBeforeUnmount } from 'vue';

const props = defineProps({
    user: { type: Object, default: null },
    allUsers: { type: Array, default: () => [] },
    unreadCount: { type: Number, default: 0 },
    disputedCount: { type: Number, default: 0 },
    locale: { type: String, default: 'ru' },
    csrfToken: { type: String, required: true },
    routes: { type: Object, required: true },
    i18n: { type: Object, required: true },
});

const helpOpen = ref(false);
const mobileOpen = ref(false);
const dropdownRef = ref(null);

function formatPrice(val) {
    return Math.round(Number(val || 0)).toLocaleString('ru-RU');
}

function handleOutsideClick(e) {
    if (dropdownRef.value && !dropdownRef.value.contains(e.target)) {
        helpOpen.value = false;
    }
}

onMounted(() => document.addEventListener('click', handleOutsideClick));
onBeforeUnmount(() => document.removeEventListener('click', handleOutsideClick));
</script>
