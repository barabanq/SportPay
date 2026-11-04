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
</head>
<body class="bg-slate-950 text-slate-100 min-h-screen font-sans antialiased">

<!-- Реактивная шапка на Vue 3 (работает всегда: и с Vite, и через встроенный Vue 3 инстанс) -->
<header id="sportpay-header-vue" class="border-b border-slate-800 bg-slate-900/90 backdrop-blur sticky top-0 z-40">
    <div class="max-w-7xl mx-auto px-3 sm:px-4 py-2.5 sm:py-3">
        <div class="flex items-center justify-between gap-2">
            <!-- Лого + Переключатель RU / EN -->
            <div class="flex items-center gap-2 sm:gap-3">
                <a href="{{ route('home') }}" class="text-xl sm:text-2xl font-extrabold tracking-tight text-emerald-400 flex items-center gap-1.5">
                    <span>SPORT<span class="text-white">PAY</span></span>
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

            <!-- Десктопное меню -->
            <nav class="hidden lg:flex items-center gap-1.5">
                <a href="{{ route('home') }}" class="text-xs px-3 py-2 rounded-lg {{ request()->routeIs('home') ? 'bg-slate-800 text-white font-bold' : 'text-slate-300 hover:bg-slate-800/60' }} transition">
                    {{ __('Каталог лотов') }}
                </a>

                <a href="{{ route('chat.index') }}" class="text-xs px-3 py-2 rounded-lg {{ request()->routeIs('chat.index') ? 'bg-slate-800 text-white font-bold' : 'text-slate-300 hover:bg-slate-800/60' }} transition flex items-center gap-1.5">
                    <span>💬 {{ __('Сообщения') }}</span>
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

                <!-- Выпадающее меню «Помощь» под управлением Vue 3 -->
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

            <!-- Правая панель: Баланс, Профиль, Dev-переключатель и Мобильный бургер -->
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

        <!-- Выпадающее мобильное меню под управлением Vue 3 -->
        <div v-cloak v-show="mobileOpen" class="lg:hidden mt-3 pt-3 border-t border-slate-800 space-y-3">
            <div class="grid grid-cols-2 sm:grid-cols-3 gap-2 text-xs">
                <a href="{{ route('home') }}" class="px-3 py-2.5 rounded-xl {{ request()->routeIs('home') ? 'bg-emerald-500 text-slate-950 font-extrabold' : 'bg-slate-800/80 text-slate-200 font-semibold' }}">
                    🏪 {{ __('Каталог лотов') }}
                </a>
                <a href="{{ route('chat.index') }}" class="px-3 py-2.5 rounded-xl {{ request()->routeIs('chat.index') ? 'bg-emerald-500 text-slate-950 font-extrabold' : 'bg-slate-800/80 text-slate-200 font-semibold' }} flex items-center justify-between">
                    <span>💬 {{ __('Сообщения') }}</span>
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
</body>
</html>
