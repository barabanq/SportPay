@extends('layouts.app')

@section('content')
@php
    $totalActiveLots = array_sum($categoryCounts ?? []);
    $categories = [
        '' => ['label' => __('Все категории'), 'icon' => '🔥', 'count' => $totalActiveLots],
        'team_game' => ['label' => __('Волейбол / Футбол'), 'icon' => '🏐', 'count' => $categoryCounts['team_game'] ?? 0],
        'training_plan' => ['label' => __('Программы'), 'icon' => '💾', 'count' => $categoryCounts['training_plan'] ?? 0],
        'coaching' => ['label' => __('Тренерство'), 'icon' => '🏋️', 'count' => $categoryCounts['coaching'] ?? 0],
        'race_slot' => ['label' => __('Слоты'), 'icon' => '🎟️', 'count' => $categoryCounts['race_slot'] ?? 0],
        'equipment' => ['label' => __('Экипировка'), 'icon' => '👟', 'count' => $categoryCounts['equipment'] ?? 0],
    ];
    $currentType = (string) request('type', '');
    $baseFilterParams = request()->only(['search', 'sort', 'min_price', 'max_price', 'seller', 'online_only', 'lat', 'lng', 'radius_km']);
@endphp

<div class="grid grid-cols-1 lg:grid-cols-3 gap-5 sm:gap-6">
    <div class="lg:col-span-2 bg-slate-900 border border-slate-800 rounded-2xl p-4 sm:p-5 space-y-4">
        <div class="space-y-3">
            <div class="flex flex-wrap items-center justify-between gap-2">
                <h2 class="text-base sm:text-lg font-extrabold text-white flex items-center gap-2">
                    <span>{{ __('Витрина спортивных лотов') }}</span>
                    <span class="px-2.5 py-0.5 text-xs rounded-full bg-emerald-500/20 text-emerald-300 border border-emerald-500/30">
                        {{ $lots->total() }}
                    </span>
                </h2>
                @if(request('seller'))
                    <div class="flex items-center gap-1.5 px-3 py-1 rounded-full bg-indigo-500/20 border border-indigo-500/40 text-xs text-indigo-300 font-bold">
                        <span>{{ __('Продавец:') }} {{ request('seller') }}</span>
                        <a href="{{ route('home', array_filter(request()->except('seller'))) }}" class="hover:text-white ml-1">✕</a>
                    </div>
                @endif
            </div>

            <div class="flex items-center gap-2 overflow-x-auto no-scrollbar pb-1 -mx-1 px-1">
                @foreach ($categories as $typeKey => $cat)
                    @php
                        $isActiveCat = $currentType === $typeKey;
                        $catParams = array_filter(array_merge($baseFilterParams, ['type' => $typeKey ?: null]));
                    @endphp
                    <a href="{{ route('home', $catParams) }}"
                       class="shrink-0 flex items-center gap-1.5 px-3.5 py-2 rounded-xl text-xs transition {{ $isActiveCat ? 'bg-gradient-to-r from-emerald-500 to-teal-400 text-slate-950 font-extrabold shadow-lg shadow-emerald-500/25 ring-2 ring-emerald-400/50' : 'bg-slate-950/90 hover:bg-slate-800 text-slate-300 border border-slate-800 hover:border-slate-700 font-semibold' }}">
                        <span class="text-sm">{{ $cat['icon'] }}</span>
                        <span>{{ $cat['label'] }}</span>
                        <span class="px-1.5 py-0.5 text-[10px] rounded-md {{ $isActiveCat ? 'bg-slate-950/20 text-slate-950 font-black' : 'bg-slate-800 text-slate-400' }}">
                            {{ $cat['count'] }}
                        </span>
                    </a>
                @endforeach
            </div>
        </div>

        <form action="{{ route('home') }}" method="GET" class="p-3 sm:p-3.5 rounded-xl bg-slate-950/70 border border-slate-800/90 space-y-3">
            @if(request('type'))
                <input type="hidden" name="type" value="{{ request('type') }}">
            @endif

            <div class="grid grid-cols-1 sm:grid-cols-12 gap-2.5">
                <div class="sm:col-span-5">
                    <label class="block text-[10px] uppercase tracking-wider text-slate-400 font-bold mb-1">{{ __('Поиск по лотам и залам') }}</label>
                    <input type="text" id="lot-search-input" name="search" value="{{ request('search') }}" placeholder="{{ __('Волейбол, футбол, адрес зала...') }}" class="w-full bg-slate-900 border border-slate-800 rounded-xl px-3 py-2 text-xs text-white focus:outline-none focus:border-emerald-500">
                </div>

                <div class="sm:col-span-4">
                    <label class="block text-[10px] uppercase tracking-wider text-slate-400 font-bold mb-1">{{ __('Цена (от — до, ₽)') }}</label>
                    <div class="grid grid-cols-2 gap-1.5">
                        <input type="number" name="min_price" value="{{ request('min_price') }}" min="0" step="50" placeholder="{{ __('Мин. 500') }}" class="w-full bg-slate-900 border border-slate-800 rounded-xl px-2.5 py-2 text-xs text-white focus:outline-none focus:border-emerald-500">
                        <input type="number" name="max_price" value="{{ request('max_price') }}" min="0" step="50" placeholder="{{ __('Макс. 1500') }}" class="w-full bg-slate-900 border border-slate-800 rounded-xl px-2.5 py-2 text-xs text-white focus:outline-none focus:border-emerald-500">
                    </div>
                </div>

                <div class="sm:col-span-3">
                    <label class="block text-[10px] uppercase tracking-wider text-slate-400 font-bold mb-1">{{ __('Продавец / Организатор') }}</label>
                    <select name="seller" onchange="this.form.submit()" class="w-full bg-slate-900 border border-slate-800 rounded-xl px-2.5 py-2 text-xs text-slate-200 focus:outline-none focus:border-emerald-500">
                        <option value="">{{ __('Все продавцы') }}</option>
                        @foreach ($sellers as $s)
                            <option value="{{ $s->username }}" @selected(request('seller') === $s->username)>
                                {{ $s->isOnline() ? '🟢' : '⚪' }} {{ $s->username }} (★ {{ number_format((float) $s->rating, 1) }})
                            </option>
                        @endforeach
                    </select>
                </div>
            </div>

            <div class="pt-2 border-t border-slate-800/80 flex flex-wrap items-center justify-between gap-2">
                <div class="flex flex-wrap items-center gap-2">
                    <span class="text-[11px] font-bold text-emerald-400">�� {{ __('Игры рядом:') }}</span>
                    <input type="number" step="any" id="filter_lat" name="lat" value="{{ request('lat') }}" placeholder="{{ __('Широта') }}" class="w-24 bg-slate-900 border border-slate-800 rounded-lg px-2.5 py-1.5 text-xs text-white">
                    <input type="number" step="any" id="filter_lng" name="lng" value="{{ request('lng') }}" placeholder="{{ __('Долгота') }}" class="w-24 bg-slate-900 border border-slate-800 rounded-lg px-2.5 py-1.5 text-xs text-white">
                    <input type="number" step="1" min="1" name="radius_km" value="{{ request('radius_km') }}" placeholder="{{ __('Радиус км') }}" class="w-24 bg-slate-900 border border-slate-800 rounded-lg px-2.5 py-1.5 text-xs text-white">
                </div>
                <div class="flex flex-wrap items-center gap-1.5 text-[11px]">
                    <button type="button" onclick="setFilterGeo(55.7558, 37.6173)" class="px-2 py-1 rounded-lg bg-slate-900 border border-slate-800 hover:border-emerald-500/40 text-slate-300">Москва</button>
                    <button type="button" onclick="setFilterGeo(56.3269, 44.0059)" class="px-2 py-1 rounded-lg bg-slate-900 border border-slate-800 hover:border-emerald-500/40 text-slate-300">Н. Новгород</button>
                    <button type="button" onclick="detectFilterGeo()" class="px-2.5 py-1 rounded-lg bg-emerald-500/15 border border-emerald-500/30 text-emerald-300 font-bold hover:bg-emerald-500/25">Мой GPS</button>
                </div>
            </div>

            <div class="flex flex-wrap items-center justify-between gap-3 pt-2 border-t border-slate-800/80">
                <div class="flex flex-wrap items-center gap-3 w-full sm:w-auto">
                    <div class="flex items-center gap-2">
                        <span class="text-[11px] text-slate-400 font-semibold shrink-0">{{ __('Сортировка:') }}</span>
                        <select name="sort" onchange="this.form.submit()" class="bg-slate-900 border border-slate-800 rounded-xl px-3 py-1.5 text-xs text-slate-200">
                            <option value="latest" @selected(request('sort', 'latest') === 'latest')>{{ __('Сначала новые') }}</option>
                            <option value="nearest" @selected(request('sort') === 'nearest')>📍 {{ __('Сначала ближайшие ко мне') }}</option>
                            <option value="online_first" @selected(request('sort') === 'online_first')>🟢 {{ __('Сначала продавцы онлайн') }}</option>
                            <option value="price_asc" @selected(request('sort') === 'price_asc')>{{ __('Дешевле (Цена ↑)') }}</option>
                            <option value="price_desc" @selected(request('sort') === 'price_desc')>{{ __('Дороже (Цена ↓)') }}</option>
                            <option value="rating_desc" @selected(request('sort') === 'rating_desc')>{{ __('По рейтингу тренера ★') }}</option>
                        </select>
                    </div>

                    <label class="inline-flex items-center gap-2 px-3 py-1.5 rounded-xl bg-slate-900 border border-slate-800 cursor-pointer hover:border-emerald-500/50 transition text-xs">
                        <input type="checkbox" name="online_only" value="1" onchange="this.form.submit()" @checked(request()->boolean('online_only')) class="rounded bg-slate-950 border-slate-700 text-emerald-500 focus:ring-0">
                        <span class="w-2 h-2 rounded-full bg-emerald-400 animate-pulse"></span>
                        <span class="font-bold text-slate-200">{{ __('Только онлайн') }}</span>
                    </label>
                </div>

                <div class="flex items-center gap-2 w-full sm:w-auto justify-end">
                    @if(request()->anyFilled(['search', 'type', 'sort', 'min_price', 'max_price', 'seller', 'online_only', 'lat', 'lng', 'radius_km']))
                        <a href="{{ route('home') }}" class="px-3 py-1.5 rounded-xl bg-slate-800 hover:bg-rose-500/20 text-xs text-slate-300 hover:text-rose-300 font-semibold transition">
                            ✕ {{ __('Сбросить') }}
                        </a>
                    @endif
                    <button type="submit" class="flex-1 sm:flex-initial px-4 py-1.5 rounded-xl bg-emerald-500 hover:bg-emerald-400 text-xs font-extrabold text-slate-950 transition">
                        {{ __('Применить фильтры') }}
                    </button>
                </div>
            </div>
        </form>

        <!-- МОБИЛЬНЫЙ ВИД ЛОТОВ -->
        <div id="lots-mobile-list" class="md:hidden space-y-3">
            @if($lots->isEmpty())
                <div class="py-8 text-center text-slate-500 text-xs">{{ __('По вашему запросу лотов не найдено.') }}</div>
            @else
                @foreach ($lots as $lot)
                    @php
                        $catMeta = $categories[$lot->product_type] ?? ['icon' => '🏅', 'label' => $lot->product_type];
                        $sellerOnline = $lot->seller?->isOnline() ?? false;
                    @endphp
                    <div class="lot-row p-3.5 rounded-xl bg-slate-950/80 border border-slate-800 space-y-2.5">
                        <div class="flex items-center justify-between gap-2">
                            <span class="inline-flex items-center gap-1 text-[11px] px-2 py-0.5 rounded-lg bg-slate-800 text-slate-200 font-medium">
                                <span>{{ $catMeta['icon'] }}</span>
                                <span>{{ $catMeta['label'] }}</span>
                            </span>
                            <div class="text-base font-extrabold text-emerald-400">
                                {{ number_format((float) $lot->price, 0, '.', ' ') }} ₽
                            </div>
                        </div>

                        <div class="flex items-start gap-3">
                            @if($lot->imageUrl())
                                <a href="{{ route('lots.show', $lot) }}" class="shrink-0">
                                    <img src="{{ $lot->imageUrl() }}" alt="{{ $lot->title }}" class="w-14 h-14 rounded-xl object-cover border border-slate-700">
                                </a>
                            @endif
                            <div class="min-w-0 flex-1">
                                <div class="font-bold text-white text-sm flex flex-wrap items-center gap-1.5">
                                    <a href="{{ route('lots.show', $lot) }}" class="hover:text-emerald-400 transition">
                                        {{ $lot->title }}
                                    </a>
                                    @if($lot->hasAutoDelivery())
                                        <span class="px-1.5 py-0.5 text-[10px] font-extrabold rounded bg-emerald-500/20 text-emerald-300 border border-emerald-500/30">
                                            ⚡ {{ __('Автовыдача') }}
                                        </span>
                                    @endif
                                </div>
                                @if($lot->location_name || $lot->hasLocation() || $lot->distance_km !== null)
                                    <div class="mt-1 flex flex-wrap items-center gap-1.5 text-[11px] text-emerald-300 font-semibold">
                                        <span>📍 {{ $lot->location_name ?: number_format((float) $lot->latitude, 4) . ', ' . number_format((float) $lot->longitude, 4) }}</span>
                                        @if($lot->distance_km !== null)
                                            <span class="px-1.5 py-0.5 rounded bg-emerald-500/20 text-emerald-300 font-extrabold">
                                                {{ $lot->distance_km }} {{ __('км') }}
                                            </span>
                                        @endif
                                    </div>
                                @endif
                                <a href="{{ route('lots.show', $lot) }}" class="block text-xs text-slate-400 mt-1 line-clamp-2">
                                    {{ $lot->description ? $lot->description . ' • ' : '' }}{{ __('Мест / В наличии:') }} {{ $lot->stock_quantity }} {{ __('шт.') }}
                                </a>
                            </div>
                        </div>

                        <div class="flex items-center justify-between pt-2 border-t border-slate-800/80 gap-2">
                            <button type="button" onclick="openSellerModal({{ (int) $lot->seller_id }})" class="flex items-center gap-2 text-left">
                                <div class="relative shrink-0">
                                    @if($lot->seller?->avatarUrl())
                                        <img src="{{ $lot->seller->avatarUrl() }}" alt="{{ $lot->seller->username }}" class="w-8 h-8 rounded-lg object-cover border border-slate-700">
                                    @else
                                        <div class="w-8 h-8 rounded-lg bg-slate-800 border border-slate-700 flex items-center justify-center text-xs font-extrabold text-emerald-400">
                                            {{ strtoupper(substr($lot->seller->username ?? 'U', 0, 1)) }}
                                        </div>
                                    @endif
                                    <span class="absolute -bottom-0.5 -right-0.5 w-2.5 h-2.5 rounded-full border-2 border-slate-950 {{ $sellerOnline ? 'bg-emerald-400' : 'bg-slate-600' }}"></span>
                                </div>
                                <div>
                                    <div class="text-xs font-bold text-emerald-400 flex items-center gap-1">
                                        <span>{{ $lot->seller->username ?? '-' }}</span>
                                        <span class="text-[10px] {{ $sellerOnline ? 'text-emerald-400' : 'text-slate-500' }}">
                                            • {{ $sellerOnline ? __('Онлайн') : __('Офлайн') }}
                                        </span>
                                    </div>
                                    <div class="text-[11px] text-amber-400">★ {{ number_format((float) ($lot->seller->rating ?? 5), 2) }}</div>
                                </div>
                            </button>

                            @if($user && $lot->seller_id !== $user->id)
                                <form action="{{ route('lots.purchase', $lot) }}" method="POST" class="flex items-center gap-1.5">
                                    @csrf
                                    <input type="number" name="quantity" value="1" min="1" max="{{ $lot->stock_quantity }}" class="w-12 bg-slate-900 border border-slate-700 rounded-lg px-2 py-1.5 text-xs text-white text-center">
                                    <button type="submit" class="px-3.5 py-1.5 rounded-lg bg-emerald-500 hover:bg-emerald-400 text-slate-950 font-extrabold text-xs">
                                        {{ __('Купить') }}
                                    </button>
                                </form>
                            @else
                                <div class="flex items-center gap-1.5">
                                    <a href="{{ route('lots.show', $lot) }}" class="px-2.5 py-1.5 rounded-lg bg-slate-800 text-xs text-slate-200">{{ __('Открыть') }}</a>
                                    <a href="{{ route('lots.edit', $lot) }}" class="px-2.5 py-1.5 rounded-lg bg-slate-800 text-xs text-slate-200">✎</a>
                                </div>
                            @endif
                        </div>
                    </div>
                @endforeach
            @endif
        </div>

        <!-- ДЕСКТОПНАЯ ТАБЛИЦА ЛОТОВ -->
        <div class="hidden md:block overflow-x-auto">
            <table class="w-full text-left border-collapse text-sm">
                <thead>
                    <tr class="border-b border-slate-800 text-xs text-slate-400 uppercase">
                        <th class="py-3 px-2">{{ __('Тип') }}</th>
                        <th class="py-3 px-2">{{ __('Лот / Адрес игры') }}</th>
                        <th class="py-3 px-2">{{ __('Продавец') }}</th>
                        <th class="py-3 px-2 text-right">{{ __('Цена') }}</th>
                        <th class="py-3 px-2 text-right">{{ __('Действие') }}</th>
                    </tr>
                </thead>
                <tbody id="lots-table-body" class="divide-y divide-slate-800/70">
                    @if($lots->isEmpty())
                        <tr>
                            <td colspan="5" class="py-8 text-center text-slate-500 text-xs">{{ __('По вашему запросу лотов не найдено.') }}</td>
                        </tr>
                    @else
                        @foreach ($lots as $lot)
                        @php
                            $catMeta = $categories[$lot->product_type] ?? ['icon' => '🏅', 'label' => $lot->product_type];
                            $sellerOnline = $lot->seller?->isOnline() ?? false;
                        @endphp
                        <tr class="lot-row hover:bg-slate-800/40 transition">
                            <td class="py-3 px-2">
                                <span class="inline-flex items-center gap-1 text-xs px-2.5 py-1 rounded-lg bg-slate-800 text-slate-200 font-medium">
                                    <span>{{ $catMeta['icon'] }}</span>
                                    <span>{{ $catMeta['label'] }}</span>
                                </span>
                            </td>
                            <td class="py-3 px-2">
                                <div class="flex items-center gap-3">
                                    @if($lot->imageUrl())
                                        <a href="{{ route('lots.show', $lot) }}" class="shrink-0">
                                            <img src="{{ $lot->imageUrl() }}" alt="{{ $lot->title }}" class="w-11 h-11 rounded-xl object-cover border border-slate-700">
                                        </a>
                                    @endif
                                    <div>
                                        <div class="font-semibold text-white flex flex-wrap items-center gap-1.5">
                                            <a href="{{ route('lots.show', $lot) }}" class="hover:text-emerald-400 hover:underline transition">
                                                {{ $lot->title }}
                                            </a>
                                            @if($lot->hasAutoDelivery())
                                                <span class="px-1.5 py-0.5 text-[10px] font-extrabold rounded bg-emerald-500/20 text-emerald-300 border border-emerald-500/30">
                                                    ⚡ {{ __('Автовыдача') }}
                                                </span>
                                            @endif
                                        </div>
                                        @if($lot->location_name || $lot->hasLocation() || $lot->distance_km !== null)
                                            <div class="mt-0.5 flex flex-wrap items-center gap-1.5 text-[11px] text-emerald-300 font-medium">
                                                <span>📍 {{ $lot->location_name ?: number_format((float) $lot->latitude, 4) . ', ' . number_format((float) $lot->longitude, 4) }}</span>
                                                @if($lot->distance_km !== null)
                                                    <span class="px-1.5 py-0.5 rounded bg-emerald-500/20 border border-emerald-500/30 text-emerald-300 font-extrabold">
                                                        {{ $lot->distance_km }} {{ __('км от вас') }}
                                                    </span>
                                                @endif
                                            </div>
                                        @endif
                                        <a href="{{ route('lots.show', $lot) }}" class="block text-xs text-slate-400 hover:text-slate-300">
                                            {{ $lot->description ? $lot->description . ' • ' : '' }}{{ __('В наличии:') }} {{ $lot->stock_quantity }} {{ __('шт.') }}
                                        </a>
                                    </div>
                                </div>
                            </td>
                            <td class="py-3 px-2">
                                <button type="button"
                                        onclick="openSellerModal({{ (int) $lot->seller_id }})"
                                        class="flex items-center gap-2.5 text-left group focus:outline-none">
                                    <div class="relative shrink-0">
                                        @if($lot->seller?->avatarUrl())
                                            <img src="{{ $lot->seller->avatarUrl() }}" alt="{{ $lot->seller->username }}" class="w-9 h-9 rounded-xl object-cover border border-slate-700">
                                        @else
                                            <div class="w-9 h-9 rounded-xl bg-slate-800 border border-slate-700 flex items-center justify-center text-xs font-extrabold text-emerald-400">
                                                {{ strtoupper(substr($lot->seller->username ?? 'U', 0, 1)) }}
                                            </div>
                                        @endif
                                        <span title="{{ $sellerOnline ? __('Онлайн') : __('Офлайн') }}"
                                              class="absolute -bottom-0.5 -right-0.5 w-2.5 h-2.5 rounded-full border-2 border-slate-900 {{ $sellerOnline ? 'bg-emerald-400' : 'bg-slate-600' }}"></span>
                                    </div>
                                    <div>
                                        <div class="font-bold text-emerald-400 group-hover:underline flex items-center gap-1">
                                            <span>{{ $lot->seller->username ?? '-' }}</span>
                                            <span class="text-[10px] text-slate-500 group-hover:text-slate-300">ⓘ</span>
                                        </div>
                                        <div class="flex items-center gap-1.5 text-xs">
                                            <span class="text-amber-400">★ {{ number_format((float) ($lot->seller->rating ?? 5), 2) }}</span>
                                            <span class="text-[10px] font-semibold {{ $sellerOnline ? 'text-emerald-400' : 'text-slate-500' }}">
                                                • {{ $sellerOnline ? __('Онлайн') : __('Офлайн') }}
                                            </span>
                                        </div>
                                    </div>
                                </button>
                            </td>
                            <td class="py-3 px-2 text-right font-bold text-emerald-400">{{ number_format((float) $lot->price, 0, '.', ' ') }} ₽</td>
                            <td class="py-3 px-2 text-right">
                                @if($user && $lot->seller_id !== $user->id)
                                    <div class="flex items-center justify-end gap-1.5">
                                        <a href="{{ route('lots.show', $lot) }}" class="px-2.5 py-1.5 rounded-lg bg-slate-800 hover:bg-slate-700 text-slate-200 text-xs font-medium">{{ __('Открыть') }}</a>
                                        <form action="{{ route('lots.purchase', $lot) }}" method="POST" class="flex items-center gap-1">
                                            @csrf
                                            <input type="number" name="quantity" value="1" min="1" max="{{ $lot->stock_quantity }}" class="w-14 bg-slate-950 border border-slate-700 rounded-lg px-2 py-1 text-xs text-white text-center">
                                            <button type="submit" class="px-3 py-1.5 rounded-lg bg-emerald-500 hover:bg-emerald-400 text-slate-950 font-bold text-xs">{{ __('Купить') }}</button>
                                        </form>
                                    </div>
                                @else
                                    <div class="flex items-center justify-end gap-1.5">
                                        <a href="{{ route('lots.show', $lot) }}" class="px-2.5 py-1 rounded-lg bg-slate-800 hover:bg-slate-700 border border-slate-700 text-xs text-slate-300">👁</a>
                                        <a href="{{ route('lots.edit', $lot) }}" class="px-2.5 py-1 rounded-lg bg-slate-800 hover:bg-slate-700 border border-slate-700 text-xs text-slate-200">✎ {{ __('Изм.') }}</a>
                                        <form action="{{ route('lots.destroy', $lot) }}" method="POST">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="px-2.5 py-1 rounded-lg bg-rose-500/10 text-rose-300 text-xs">✕</button>
                                        </form>
                                    </div>
                                @endif
                            </td>
                        </tr>
                        @endforeach
                    @endif
                </tbody>
            </table>
        </div>

        @if($lots->hasPages())
            <div class="pt-4 border-t border-slate-800 flex flex-col sm:flex-row items-center justify-between gap-3">
                <div class="text-xs text-slate-400">
                    <b class="text-white">{{ $lots->count() }}</b> / <b class="text-white">{{ $lots->total() }}</b> ({{ $lots->currentPage() }} / {{ $lots->lastPage() }})
                </div>

                <div class="flex items-center gap-2 w-full sm:w-auto justify-center">
                    @if(!$lots->onFirstPage())
                        <a href="{{ $lots->previousPageUrl() }}" class="px-3.5 py-2 rounded-xl bg-slate-800 hover:bg-slate-700 text-xs font-bold text-slate-200 transition">
                            ← {{ __('Предыдущие') }}
                        </a>
                    @endif

                    @if($lots->hasMorePages())
                        <a href="{{ $lots->nextPageUrl() }}" class="flex-1 sm:flex-initial text-center px-5 py-2 rounded-xl bg-emerald-500 hover:bg-emerald-400 text-xs font-extrabold text-slate-950 shadow-md shadow-emerald-500/10 transition">
                            {{ __('Показать еще 20 →') }}
                        </a>
                    @endif
                </div>
            </div>
        @endif
    </div>

    <!-- Правая колонка: Кошелек и Выставление лота с поддержкой локализации -->
    <div class="space-y-5 sm:space-y-6">
        <div class="bg-slate-900 border border-slate-800 rounded-2xl p-4 sm:p-5">
            <div class="flex items-center justify-between mb-3">
                <h3 class="text-base font-bold text-white">{{ __('Кошелек') }}</h3>
                <a href="{{ route('wallet.index') }}" class="text-xs text-emerald-400 hover:underline font-semibold">{{ __('Все операции →') }}</a>
            </div>

            <div class="grid grid-cols-2 gap-3 mb-4">
                <form action="{{ route('wallet.deposit') }}" method="POST" class="space-y-2">
                    @csrf
                    <input type="number" name="amount" value="2000" min="100" class="w-full bg-slate-800 border border-slate-700 rounded-lg px-3 py-1.5 text-sm text-white">
                    <button type="submit" class="w-full py-2 rounded-lg bg-emerald-500 text-slate-950 font-bold text-xs">{{ __('+ Пополнить') }}</button>
                </form>
                <form action="{{ route('wallet.withdraw') }}" method="POST" class="space-y-2">
                    @csrf
                    <input type="number" name="amount" value="1000" min="100" class="w-full bg-slate-800 border border-slate-700 rounded-lg px-3 py-1.5 text-sm text-white">
                    <button type="submit" class="w-full py-2 rounded-lg bg-slate-800 text-slate-200 font-bold text-xs">{{ __('→ Вывести') }}</button>
                </form>
            </div>

            <div class="space-y-2 max-h-56 overflow-y-auto pr-1">
                @foreach ($myTransactions as $tx)
                    @php
                        $isPositive = (float) $tx->amount >= 0;
                        $txTime = $tx->created_at
                            ? ($tx->created_at->isToday() ? __('Сегодня,') . ' ' . $tx->created_at->format('H:i') : $tx->created_at->format('d.m, H:i'))
                            : __('Только что');
                    @endphp
                    <div class="p-2.5 rounded-xl bg-slate-950/80 border border-slate-800/80 text-xs space-y-1">
                        <div class="flex items-center justify-between">
                            <span class="font-bold text-slate-200">{{ $tx->type->label() }}</span>
                            <span class="font-extrabold {{ $isPositive ? 'text-emerald-400' : 'text-rose-400' }}">
                                {{ $isPositive ? '+' : '' }}{{ number_format((float) $tx->amount, 0, '.', ' ') }} ₽
                            </span>
                        </div>
                        <div class="flex items-center justify-between text-[11px] text-slate-400">
                            <span>🕒 {{ $txTime }}</span>
                            @if($tx->order_id)
                                <a href="{{ route('orders.show', $tx->order_id) }}" class="text-indigo-400 hover:underline font-semibold truncate max-w-[150px]">
                                    {{ __('Заказ') }} #{{ $tx->order_id }}{{ $tx->order?->lot ? ' • ' . $tx->order->lot->title : '' }}
                                </a>
                            @else
                                <span class="text-slate-600">{{ number_format((float) $tx->balance_after, 0, '.', ' ') }} ₽</span>
                            @endif
                        </div>
                    </div>
                @endforeach
            </div>
        </div>

        <div class="bg-slate-900 border border-slate-800 rounded-2xl p-4 sm:p-5">
            <h3 class="text-base font-bold text-white mb-3">{{ __('Create a Lot') }}</h3>
            <form id="create-lot-form" action="{{ route('lots.store') }}" method="POST" enctype="multipart/form-data" class="space-y-3 text-sm">
                @csrf
                <input type="text" name="title" required placeholder="{{ __('Напр: Волейбол 6х6 сегодня в 19:00...') }}" class="w-full bg-slate-800 border border-slate-700 rounded-lg px-3 py-2 text-white">
                <div class="grid grid-cols-1 sm:grid-cols-3 gap-2">
                    <select name="product_type" class="bg-slate-800 border border-slate-700 rounded-lg px-2.5 py-2 text-white text-xs">
                        <option value="team_game">🏐 {{ __('Волейбол / Футбол') }}</option>
                        <option value="training_plan">💾 {{ __('Программа') }}</option>
                        <option value="coaching">🏋️ {{ __('Тренерство') }}</option>
                        <option value="race_slot">🎟️ {{ __('Слот на забег') }}</option>
                        <option value="equipment">👟 {{ __('Экипировка') }}</option>
                    </select>
                    <input type="number" name="price" value="500" min="50" required placeholder="{{ __('Цена (₽)') }}" class="bg-slate-800 border border-slate-700 rounded-lg px-3 py-2 text-white text-xs">
                    <input type="number" name="stock_quantity" value="12" min="1" max="1000" required placeholder="{{ __('Мест (шт.)') }}" class="w-full bg-slate-800 border border-slate-700 rounded-lg px-3 py-2 text-white text-xs">
                </div>

                <div class="p-3 rounded-xl bg-slate-950/90 border border-slate-800 space-y-2.5">
                    <div class="flex flex-wrap items-center justify-between gap-1">
                        <span class="text-[11px] text-emerald-400 font-bold">📍 {{ __('Адрес площадки (введите текст или кликните по карте)') }}</span>
                        <div class="flex gap-1 text-[10px]">
                            <button type="button" onclick="setCreateLotGeo('Москва, СК Лужники', 55.715765, 37.551521)" class="px-1.5 py-0.5 rounded bg-slate-800 text-slate-300 hover:text-white">МСК</button>
                            <button type="button" onclick="setCreateLotGeo('Нижний Новгород, ФОК Мещерский', 56.339900, 43.933200)" class="px-1.5 py-0.5 rounded bg-slate-800 text-slate-300 hover:text-white">НН</button>
                            <button type="button" onclick="detectCreateLotGeo()" class="px-1.5 py-0.5 rounded bg-emerald-500/20 text-emerald-300">GPS</button>
                        </div>
                    </div>

                    <div class="flex gap-1.5">
                        <input type="text" id="create_location_name" name="location_name" placeholder="{{ __('Введите адрес зала и нажмите Найти...') }}" class="flex-1 bg-slate-900 border border-slate-700 rounded-lg px-2.5 py-1.5 text-xs text-white">
                        <button type="button" onclick="searchAddressOnCreateMap()" class="px-2.5 py-1.5 rounded-lg bg-emerald-500 hover:bg-emerald-400 text-slate-950 font-extrabold text-xs shrink-0">
                            🔍 {{ __('Найти') }}
                        </button>
                    </div>

                    <div id="create-lot-map" class="w-full h-44 rounded-xl border border-slate-700 z-10"></div>

                    <div class="grid grid-cols-2 gap-2">
                        <input type="number" step="any" id="create_lat" name="latitude" placeholder="{{ __('Широта (55.75)') }}" class="w-full bg-slate-900 border border-slate-700 rounded-lg px-2.5 py-1.5 text-xs text-white">
                        <input type="number" step="any" id="create_lng" name="longitude" placeholder="{{ __('Долгота (37.61)') }}" class="w-full bg-slate-900 border border-slate-700 rounded-lg px-2.5 py-1.5 text-xs text-white">
                    </div>
                </div>

                <div>
                    <label class="block text-[11px] text-slate-400 font-semibold mb-1">📷 {{ __('Фото зала / лота (необязательно)') }}</label>
                    <input type="file" name="image" accept="image/*" class="w-full text-xs text-slate-300 file:mr-3 file:py-1.5 file:px-3 file:rounded-lg file:border-0 file:text-xs file:font-bold file:bg-slate-800 file:text-emerald-400 hover:file:bg-slate-700 cursor-pointer">
                </div>
                <textarea name="description" rows="2" placeholder="{{ __('Уровень игроков, покрытие, душ/раздевалка (необязательно)...') }}" class="w-full bg-slate-800 border border-slate-700 rounded-lg px-3 py-2 text-white"></textarea>
                <textarea name="auto_delivery_text" rows="2" placeholder="{{ __('⚡ Код от входа / ссылка на чат игроков после оплаты (необязательно)...') }}" class="w-full bg-slate-950 border border-emerald-500/40 rounded-lg px-3 py-2 text-xs text-emerald-300 placeholder-slate-500"></textarea>
                <button type="submit" class="w-full py-2.5 rounded-xl bg-indigo-500 hover:bg-indigo-400 text-white font-bold text-xs transition">{{ __('Опубликовать') }}</button>
            </form>
        </div>
    </div>
