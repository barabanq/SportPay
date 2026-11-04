<template>
    <div class="space-y-6">
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-5 sm:gap-6">
            <!-- Левая колонка: Витрина спортивных лотов -->
            <div class="lg:col-span-2 bg-slate-900 border border-slate-800 rounded-2xl p-4 sm:p-5 space-y-4">
                <div class="space-y-3">
                    <div class="flex flex-wrap items-center justify-between gap-2">
                        <h2 class="text-base sm:text-lg font-extrabold text-white flex items-center gap-2">
                            <span>{{ i18n.title }}</span>
                            <span class="px-2.5 py-0.5 text-xs rounded-full bg-emerald-500/20 text-emerald-300 border border-emerald-500/30">
                                {{ filteredLots.length }}
                            </span>
                        </h2>
                    </div>

                    <!-- Реактивные вкладки категорий на Vue + серверные ссылки -->
                    <div class="flex items-center gap-2 overflow-x-auto no-scrollbar pb-1 -mx-1 px-1">
                        <button v-for="(cat, typeKey) in categories" :key="typeKey"
                                type="button"
                                @click="selectedCategory = typeKey"
                                :class="selectedCategory === typeKey
                                    ? 'bg-gradient-to-r from-emerald-500 to-teal-400 text-slate-950 font-extrabold shadow-lg shadow-emerald-500/25 ring-2 ring-emerald-400/50'
                                    : 'bg-slate-950/90 hover:bg-slate-800 text-slate-300 border border-slate-800 hover:border-slate-700 font-semibold'"
                                class="shrink-0 flex items-center gap-1.5 px-3.5 py-2 rounded-xl text-xs transition">
                            <span class="text-sm">{{ cat.icon }}</span>
                            <span>{{ cat.label }}</span>
                            <span class="px-1.5 py-0.5 text-[10px] rounded-md"
                                  :class="selectedCategory === typeKey ? 'bg-slate-950/20 text-slate-950 font-black' : 'bg-slate-800 text-slate-400'">
                                {{ cat.count }}
                            </span>
                        </button>
                    </div>
                </div>

                <!-- Форма фильтрации (работает и мгновенно через Vue, и через GET для бэкенда) -->
                <form :action="routes.home" method="GET" class="p-3 sm:p-3.5 rounded-xl bg-slate-950/70 border border-slate-800/90 space-y-3">
                    <input v-if="selectedCategory" type="hidden" name="type" :value="selectedCategory">

                    <div class="grid grid-cols-1 sm:grid-cols-12 gap-2.5">
                        <div class="sm:col-span-5">
                            <label class="block text-[10px] uppercase tracking-wider text-slate-400 font-bold mb-1">{{ i18n.searchLabel }}</label>
                            <input type="text" name="search" v-model="searchQuery" :placeholder="i18n.searchPlaceholder"
                                   class="w-full bg-slate-900 border border-slate-800 rounded-xl px-3 py-2 text-xs text-white focus:outline-none focus:border-emerald-500">
                        </div>

                        <div class="sm:col-span-4">
                            <label class="block text-[10px] uppercase tracking-wider text-slate-400 font-bold mb-1">{{ i18n.priceLabel }}</label>
                            <div class="grid grid-cols-2 gap-1.5">
                                <input type="number" name="min_price" v-model.number="minPrice" min="0" step="50" placeholder="500"
                                       class="w-full bg-slate-900 border border-slate-800 rounded-xl px-2.5 py-2 text-xs text-white focus:outline-none focus:border-emerald-500">
                                <input type="number" name="max_price" v-model.number="maxPrice" min="0" step="50" placeholder="1500"
                                       class="w-full bg-slate-900 border border-slate-800 rounded-xl px-2.5 py-2 text-xs text-white focus:outline-none focus:border-emerald-500">
                            </div>
                        </div>

                        <div class="sm:col-span-3">
                            <label class="block text-[10px] uppercase tracking-wider text-slate-400 font-bold mb-1">{{ i18n.sellerLabel }}</label>
                            <select name="seller" v-model="selectedSeller" class="w-full bg-slate-900 border border-slate-800 rounded-xl px-2.5 py-2 text-xs text-slate-200">
                                <option value="">{{ i18n.allSellers }}</option>
                                <option v-for="s in sellers" :key="s.id" :value="s.username">
                                    {{ s.is_online ? '🟢' : '⚪' }} {{ s.username }} (★ {{ Number(s.rating).toFixed(1) }})
                                </option>
                            </select>
                        </div>
                    </div>

                    <div class="flex flex-wrap items-center justify-between gap-3 pt-2 border-t border-slate-800/80">
                        <div class="flex flex-wrap items-center gap-3">
                            <div class="flex items-center gap-2">
                                <span class="text-[11px] text-slate-400 font-semibold">{{ i18n.sortBy }}</span>
                                <select name="sort" v-model="sortBy" class="bg-slate-900 border border-slate-800 rounded-xl px-3 py-1.5 text-xs text-slate-200">
                                    <option value="latest">{{ i18n.sortLatest }}</option>
                                    <option value="online_first">🟢 {{ i18n.sortOnline }}</option>
                                    <option value="price_asc">{{ i18n.sortPriceAsc }}</option>
                                    <option value="price_desc">{{ i18n.sortPriceDesc }}</option>
                                    <option value="rating_desc">{{ i18n.sortRating }}</option>
                                </select>
                            </div>

                            <label class="inline-flex items-center gap-2 px-3 py-1.5 rounded-xl bg-slate-900 border border-slate-800 cursor-pointer hover:border-emerald-500/50 transition text-xs">
                                <input type="checkbox" name="online_only" value="1" v-model="onlineOnly" class="rounded bg-slate-950 border-slate-700 text-emerald-500">
                                <span class="w-2 h-2 rounded-full bg-emerald-400 animate-pulse"></span>
                                <span class="font-bold text-slate-200">{{ i18n.onlineOnly }}</span>
                            </label>
                        </div>

                        <div class="flex items-center gap-2">
                            <button type="button" @click="resetFilters" class="px-3 py-1.5 rounded-xl bg-slate-800 hover:bg-rose-500/20 text-xs text-slate-300 hover:text-rose-300 font-semibold transition">
                                ✕ {{ i18n.reset }}
                            </button>
                            <button type="submit" class="px-4 py-1.5 rounded-xl bg-emerald-500 hover:bg-emerald-400 text-xs font-extrabold text-slate-950 transition">
                                {{ i18n.applyFilters }}
                            </button>
                        </div>
                    </div>
                </form>

                <!-- Мобильные карточки (< 768px) -->
                <div class="md:hidden space-y-3">
                    <div v-if="filteredLots.length === 0" class="py-8 text-center text-slate-500 text-xs">
                        {{ i18n.noLots }}
                    </div>
                    <div v-for="lot in filteredLots" :key="'mob-' + lot.id" class="p-3.5 rounded-xl bg-slate-950/80 border border-slate-800 space-y-2.5">
                        <div class="flex items-center justify-between gap-2">
                            <span class="inline-flex items-center gap-1 text-[11px] px-2 py-0.5 rounded-lg bg-slate-800 text-slate-200 font-medium">
                                <span>{{ categoryIcon(lot.product_type) }}</span>
                                <span>{{ categoryLabel(lot.product_type) }}</span>
                            </span>
                            <div class="text-base font-extrabold text-emerald-400">{{ formatPrice(lot.price) }} ₽</div>
                        </div>

                        <div class="flex items-start gap-3">
                            <a v-if="lot.image_url" :href="`/lots/${lot.id}`" class="shrink-0">
                                <img :src="lot.image_url" :alt="lot.title" class="w-14 h-14 rounded-xl object-cover border border-slate-700">
                            </a>
                            <div class="min-w-0 flex-1">
                                <div class="font-bold text-white text-sm flex flex-wrap items-center gap-1.5">
                                    <a :href="`/lots/${lot.id}`" class="hover:text-emerald-400 transition">{{ lot.title }}</a>
                                    <span v-if="lot.has_auto_delivery" class="px-1.5 py-0.5 text-[10px] font-extrabold rounded bg-emerald-500/20 text-emerald-300 border border-emerald-500/30">
                                        ⚡ {{ i18n.autoDelivery }}
                                    </span>
                                </div>
                                <a :href="`/lots/${lot.id}`" class="block text-xs text-slate-400 mt-1">
                                    {{ lot.description ? lot.description + ' • ' : '' }}{{ i18n.inStock }} {{ lot.stock_quantity }} {{ i18n.pcs }}
                                </a>
                            </div>
                        </div>

                        <div class="flex items-center justify-between pt-2 border-t border-slate-800/80 gap-2">
                            <button type="button" @click="openSellerModal(lot.seller_id)" class="flex items-center gap-2 text-left">
                                <div class="text-xs font-bold text-emerald-400">
                                    {{ lot.seller_username }}
                                    <span class="text-[10px]" :class="lot.seller_online ? 'text-emerald-400' : 'text-slate-500'">
                                        • {{ lot.seller_online ? i18n.online : i18n.offline }}
                                    </span>
                                </div>
                            </button>

                            <form v-if="user && lot.seller_id !== user.id" :action="`/lots/${lot.id}/purchase`" method="POST" class="flex items-center gap-1.5">
                                <input type="hidden" name="_token" :value="csrfToken">
                                <input type="number" name="quantity" value="1" min="1" :max="lot.stock_quantity" class="w-12 bg-slate-900 border border-slate-700 rounded-lg px-2 py-1.5 text-xs text-white text-center">
                                <button type="submit" class="px-3.5 py-1.5 rounded-lg bg-emerald-500 hover:bg-emerald-400 text-slate-950 font-extrabold text-xs">
                                    {{ i18n.buy }}
                                </button>
                            </form>
                        </div>
                    </div>
                </div>

                <!-- Десктопная таблица (>= 768px) -->
                <div class="hidden md:block overflow-x-auto">
                    <table class="w-full text-left border-collapse text-sm">
                        <thead>
                            <tr class="border-b border-slate-800 text-xs text-slate-400 uppercase">
                                <th class="py-3 px-2">{{ i18n.colType }}</th>
                                <th class="py-3 px-2">{{ i18n.colLot }}</th>
                                <th class="py-3 px-2">{{ i18n.sellerLabel }}</th>
                                <th class="py-3 px-2 text-right">{{ i18n.colPrice }}</th>
                                <th class="py-3 px-2 text-right">{{ i18n.colAction }}</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-800/70">
                            <tr v-if="filteredLots.length === 0">
                                <td colspan="5" class="py-8 text-center text-slate-500 text-xs">{{ i18n.noLots }}</td>
                            </tr>
                            <tr v-for="lot in filteredLots" :key="lot.id" class="hover:bg-slate-800/40 transition">
                                <td class="py-3 px-2">
                                    <span class="inline-flex items-center gap-1 text-xs px-2.5 py-1 rounded-lg bg-slate-800 text-slate-200 font-medium">
                                        <span>{{ categoryIcon(lot.product_type) }}</span>
                                        <span>{{ categoryLabel(lot.product_type) }}</span>
                                    </span>
                                </td>
                                <td class="py-3 px-2">
                                    <div class="flex items-center gap-3">
                                        <a v-if="lot.image_url" :href="`/lots/${lot.id}`" class="shrink-0">
                                            <img :src="lot.image_url" :alt="lot.title" class="w-11 h-11 rounded-xl object-cover border border-slate-700">
                                        </a>
                                        <div>
                                            <div class="font-semibold text-white flex items-center gap-1.5">
                                                <a :href="`/lots/${lot.id}`" class="hover:text-emerald-400 hover:underline transition">{{ lot.title }}</a>
                                                <span v-if="lot.has_auto_delivery" class="px-1.5 py-0.5 text-[10px] font-extrabold rounded bg-emerald-500/20 text-emerald-300 border border-emerald-500/30">
                                                    ⚡ {{ i18n.autoDelivery }}
                                                </span>
                                            </div>
                                            <a :href="`/lots/${lot.id}`" class="block text-xs text-slate-400 hover:text-slate-300">
                                                {{ lot.description ? lot.description + ' • ' : '' }}{{ i18n.inStock }} {{ lot.stock_quantity }} {{ i18n.pcs }}
                                            </a>
                                        </div>
                                    </div>
                                </td>
                                <td class="py-3 px-2">
                                    <button type="button" @click="openSellerModal(lot.seller_id)" class="flex items-center gap-2.5 text-left group">
                                        <div class="relative shrink-0">
                                            <img v-if="lot.seller_avatar" :src="lot.seller_avatar" class="w-9 h-9 rounded-xl object-cover border border-slate-700">
                                            <div v-else class="w-9 h-9 rounded-xl bg-slate-800 border border-slate-700 flex items-center justify-center text-xs font-extrabold text-emerald-400">
                                                {{ (lot.seller_username || 'U').slice(0, 1).toUpperCase() }}
                                            </div>
                                            <span class="absolute -bottom-0.5 -right-0.5 w-2.5 h-2.5 rounded-full border-2 border-slate-900"
                                                  :class="lot.seller_online ? 'bg-emerald-400' : 'bg-slate-600'"></span>
                                        </div>
                                        <div>
                                            <div class="font-bold text-emerald-400 group-hover:underline flex items-center gap-1">
                                                <span>{{ lot.seller_username }}</span>
                                                <span class="text-[10px] text-slate-500">ⓘ</span>
                                            </div>
                                            <div class="flex items-center gap-1.5 text-xs">
                                                <span class="text-amber-400">★ {{ Number(lot.seller_rating).toFixed(2) }}</span>
                                                <span class="text-[10px] font-semibold" :class="lot.seller_online ? 'text-emerald-400' : 'text-slate-500'">
                                                    • {{ lot.seller_online ? i18n.online : i18n.offline }}
                                                </span>
                                            </div>
                                        </div>
                                    </button>
                                </td>
                                <td class="py-3 px-2 text-right font-bold text-emerald-400">{{ formatPrice(lot.price) }} ₽</td>
                                <td class="py-3 px-2 text-right">
                                    <div v-if="user && lot.seller_id !== user.id" class="flex items-center justify-end gap-1.5">
                                        <a :href="`/lots/${lot.id}`" class="px-2.5 py-1.5 rounded-lg bg-slate-800 hover:bg-slate-700 text-slate-200 text-xs font-medium">{{ i18n.open }}</a>
                                        <form :action="`/lots/${lot.id}/purchase`" method="POST" class="flex items-center gap-1">
                                            <input type="hidden" name="_token" :value="csrfToken">
                                            <input type="number" name="quantity" value="1" min="1" :max="lot.stock_quantity" class="w-14 bg-slate-950 border border-slate-700 rounded-lg px-2 py-1 text-xs text-white text-center">
                                            <button type="submit" class="px-3 py-1.5 rounded-lg bg-emerald-500 hover:bg-emerald-400 text-slate-950 font-bold text-xs">{{ i18n.buy }}</button>
                                        </form>
                                    </div>
                                    <div v-else class="flex items-center justify-end gap-1.5">
                                        <a :href="`/lots/${lot.id}`" class="px-2.5 py-1 rounded-lg bg-slate-800 hover:bg-slate-700 border border-slate-700 text-xs text-slate-300">👁</a>
                                        <a :href="`/lots/${lot.id}/edit`" class="px-2.5 py-1 rounded-lg bg-slate-800 hover:bg-slate-700 border border-slate-700 text-xs text-slate-200">✎ {{ i18n.edit }}</a>
                                    </div>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Правая колонка: Быстрый кошелек и Создание лота -->
            <div class="space-y-5 sm:space-y-6">
                <div class="bg-slate-900 border border-slate-800 rounded-2xl p-4 sm:p-5">
                    <div class="flex items-center justify-between mb-3">
                        <h3 class="text-base font-bold text-white">{{ i18n.walletTitle }}</h3>
                        <a :href="routes.wallet" class="text-xs text-emerald-400 hover:underline font-semibold">{{ i18n.allTransactions }}</a>
                    </div>

                    <div class="grid grid-cols-2 gap-3 mb-4">
                        <form :action="routes.deposit" method="POST" class="space-y-2">
                            <input type="hidden" name="_token" :value="csrfToken">
                            <input type="number" name="amount" value="2000" min="100" class="w-full bg-slate-800 border border-slate-700 rounded-lg px-3 py-1.5 text-sm text-white">
                            <button type="submit" class="w-full py-2 rounded-lg bg-emerald-500 text-slate-950 font-bold text-xs">{{ i18n.depositBtn }}</button>
                        </form>
                        <form :action="routes.withdraw" method="POST" class="space-y-2">
                            <input type="hidden" name="_token" :value="csrfToken">
                            <input type="number" name="amount" value="1000" min="100" class="w-full bg-slate-800 border border-slate-700 rounded-lg px-3 py-1.5 text-sm text-white">
                            <button type="submit" class="w-full py-2 rounded-lg bg-slate-800 text-slate-200 font-bold text-xs">{{ i18n.withdrawBtn }}</button>
                        </form>
                    </div>

                    <div class="space-y-2 max-h-56 overflow-y-auto pr-1">
                        <div v-for="tx in transactions" :key="tx.id" class="p-2.5 rounded-xl bg-slate-950/80 border border-slate-800/80 text-xs space-y-1">
                            <div class="flex items-center justify-between">
                                <span class="font-bold text-slate-200">{{ tx.type_label }}</span>
                                <span class="font-extrabold" :class="Number(tx.amount) >= 0 ? 'text-emerald-400' : 'text-rose-400'">
                                    {{ Number(tx.amount) >= 0 ? '+' : '' }}{{ formatPrice(tx.amount) }} ₽
                                </span>
                            </div>
                            <div class="flex items-center justify-between text-[11px] text-slate-400">
                                <span>🕒 {{ tx.time_formatted }}</span>
                                <a v-if="tx.order_id" :href="`/orders/${tx.order_id}`" class="text-indigo-400 hover:underline font-semibold truncate max-w-[150px]">
                                    #{{ tx.order_id }} {{ tx.lot_title ? '• ' + tx.lot_title : '' }}
                                </a>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Создание лота с мгновенным превью фото на Vue -->
                <div class="bg-slate-900 border border-slate-800 rounded-2xl p-4 sm:p-5">
                    <h3 class="text-base font-bold text-white mb-3">{{ i18n.createLotTitle }}</h3>
                    <form :action="routes.lotsStore" method="POST" enctype="multipart/form-data" class="space-y-3 text-sm">
                        <input type="hidden" name="_token" :value="csrfToken">
                        <input type="text" name="title" required :placeholder="i18n.lotTitlePlaceholder" class="w-full bg-slate-800 border border-slate-700 rounded-lg px-3 py-2 text-white">
                        <div class="grid grid-cols-1 sm:grid-cols-3 gap-2">
                            <select name="product_type" class="bg-slate-800 border border-slate-700 rounded-lg px-2.5 py-2 text-white text-xs">
                                <option value="training_plan">💾 {{ categories.training_plan?.label }}</option>
                                <option value="coaching">🏋️ {{ categories.coaching?.label }}</option>
                                <option value="race_slot">🎟️ {{ categories.race_slot?.label }}</option>
                                <option value="equipment">👟 {{ categories.equipment?.label }}</option>
                            </select>
                            <input type="number" name="price" value="2000" min="50" required class="bg-slate-800 border border-slate-700 rounded-lg px-3 py-2 text-white text-xs">
                            <input type="number" name="stock_quantity" value="5" min="1" max="1000" required class="bg-slate-800 border border-slate-700 rounded-lg px-3 py-2 text-white text-xs">
                        </div>

                        <div>
                            <label class="block text-[11px] text-slate-400 font-semibold mb-1">📷 {{ i18n.lotPhotoLabel }}</label>
                            <input type="file" name="image" accept="image/*" @change="onLotImageChange"
                                   class="w-full text-xs text-slate-300 file:mr-3 file:py-1.5 file:px-3 file:rounded-lg file:border-0 file:text-xs file:font-bold file:bg-slate-800 file:text-emerald-400 hover:file:bg-slate-700 cursor-pointer">
                            <img v-if="imagePreviewUrl" :src="imagePreviewUrl" class="mt-2 w-20 h-20 rounded-xl object-cover border border-emerald-500/40">
                        </div>

                        <textarea name="description" rows="2" :placeholder="i18n.descPlaceholder" class="w-full bg-slate-800 border border-slate-700 rounded-lg px-3 py-2 text-white"></textarea>
                        <textarea name="auto_delivery_text" rows="2" :placeholder="i18n.autoDeliveryPlaceholder" class="w-full bg-slate-950 border border-emerald-500/40 rounded-lg px-3 py-2 text-xs text-emerald-300 placeholder-slate-500"></textarea>
                        <button type="submit" class="w-full py-2.5 rounded-xl bg-indigo-500 hover:bg-indigo-400 text-white font-bold text-xs transition">{{ i18n.publishBtn }}</button>
                    </form>
                </div>
            </div>
        </div>

        <!-- Реактивное модальное окно продавца на Vue -->
        <div v-if="modalOpen" @click.self="modalOpen = false" class="fixed inset-0 z-50 bg-slate-950/80 backdrop-blur-sm flex items-center justify-center p-3 sm:p-4">
            <div class="bg-slate-900 border border-slate-700 rounded-2xl max-w-lg w-full p-5 sm:p-6 space-y-4 shadow-2xl relative">
                <button type="button" @click="modalOpen = false" class="absolute top-4 right-4 w-8 h-8 rounded-lg bg-slate-800 hover:bg-slate-700 text-slate-400 hover:text-white flex items-center justify-center text-sm font-bold">✕</button>

                <div v-if="modalLoading" class="py-10 text-center text-slate-400 text-sm">Loading seller profile...</div>
                <template v-else-if="modalSeller">
                    <div class="flex items-center gap-3.5">
                        <div class="relative shrink-0">
                            <img v-if="modalSeller.avatar_url" :src="modalSeller.avatar_url" class="w-14 h-14 rounded-2xl object-cover border border-emerald-500/40">
                            <div v-else class="w-14 h-14 rounded-2xl bg-emerald-500/20 border border-emerald-500/40 flex items-center justify-center text-xl font-extrabold text-emerald-400">
                                {{ modalSeller.initial }}
                            </div>
                        </div>
                        <div>
                            <div class="flex flex-wrap items-center gap-2">
                                <h3 class="text-xl font-extrabold text-white">{{ modalSeller.username }}</h3>
                                <span class="text-[11px] font-bold px-2 py-0.5 rounded-full"
                                      :class="modalSeller.is_online ? 'bg-emerald-500/20 text-emerald-300 border border-emerald-500/30' : 'bg-slate-800 text-slate-400'">
                                    {{ modalSeller.is_online ? '🟢 ' : '⚪ ' }}{{ modalSeller.online_label }}
                                </span>
                            </div>
                            <p class="text-xs text-slate-400 mt-0.5">SportPay • {{ modalSeller.registered_at }}</p>
                        </div>
                    </div>

                    <div class="grid grid-cols-3 gap-3 text-center bg-slate-950/80 p-3 rounded-xl border border-slate-800">
                        <div>
                            <div class="text-[11px] text-slate-400">Rating</div>
                            <div class="text-base font-extrabold text-amber-400 mt-0.5">★ {{ modalSeller.rating }}</div>
                        </div>
                        <div class="border-x border-slate-800">
                            <div class="text-[11px] text-slate-400">Orders</div>
                            <div class="text-base font-extrabold text-emerald-400 mt-0.5">{{ modalSeller.completed_orders }}</div>
                        </div>
                        <div>
                            <div class="text-[11px] text-slate-400">Active Lots</div>
                            <div class="text-base font-extrabold text-white mt-0.5">{{ modalSeller.active_lots_count }}</div>
                        </div>
                    </div>

                    <div class="flex gap-2.5 pt-2">
                        <a :href="modalSeller.filter_seller_url" class="flex-1 py-2.5 rounded-xl bg-slate-800 hover:bg-slate-700 text-center text-xs font-bold text-emerald-400">
                            {{ i18n.showSellerLots }}
                        </a>
                        <a :href="modalSeller.profile_url" class="flex-1 py-2.5 rounded-xl bg-emerald-500 hover:bg-emerald-400 text-center text-xs font-extrabold text-slate-950">
                            {{ i18n.openProfile }}
                        </a>
                    </div>
                </template>
            </div>
        </div>
    </div>
