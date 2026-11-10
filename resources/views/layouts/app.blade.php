<!DOCTYPE html>
<html lang="{{ app()->getLocale() }}" class="dark">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=5.0">
    <title>@yield('title', 'SportPay — P2P Sports Marketplace')</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://unpkg.com/vue@3/dist/vue.global.prod.js"></script>
    <script src="https://js.pusher.com/8.2.0/pusher.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/laravel-echo@1.16.1/dist/echo.iife.js"></script>
    <link rel="stylesheet" href="https://unpkg.com/maplibre-gl@4.7.1/dist/maplibre-gl.css" />
    <script src="https://unpkg.com/maplibre-gl@4.7.1/dist/maplibre-gl.js"></script>
    <script>
    // Адаптер интерактивных карт на базе нейтрального движка MapLibre GL JS (без Leaflet)
    window.L = {
        map: function (containerId) {
            const instance = {
                _map: null,
                _clickHandlers: [],
                setView: function (coords, zoom) {
                    const lat = Number(coords[0]) || 55.7558;
                    const lng = Number(coords[1]) || 37.6173;
                    const z = Math.max(2, (Number(zoom) || 13) - 1);
                    if (!this._map) {
                        this._map = new maplibregl.Map({
                            container: containerId,
                            style: {
                                version: 8,
                                sources: {
                                    osm: {
                                        type: 'raster',
                                        tiles: ['https://tile.openstreetmap.org/{z}/{x}/{y}.png'],
                                        tileSize: 256,
                                        attribution: 'SportPay Maps'
                                    }
                                },
                                layers: [{ id: 'osm', type: 'raster', source: 'osm' }]
                            },
                            center: [lng, lat],
                            zoom: z,
                            attributionControl: false
                        });
                        this._map.addControl(new maplibregl.NavigationControl({ showCompass: false }), 'top-right');
                        this._map.on('click', (e) => {
                            this._clickHandlers.forEach(fn => fn({ latlng: { lat: e.lngLat.lat, lng: e.lngLat.lng } }));
                        });
                    } else {
                        this._map.JumpTo ? this._map.jumpTo({ center: [lng, lat], zoom: z }) : this._map.setCenter([lng, lat]);
                    }
                    return this;
                },
                on: function (event, handler) {
                    if (event === 'click') this._clickHandlers.push(handler);
                    return this;
                },
                removeLayer: function (markerWrapper) {
                    if (markerWrapper && markerWrapper._mlMarker) markerWrapper._mlMarker.remove();
                }
            };
            return instance;
        },
        tileLayer: function () {
            return { addTo: function () { return this; } };
        },
        marker: function (coords, options = {}) {
            let lat = Number(coords[0]) || 55.7558;
            let lng = Number(coords[1]) || 37.6173;
            const mlMarker = new maplibregl.Marker({
                color: '#10b981',
                draggable: Boolean(options.draggable)
            }).setLngLat([lng, lat]);

            return {
                _mlMarker: mlMarker,
                addTo: function (mapWrapper) {
                    if (mapWrapper && mapWrapper._map) {
                        mlMarker.addTo(mapWrapper._map);
                    }
                    return this;
                },
                setLatLng: function (newCoords) {
                    const nLat = Array.isArray(newCoords) ? Number(newCoords[0]) : Number(newCoords.lat);
                    const nLng = Array.isArray(newCoords) ? Number(newCoords[1]) : Number(newCoords.lng);
                    mlMarker.setLngLat([nLng, nLat]);
                    return this;
                },
                on: function (event, fn) {
                    if (event === 'dragend') {
                        mlMarker.on('dragend', () => {
                            const pos = mlMarker.getLngLat();
                            fn({ target: { getLatLng: () => ({ lat: pos.lat, lng: pos.lng }) } });
                        });
                    }
                    return this;
                },
                bindPopup: function (text) {
                    const popup = new maplibregl.Popup({ offset: 25 }).setText(String(text || ''));
                    mlMarker.setPopup(popup);
                    return this;
                },
                openPopup: function () {
                    const popup = mlMarker.getPopup();
                    if (popup && !popup.isOpen()) mlMarker.togglePopup();
                    return this;
                }
            };
        }
    };
    </script>
    @php
        $viteReady = file_exists(public_path('hot'));
        if (!$viteReady && file_exists(public_path('build/manifest.json'))) {
            $manifest = json_decode((string) file_get_contents(public_path('build/manifest.json')), true);
            $viteReady = is_array($manifest) && isset($manifest['resources/js/app.js']);
        }
    @endphp
    @if ($viteReady)
        @vite(['resources/js/app.js'])
    @endif
    <style>
        [v-cloak] { display: none; }
        .no-scrollbar::-webkit-scrollbar { display: none; }
        .no-scrollbar { -ms-overflow-style: none; scrollbar-width: none; }
        .maplibregl-popup-content { background: #0f172a !important; color: #f8fafc !important; border: 1px solid #334155; border-radius: 0.75rem !important; padding: 8px 12px !important; font-size: 12px; font-weight: 700; }
        .maplibregl-popup-tip { border-top-color: #0f172a !important; }
    </style>
    <script>
    window.Pusher = Pusher;
    window.Echo = new Echo({
        broadcaster: 'reverb',
        key: '{{ config("broadcasting.connections.reverb.key", "sportpay-key") }}',
        wsHost: '{{ config("broadcasting.connections.reverb.options.host", "127.0.0.1") }}',
        wsPort: {{ (int) config("broadcasting.connections.reverb.options.port", 8080) }},
        wssPort: {{ (int) config("broadcasting.connections.reverb.options.port", 8080) }},
        forceTLS: false,
        enabledTransports: ['ws', 'wss'],
    });
    </script>
        <link rel="icon" type="image/png" href="/images/sportpay-bot-avatar.png?v=9">
</head>
<body class="bg-slate-950 text-slate-100 min-h-screen font-sans antialiased">

<header id="sportpay-header-vue" class="border-b border-slate-800 bg-slate-900/90 backdrop-blur sticky top-0 z-40">
    <div class="max-w-7xl mx-auto px-3 sm:px-4 py-2.5 sm:py-3">
        <div class="flex items-center justify-between gap-2">
            <div class="flex items-center gap-2 sm:gap-3">
                <a href="{{ route('home') }}" class="text-xl sm:text-2xl font-extrabold tracking-tight text-emerald-400 flex items-center gap-1.5">
                    <span class="relative inline-flex items-center justify-center w-9 h-9 rounded-xl bg-slate-900 border border-emerald-500/40 shadow-[0_0_15px_rgba(16,185,129,0.45)] mr-2 shrink-0">
    <img src="/images/sportpay-logo.svg" alt="SportPay" class="w-7 h-7">
    <span class="absolute -top-0.5 -right-0.5 w-2.5 h-2.5 rounded-full bg-emerald-400 animate-ping"></span>
    <span class="absolute -top-0.5 -right-0.5 w-2.5 h-2.5 rounded-full bg-emerald-400"></span>
</span><span>SPORT<span class="text-white">PAY</span></span>
                    <span class="text-[10px] px-1.5 py-0.5 rounded bg-emerald-500/15 text-emerald-300 border border-emerald-500/30 font-mono uppercase">Vue 3</span>
                </a>

                <form action="{{ route('locale.switch') }}" method="POST" class="inline-flex rounded-xl bg-slate-950 p-0.5 border border-slate-800">
                    @csrf
                    <button type="submit" name="locale" value="ru" class="px-2 py-1 rounded-lg text-[11px] font-extrabold transition {{ app()->getLocale() === 'ru' ? 'bg-emerald-500 text-slate-950' : 'text-slate-400 hover:text-white' }}">
                        RU
                    </button>
                    <button type="submit" name="locale" value="en" class="px-2 py-1 rounded-lg text-[11px] font-extrabold transition {{ app()->getLocale() === 'en' ? 'bg-emerald-500 text-slate-950' : 'text-slate-400 hover:text-white' }}">
                        EN
                    </button>
                </form>
            </div>

            <nav class="hidden lg:flex items-center gap-1.5">
                <a href="{{ route('home') }}" class="text-xs px-3 py-2 rounded-lg {{ request()->routeIs('home') ? 'bg-slate-800 text-white font-bold' : 'text-slate-300 hover:bg-slate-800/60' }} transition">
                    {{ __('Каталог лотов') }}
                </a>

                <a href="{{ route('chat.index') }}" class="text-xs px-3 py-2 rounded-lg {{ request()->routeIs('chat.index') ? 'bg-slate-800 text-white font-bold' : 'text-slate-300 hover:bg-slate-800/60' }} transition flex items-center gap-1.5">
                    <span>💬 {{ __('Сообщения') }}</span>
                    @php
    $__u = $currentUser ?? $user ?? null;
    if ($__u && request()->routeIs('chat.*') && method_exists($__u, 'markMessagesAsRead')) {
        $__u->markMessagesAsRead();
    }
    $unreadMessagesCount = ($__u && method_exists($__u, 'unreadMessagesCount')) ? $__u->unreadMessagesCount() : 0;
@endphp
@if(!empty($unreadMessagesCount))
                        <span class="px-1.5 py-0.5 text-[10px] font-extrabold rounded-full bg-rose-500 text-white">
                            +{{ $unreadMessagesCount }}
                        </span>
                    @endif
                </a>

                <a href="{{ route('chat.global') }}" class="text-xs px-3 py-2 rounded-lg {{ request()->routeIs('chat.global') ? 'bg-slate-800 text-white font-bold' : 'text-slate-300 hover:bg-slate-800/60' }} transition">
                    🌍 {{ __('Общий чат') }}
                </a>

                <a href="{{ route('wallet.index') }}" class="text-xs px-3 py-2 rounded-lg {{ request()->routeIs('wallet.*') ? 'bg-slate-800 text-white font-bold' : 'text-slate-300 hover:bg-slate-800/60' }} transition">
                    💳 {{ __('Финансы') }}
                </a>

                <a href="{{ route('arbitration.index') }}" class="text-xs px-3 py-2 rounded-lg {{ request()->routeIs('arbitration.*') ? 'bg-slate-800 text-white font-bold' : 'text-slate-300 hover:bg-slate-800/60' }} transition flex items-center gap-1.5">
                    <span>⚖️ {{ __('Арбитраж') }}</span>
                    @if(!empty($disputedOrdersCount))
                        <span class="px-1.5 py-0.5 text-[10px] font-extrabold rounded-full bg-amber-500 text-slate-950">
                            {{ $disputedOrdersCount }}
                        </span>
                    @endif
                </a>

                <div class="relative">
                    <button type="button" @click.stop="helpOpen = !helpOpen" class="text-xs px-3 py-2 rounded-lg {{ request()->routeIs('support.*') ? 'bg-slate-800 text-white font-bold' : 'text-slate-300 hover:bg-slate-800/60' }} transition flex items-center gap-1">
                        <span>{{ __('Помощь') }}</span>
                        <svg class="w-3.5 h-3.5 text-slate-400 transition-transform" :class="{ 'rotate-180': helpOpen }" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                        </svg>
                    </button>

                    <div v-cloak v-show="helpOpen" class="absolute left-0 mt-2 w-48 rounded-xl bg-slate-900 border border-slate-700 shadow-2xl py-1.5 z-50 divide-y divide-slate-800/80 text-xs">
                        <div class="py-1">
                            <a href="{{ route('support.rules') }}" class="block px-4 py-2 text-slate-200 hover:bg-slate-800 hover:text-emerald-400 transition">
                                📜 {{ __('Правила') }}
                            </a>
                            <a href="{{ route('support.help') }}" class="block px-4 py-2 text-slate-200 hover:bg-slate-800 hover:text-emerald-400 transition">
                                💡 {{ __('Центр помощи') }}
                            </a>
                        </div>
                        <div class="py-1">
                            <a href="{{ route('support.tickets.create') }}" class="block px-4 py-2 text-slate-200 hover:bg-slate-800 hover:text-emerald-400 transition">
                                ✉️ {{ __('Отправить запрос') }}
                            </a>
                            <a href="{{ route('support.tickets.index') }}" class="block px-4 py-2 text-slate-200 hover:bg-slate-800 hover:text-emerald-400 transition">
                                📂 {{ __('Мои запросы') }}
                            </a>
                        </div>
                    </div>
                </div>
            </nav>

            <div class="flex items-center gap-2">
                @if(isset($user))
                    <a href="{{ route('wallet.index') }}" class="bg-slate-800/90 hover:bg-slate-800 px-2.5 sm:px-3.5 py-1.5 rounded-xl border border-slate-700 transition">
                        <div class="text-[9px] sm:text-[10px] text-slate-400 leading-none">{{ __('Баланс') }}</div>
                        <div class="font-extrabold text-emerald-400 text-xs mt-0.5">
                            {{ number_format((float) $user->wallet_balance, 0, '.', ' ') }} ₽
                        </div>
                    </a>

                    <a href="{{ route('users.show', $user->id) }}" class="hidden sm:inline-flex items-center gap-1.5 text-xs text-slate-200 hover:text-emerald-400 font-bold px-3 py-2 rounded-xl bg-slate-800/80 border border-slate-700 transition">
                        <span>👤 {{ $user->username }}</span>
                    </a>

                    <form action="{{ route('logout') }}" method="POST" class="hidden sm:block">
                        @csrf
                        <button type="submit" class="px-2.5 py-2 rounded-xl bg-slate-800 hover:bg-rose-500/20 border border-slate-700 text-xs text-slate-300 hover:text-rose-300 transition">
                            {{ __('Выйти') }}
                        </button>
                    </form>
                @else
                    <a href="{{ route('login') }}" class="px-3 py-1.5 rounded-xl bg-slate-800 hover:bg-slate-700 text-xs font-bold text-white transition">
                        {{ __('Войти') }}
                    </a>
                @endif

                @if(isset($allUsers) && $allUsers->isNotEmpty())
                    <form action="{{ route('user.switch') }}" method="POST" class="hidden xl:flex items-center gap-1.5 bg-slate-950/90 px-2.5 py-1.5 rounded-xl border border-slate-800">
                        @csrf
                        <label for="switch-user-select" class="text-[10px] text-slate-500 uppercase font-bold">Dev:</label>
                        <select id="switch-user-select" name="user_id" onchange="this.form.submit()" class="bg-slate-900 border border-slate-700 rounded-lg px-2 py-1 text-[11px] text-slate-200 font-semibold focus:outline-none focus:border-emerald-500 cursor-pointer">
                            @foreach ($allUsers as $u)
                                <option value="{{ $u->id }}" @selected(isset($user) && $user->id === $u->id)>
                                    {{ $u->username }} ({{ number_format((float) $u->wallet_balance, 0, '.', ' ') }} ₽)
                                </option>
                            @endforeach
                        </select>
                    </form>
                @endif

                <button type="button" @click="mobileOpen = !mobileOpen" aria-label="Menu" class="lg:hidden p-2 rounded-xl bg-slate-800 border border-slate-700 text-slate-200 hover:text-emerald-400">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/>
                    </svg>
                </button>
            </div>
        </div>

        <div v-cloak v-show="mobileOpen" class="lg:hidden mt-3 pt-3 border-t border-slate-800 space-y-3">
            <div class="grid grid-cols-2 sm:grid-cols-3 gap-2 text-xs">
                <a href="{{ route('home') }}" class="px-3 py-2.5 rounded-xl {{ request()->routeIs('home') ? 'bg-emerald-500 text-slate-950 font-extrabold' : 'bg-slate-800/80 text-slate-200 font-semibold' }}">
                    🏪 {{ __('Каталог лотов') }}
                </a>
                <a href="{{ route('chat.index') }}" class="px-3 py-2.5 rounded-xl {{ request()->routeIs('chat.index') ? 'bg-emerald-500 text-slate-950 font-extrabold' : 'bg-slate-800/80 text-slate-200 font-semibold' }} flex items-center justify-between">
                    <span>💬 {{ __('Сообщения') }}</span>
                    @php
    $__u = $currentUser ?? $user ?? null;
    if ($__u && request()->routeIs('chat.*') && method_exists($__u, 'markMessagesAsRead')) {
        $__u->markMessagesAsRead();
    }
    $unreadMessagesCount = ($__u && method_exists($__u, 'unreadMessagesCount')) ? $__u->unreadMessagesCount() : 0;
@endphp
@if(!empty($unreadMessagesCount))
                        <span class="px-1.5 py-0.5 text-[10px] font-extrabold rounded-full bg-rose-500 text-white">+{{ $unreadMessagesCount }}</span>
                    @endif
                </a>
                <a href="{{ route('chat.global') }}" class="px-3 py-2.5 rounded-xl {{ request()->routeIs('chat.global') ? 'bg-emerald-500 text-slate-950 font-extrabold' : 'bg-slate-800/80 text-slate-200 font-semibold' }}">
                    🌍 {{ __('Общий чат') }}
                </a>
                <a href="{{ route('wallet.index') }}" class="px-3 py-2.5 rounded-xl {{ request()->routeIs('wallet.*') ? 'bg-emerald-500 text-slate-950 font-extrabold' : 'bg-slate-800/80 text-slate-200 font-semibold' }}">
                    💳 {{ __('Финансы') }}
                </a>
                <a href="{{ route('arbitration.index') }}" class="px-3 py-2.5 rounded-xl {{ request()->routeIs('arbitration.*') ? 'bg-emerald-500 text-slate-950 font-extrabold' : 'bg-slate-800/80 text-slate-200 font-semibold' }} flex items-center justify-between">
                    <span>⚖️ {{ __('Арбитраж') }}</span>
                    @if(!empty($disputedOrdersCount))
                        <span class="px-1.5 py-0.5 text-[10px] font-extrabold rounded-full bg-amber-500 text-slate-950">{{ $disputedOrdersCount }}</span>
                    @endif
                </a>
                <a href="{{ route('support.help') }}" class="px-3 py-2.5 rounded-xl bg-slate-800/80 text-slate-200 font-semibold">
                    💡 {{ __('Центр помощи') }}
                </a>
            </div>
        </div>
    </div>
</header>

<main class="max-w-7xl mx-auto px-3 sm:px-4 py-4 sm:py-6 space-y-5 sm:space-y-6">
    @if(session('success'))
        <div class="p-3.5 sm:p-4 rounded-xl bg-emerald-500/10 border border-emerald-500/30 text-emerald-300 text-xs sm:text-sm font-medium">
            ✓ {{ session('success') }}
        </div>
    @endif

    @if(session('error'))
        <div class="p-3.5 sm:p-4 rounded-xl bg-rose-500/10 border border-rose-500/30 text-rose-300 text-xs sm:text-sm font-medium">
            ✕ {{ session('error') }}
        </div>
    @endif

    @if($errors->any())
        <div class="p-3.5 sm:p-4 rounded-xl bg-rose-500/10 border border-rose-500/30 text-rose-300 text-xs sm:text-sm">
            {{ $errors->first() }}
        </div>
    @endif

    @yield('content')
</main>

<script>
if (window.Vue && document.getElementById('sportpay-header-vue')) {
    const { createApp, ref, onMounted, onBeforeUnmount } = window.Vue;
    createApp({
        setup() {
            const helpOpen = ref(false);
            const mobileOpen = ref(false);
            const closeHelp = () => { helpOpen.value = false; };
            onMounted(() => document.addEventListener('click', closeHelp));
            onBeforeUnmount(() => document.removeEventListener('click', closeHelp));
            return { helpOpen, mobileOpen };
        }
    }).mount('#sportpay-header-vue');
}
</script>
<!-- SPORTPAY_SOUND_ENGINE_START -->
<div id="sportpay-live-toast" class="fixed bottom-5 right-5 z-50 hidden max-w-sm p-4 rounded-2xl bg-slate-900/95 border border-emerald-500/50 shadow-2xl text-xs text-white space-y-1.5">
    <div class="flex items-center justify-between gap-3">
        <span id="sportpay-toast-title" class="font-extrabold text-emerald-400">🔔 SportPay</span>
        <button type="button" onclick="document.getElementById('sportpay-live-toast').classList.add('hidden')" class="text-slate-400 hover:text-white">✕</button>
    </div>
    <div id="sportpay-toast-body" class="text-slate-200 leading-relaxed"></div>
    <a id="sportpay-toast-link" href="#" class="inline-block pt-1 text-emerald-400 font-bold hover:underline">Открыть →</a>
</div>

<script>
(function () {
    let audioCtx = null;

    function getAudioCtx() {
        if (!audioCtx) {
            const Ctx = window.AudioContext || window.webkitAudioContext;
            if (Ctx) audioCtx = new Ctx();
        }
        if (audioCtx && audioCtx.state === 'suspended') {
            audioCtx.resume().catch(() => {});
        }
        return audioCtx;
    }

    document.addEventListener('click', () => getAudioCtx(), { once: true });

    function playNote(freq, delay, duration, type = 'sine', peak = 0.16) {
        const ctx = getAudioCtx();
        if (!ctx) return;
        const osc = ctx.createOscillator();
        const gain = ctx.createGain();
        osc.type = type;
        osc.frequency.setValueAtTime(freq, ctx.currentTime + delay);

        gain.gain.setValueAtTime(0.0001, ctx.currentTime + delay);
        gain.gain.exponentialRampToValueAtTime(peak, ctx.currentTime + delay + 0.02);
        gain.gain.exponentialRampToValueAtTime(0.0001, ctx.currentTime + delay + duration);

        osc.connect(gain);
        gain.connect(ctx.destination);
        osc.start(ctx.currentTime + delay);
        osc.stop(ctx.currentTime + delay + duration + 0.02);
    }

    window.SportPaySound = {
        playMessage: function () {
            try {
                playNote(659.25, 0.00, 0.14, 'sine', 0.15); // E5
                playNote(880.00, 0.12, 0.22, 'sine', 0.18); // A5
            } catch (e) {}
        },
        playPurchase: function () {
            try {
                playNote(523.25, 0.00, 0.11, 'triangle', 0.18); // C5
                playNote(659.25, 0.10, 0.11, 'triangle', 0.18); // E5
                playNote(783.99, 0.20, 0.13, 'triangle', 0.20); // G5
                playNote(1046.50, 0.32, 0.28, 'triangle', 0.22); // C6
            } catch (e) {}
        }
    };

    function showToast(title, body, link) {
        const toast = document.getElementById('sportpay-live-toast');
        if (!toast) return;
        document.getElementById('sportpay-toast-title').textContent = title;
        document.getElementById('sportpay-toast-body').textContent = body;
        document.getElementById('sportpay-toast-link').href = link || '#';
        toast.classList.remove('hidden');
        setTimeout(() => toast.classList.add('hidden'), 7000);
    }

    // Звук при создании/оплате заказа
    document.addEventListener('DOMContentLoaded', function () {
        const pageText = document.body.innerText || '';
        if (window.location.pathname.startsWith('/orders/') && (pageText.includes('зарезервированы') || pageText.includes('оплачена') || pageText.includes('escrow'))) {
            if (!sessionStorage.getItem('sp_played_order_' + window.location.pathname)) {
                sessionStorage.setItem('sp_played_order_' + window.location.pathname, '1');
                setTimeout(() => window.SportPaySound.playPurchase(), 250);
            }
        }
    });

    setInterval(async function () {
        try {
            const box = document.getElementById('chat-messages-box');
            const partnerId = box ? (box.dataset.partnerId || '') : '';
            const afterId = box ? (box.dataset.lastId || '0') : '0';

            const res = await fetch(`/messages/poll?partner_id=${encodeURIComponent(partnerId)}&after_id=${encodeURIComponent(afterId)}`, {
                headers: { 'Accept': 'application/json' }
            });
            if (!res.ok) return;
            const data = await res.json();

            if (Array.isArray(data.chat_messages) && data.chat_messages.length > 0 && window.appendLiveChatMessages) {
                const hasIncoming = data.chat_messages.some(m => !m.is_mine);
                window.appendLiveChatMessages(data.chat_messages);
                if (hasIncoming) window.SportPaySound.playMessage();
            }

            if (data.latest_incoming_message && data.latest_incoming_message.id) {
                const msgId = String(data.latest_incoming_message.id);
                const prevMsg = sessionStorage.getItem('sp_last_msg_id');
                if (prevMsg === null) {
                    sessionStorage.setItem('sp_last_msg_id', msgId);
                } else if (Number(msgId) > Number(prevMsg)) {
                    sessionStorage.setItem('sp_last_msg_id', msgId);
                    if (!box) {
                        window.SportPaySound.playMessage();
                        showToast(
                            '💬 Сообщение от ' + data.latest_incoming_message.sender_name,
                            data.latest_incoming_message.body,
                            '/messages?partner=' + data.latest_incoming_message.partner_id
                        );
                    }
                }
            }

            if (data.latest_seller_order && data.latest_seller_order.id) {
                const ordId = String(data.latest_seller_order.id);
                const prevOrd = sessionStorage.getItem('sp_last_ord_id');
                if (prevOrd === null) {
                    sessionStorage.setItem('sp_last_ord_id', ordId);
                } else if (Number(ordId) > Number(prevOrd)) {
                    sessionStorage.setItem('sp_last_ord_id', ordId);
                    window.SportPaySound.playPurchase();
                    showToast(
                        '💰 Новая покупка лота!',
                        `${data.latest_seller_order.buyer} купил «${data.latest_seller_order.title}» (Заказ #${ordId})`,
                        '/orders/' + ordId
                    );
                }
            }
        } catch (e) {}
    }, 4000);
})();
</script>
<!-- SPORTPAY_SOUND_ENGINE_END -->
</body>
</html>