</div>

<div class="grid grid-cols-1 lg:grid-cols-2 gap-5 sm:gap-6">
    <div class="bg-slate-900 border border-slate-800 rounded-2xl p-4 sm:p-5">
        <h3 class="text-base font-bold text-white mb-3">{{ __('Мои покупки / Записи на игры') }}</h3>
        <div class="space-y-3">
            @foreach ($myPurchases as $order)
                <div class="p-3.5 rounded-xl bg-slate-800/60 border border-slate-700/70 flex flex-wrap items-center justify-between gap-2">
                    <div>
                        <a href="{{ route('orders.show', $order) }}" class="font-bold text-emerald-400 hover:underline text-sm">
                            {{ __('Заказ') }} #{{ $order->id }}: {{ $order->lot->title ?? 'Лот' }} ({{ $order->quantity }} {{ __('шт.') }})
                        </a>
                        @if($order->lot?->location_name)
                            <div class="text-[11px] text-emerald-300 font-semibold">📍 {{ $order->lot->location_name }}</div>
                        @endif
                        <div class="text-xs text-slate-400">{{ __('Продавец:') }} {{ $order->seller->username ?? '-' }} • {{ number_format((float) $order->total_price, 0, '.', ' ') }} ₽</div>
                    </div>
                    <a href="{{ route('orders.show', $order) }}" class="px-3 py-1.5 rounded-lg bg-slate-700 hover:bg-slate-600 text-xs text-white font-semibold">💬 {{ __('Чат и карта') }}</a>
                </div>
            @endforeach
        </div>
    </div>

    <div class="bg-slate-900 border border-slate-800 rounded-2xl p-4 sm:p-5">
        <h3 class="text-base font-bold text-white mb-3">{{ __('Мои продажи') }}</h3>
        <div class="space-y-3">
            @foreach ($mySales as $order)
                <div class="p-3.5 rounded-xl bg-slate-800/60 border border-slate-700/70 flex flex-wrap items-center justify-between gap-2">
                    <div>
                        <a href="{{ route('orders.show', $order) }}" class="font-bold text-emerald-400 hover:underline text-sm">
                            {{ __('Заказ') }} #{{ $order->id }}: {{ $order->lot->title ?? 'Лот' }} ({{ $order->quantity }} {{ __('шт.') }})
                        </a>
                        <div class="text-xs text-slate-400">{{ __('Покупатель:') }} {{ $order->buyer->username ?? '-' }} • +{{ number_format((float) $order->total_price, 0, '.', ' ') }} ₽</div>
                    </div>
                    <a href="{{ route('orders.show', $order) }}" class="px-3 py-1.5 rounded-lg bg-slate-700 hover:bg-slate-600 text-xs text-white font-semibold">💬 {{ __('Чат сделки') }}</a>
                </div>
            @endforeach
        </div>
    </div>