</template>

<script setup>
import { ref, computed } from 'vue';

const props = defineProps({
    user: { type: Object, default: null },
    lots: { type: Array, default: () => [] },
    categories: { type: Object, required: true },
    sellers: { type: Array, default: () => [] },
    transactions: { type: Array, default: () => [] },
    initialFilters: { type: Object, default: () => ({}) },
    csrfToken: { type: String, required: true },
    routes: { type: Object, required: true },
    i18n: { type: Object, required: true },
});

const selectedCategory = ref(props.initialFilters.type || '');
const searchQuery = ref(props.initialFilters.search || '');
const minPrice = ref(props.initialFilters.min_price ? Number(props.initialFilters.min_price) : null);
const maxPrice = ref(props.initialFilters.max_price ? Number(props.initialFilters.max_price) : null);
const selectedSeller = ref(props.initialFilters.seller || '');
const sortBy = ref(props.initialFilters.sort || 'latest');
const onlineOnly = ref(Boolean(props.initialFilters.online_only));

const imagePreviewUrl = ref(null);
const modalOpen = ref(false);
const modalLoading = ref(false);
const modalSeller = ref(null);

const filteredLots = computed(() => {
    let list = [...props.lots];

    if (selectedCategory.value) {
        list = list.filter(l => l.product_type === selectedCategory.value);
    }
    if (searchQuery.value.trim()) {
        const q = searchQuery.value.toLowerCase();
        list = list.filter(l =>
            (l.title || '').toLowerCase().includes(q) ||
            (l.description || '').toLowerCase().includes(q)
        );
    }
    if (minPrice.value && minPrice.value > 0) {
        list = list.filter(l => Number(l.price) >= minPrice.value);
    }
    if (maxPrice.value && maxPrice.value > 0) {
        list = list.filter(l => Number(l.price) <= maxPrice.value);
    }
    if (selectedSeller.value) {
        list = list.filter(l => (l.seller_username || '').toLowerCase().includes(selectedSeller.value.toLowerCase()));
    }
    if (onlineOnly.value) {
        list = list.filter(l => Boolean(l.seller_online));
    }

    if (sortBy.value === 'price_asc') {
        list.sort((a, b) => Number(a.price) - Number(b.price));
    } else if (sortBy.value === 'price_desc') {
        list.sort((a, b) => Number(b.price) - Number(a.price));
    } else if (sortBy.value === 'rating_desc') {
        list.sort((a, b) => Number(b.seller_rating) - Number(a.seller_rating));
    } else if (sortBy.value === 'online_first') {
        list.sort((a, b) => Number(Boolean(b.seller_online)) - Number(Boolean(a.seller_online)));
    }

    return list;
});

function resetFilters() {
    selectedCategory.value = '';
    searchQuery.value = '';
    minPrice.value = null;
    maxPrice.value = null;
    selectedSeller.value = '';
    sortBy.value = 'latest';
    onlineOnly.value = false;
}

function categoryIcon(type) {
    return props.categories[type]?.icon || '🏅';
}

function categoryLabel(type) {
    return props.categories[type]?.label || type;
}

function formatPrice(val) {
    return Math.round(Number(val || 0)).toLocaleString('ru-RU');
}

function onLotImageChange(e) {
    const file = e.target.files?.[0];
    imagePreviewUrl.value = file ? URL.createObjectURL(file) : null;
}

async function openSellerModal(sellerId) {
    modalOpen.value = true;
    modalLoading.value = true;
    try {
        const res = await fetch(`/users/${sellerId}/modal`, {
            headers: { Accept: 'application/json' },
        });
        if (res.ok) {
            modalSeller.value = await res.json();
        }
    } finally {
        modalLoading.value = false;
    }
}
</script>