</div>

<script>
function setFilterGeo(lat, lng, defaultRadius = 30) {
    document.getElementById('filter_lat').value = lat;
    document.getElementById('filter_lng').value = lng;
    const radiusInput = document.querySelector('input[name="radius_km"]');
    if (radiusInput && !radiusInput.value) {
        radiusInput.value = defaultRadius;
    }
}

function detectFilterGeo() {
    if (!navigator.geolocation) return;
    navigator.geolocation.getCurrentPosition(function(pos) {
        setFilterGeo(pos.coords.latitude.toFixed(6), pos.coords.longitude.toFixed(6), 30);
    });
}

let createMap = null;
let createMarker = null;
let createGeoTimer = null;

async function reverseGeocodeCreate(lat, lng) {
    const input = document.getElementById('create_location_name');
    if (!input) return;
    input.value = `${Number(lat).toFixed(5)}, ${Number(lng).toFixed(5)}`;
    try {
        const res = await fetch(`https://nominatim.openstreetmap.org/reverse?format=json&lat=${encodeURIComponent(lat)}&lon=${encodeURIComponent(lng)}&zoom=17&accept-language=ru`);
        if (!res.ok) return;
        const data = await res.json();
        if (data && data.address) {
            const city = data.address.city || data.address.town || data.address.village || data.address.state || '';
            const road = data.address.road || data.address.pedestrian || data.address.suburb || '';
            const house = data.address.house_number || '';
            const place = data.address.stadium || data.address.sports_centre || data.address.leisure || '';
            const parts = [city, place, [road, house].filter(Boolean).join(' ')].filter(Boolean);
            if (parts.length > 0) {
                input.value = parts.join(', ');
            }
        }
    } catch (e) {}
}

async function searchAddressOnCreateMap() {
    const input = document.getElementById('create_location_name');
    const query = input?.value?.trim();
    if (!query) return;
    try {
        const res = await fetch(`https://nominatim.openstreetmap.org/search?format=json&q=${encodeURIComponent(query)}&limit=1&accept-language=ru`);
        if (!res.ok) return;
        const results = await res.json();
        if (Array.isArray(results) && results.length > 0) {
            const item = results[0];
            const lat = parseFloat(item.lat).toFixed(6);
            const lng = parseFloat(item.lon).toFixed(6);
            document.getElementById('create_lat').value = lat;
            document.getElementById('create_lng').value = lng;
            placeCreateMarker(lat, lng, query);
        }
    } catch (e) {}
}

function placeCreateMarker(lat, lng, customName = null) {
    if (!createMap || typeof L === 'undefined') return;
    const latNum = parseFloat(lat);
    const lngNum = parseFloat(lng);
    if (isNaN(latNum) || isNaN(lngNum)) return;

    if (!createMarker) {
        createMarker = L.marker([latNum, lngNum], { draggable: true }).addTo(createMap);
        createMarker.on('dragend', function (e) {
            const pos = e.target.getLatLng();
            document.getElementById('create_lat').value = pos.lat.toFixed(6);
            document.getElementById('create_lng').value = pos.lng.toFixed(6);
            reverseGeocodeCreate(pos.lat, pos.lng);
        });
    } else {
        createMarker.setLatLng([latNum, lngNum]);
    }
    createMap.setView([latNum, lngNum], 14);

    if (customName !== null) {
        document.getElementById('create_location_name').value = customName;
    } else {
        clearTimeout(createGeoTimer);
        createGeoTimer = setTimeout(() => reverseGeocodeCreate(latNum, lngNum), 300);
    }
}

function setCreateLotGeo(name, lat, lng) {
    document.getElementById('create_lat').value = lat;
    document.getElementById('create_lng').value = lng;
    placeCreateMarker(lat, lng, name);
}

function detectCreateLotGeo() {
    if (!navigator.geolocation) return;
    navigator.geolocation.getCurrentPosition(function(pos) {
        const lat = pos.coords.latitude.toFixed(6);
        const lng = pos.coords.longitude.toFixed(6);
        document.getElementById('create_lat').value = lat;
        document.getElementById('create_lng').value = lng;
        placeCreateMarker(lat, lng, null);
    });
}

document.addEventListener('DOMContentLoaded', function () {
    const mapContainer = document.getElementById('create-lot-map');
    if (mapContainer && typeof L !== 'undefined') {
        createMap = L.map('create-lot-map').setView([55.7558, 37.6173], 5);
        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
            maxZoom: 19,
            attribution: '© SportPay Maps'
        }).addTo(createMap);

        createMap.on('click', function (e) {
            const lat = e.latlng.lat.toFixed(6);
            const lng = e.latlng.lng.toFixed(6);
            document.getElementById('create_lat').value = lat;
            document.getElementById('create_lng').value = lng;
            placeCreateMarker(lat, lng, null);
        });
    }

    document.getElementById('create-lot-form')?.addEventListener('submit', async function (e) {
        const lat = document.getElementById('create_lat').value;
        const lng = document.getElementById('create_lng').value;
        const name = document.getElementById('create_location_name').value.trim();
        if ((!lat || !lng) && name) {
            e.preventDefault();
            await searchAddressOnCreateMap();
            this.submit();
        }
    });

    document.getElementById('create_location_name')?.addEventListener('keydown', function (e) {
        if (e.key === 'Enter') {
            e.preventDefault();
            searchAddressOnCreateMap();
        }
    });

    ['create_lat', 'create_lng'].forEach(id => {
        document.getElementById(id)?.addEventListener('input', function () {
            const lat = document.getElementById('create_lat').value;
            const lng = document.getElementById('create_lng').value;
            if (lat && lng) placeCreateMarker(lat, lng, null);
        });
    });
});

document.getElementById('lot-search-input')?.addEventListener('input', function (e) {
    const term = e.target.value.toLowerCase();
    document.querySelectorAll('.lot-row').forEach(row => {
        row.style.display = row.textContent.toLowerCase().includes(term) ? '' : 'none';
    });
});
</script>
@endsection
