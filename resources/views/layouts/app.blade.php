<!DOCTYPE html>
<html lang="ru" class="dark">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', 'SportPay — Биржа спортивных товаров и услуг')</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://js.pusher.com/8.2.0/pusher.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/laravel-echo@1.16.1/dist/echo.iife.js"></script>
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

    window.Echo.connector.pusher.connection.bind('connected', () => {
        console.log('[WebSocket] Успешно подключено к Reverb!');
    });
    window.Echo.connector.pusher.connection.bind('error', (err) => {
        console.error('[WebSocket] Ошибка подключения:', err);
    });
</script>
</head>
<body class="bg-slate-950 text-slate-100 min-h-screen font-sans">

<header class="border-b border-slate-800 bg-slate-900/80 backdrop-blur sticky top-0 z-30">
    <div class="max-w-7xl mx-auto px-4 py-3 flex flex-wrap items-center justify-between gap-4">
        <div class="flex items-center gap-3">
            <a href="{{ route('home') }}" class="text-2xl font-extrabold tracking-tight text-emerald-400 mr-2">
                SPORT<span class="text-white">PAY</span>
            </a>

            <a href="{{ route('home') }}" class="text-xs px-3 py-2 rounded-lg {{ request()->routeIs('home') ? 'bg-slate-800 text-white font-bold' : 'text-slate-300 hover:bg-slate-800/60' }} transition">
                Каталог лотов
            </a>

            <!-- Ссылка на личные сообщения (сделки) -->
            <a href="{{ route('chat.index') }}" class="relative flex items-center gap-2 text-xs px-3.5 py-2 rounded-lg {{ request()->routeIs('chat.index') || request()->routeIs('orders.show') ? 'bg-slate-800 text-white font-bold border border-slate-700' : 'bg-slate-800/60 hover:bg-slate-800 text-slate-200' }} transition">
                <span>💬 Сообщения</span>
                @if(($unreadMessagesCount ?? 0) > 0)
                    <span class="px-1.5 py-0.5 text-[11px] font-extrabold rounded-full bg-rose-500 text-white leading-none">
                        {{ $unreadMessagesCount }}
                    </span>
                @endif
            </a>

            <!-- Ссылка на глобальный общий чат -->
            <a href="{{ route('chat.global') }}" class="text-xs px-3.5 py-2 rounded-lg {{ request()->routeIs('chat.global') ? 'bg-slate-800 text-white font-bold' : 'text-slate-300 hover:bg-slate-800/60' }} transition">
                🌍 Общий чат
            </a>

            <!-- Ссылка на панель арбитража (если есть) -->
            @if(isset($user) && $user->isArbiter())
                <a href="{{ route('arbitration.index') }}" class="text-xs px-3.5 py-2 rounded-lg {{ request()->routeIs('arbitration.*') ? 'bg-rose-500/20 text-rose-300 font-bold' : 'text-slate-300 hover:bg-slate-800/60' }} transition">
                    ⚖️ Арбитраж
                </a>
            @endif

            @if(isset($user))
                <a href="{{ route('users.show', $user) }}" class="text-xs px-3 py-2 rounded-lg text-slate-300 hover:bg-slate-800/60 transition">
                    Мой профиль
                </a>
            @endif
        </div>

        <!-- Блок профиля и переключения юзеров в правой части шапки -->
        @if(isset($user))
        <div class="flex flex-wrap items-center gap-4">
            <a href="{{ route('users.show', $user) }}" class="flex items-center gap-4 bg-slate-800/90 hover:bg-slate-800 px-4 py-2 rounded-xl border border-slate-700 transition">
                <div>
                    <div class="text-xs text-slate-400">Аккаунт</div>
                    <div class="font-bold text-sm text-white flex items-center gap-1.5">
                        {{ $user->username }}
                        <span class="text-amber-400 text-xs">★ {{ number_format((float) $user->rating, 2) }}</span>
                    </div>
                </div>
                <div class="h-7 w-px bg-slate-700"></div>
                <div>
                    <div class="text-xs text-slate-400">Баланс</div>
                    <div class="font-extrabold text-emerald-400 text-sm">
                        {{ number_format((float) $user->wallet_balance, 2, '.', ' ') }} ₽
                    </div>
                </div>
            </a>

            @if(isset($allUsers) && $allUsers->isNotEmpty())
            <form action="{{ route('user.switch') }}" method="POST">
                @csrf
                <select name="user_id" onchange="this.form.submit()" class="bg-slate-800 border border-slate-700 text-xs rounded-lg px-3 py-2 text-slate-200">
                    @foreach($allUsers as $account)
                        <option value="{{ $account->id }}" @selected($account->id === $user->id)>
                            Юзер: {{ $account->username }} ({{ number_format((float) $account->wallet_balance, 0, '.', ' ') }} ₽)
                        </option>
                    @endforeach
                </select>
            </form>
            @endif
        </div>
        @endif
    </div>
</header>

<main class="max-w-7xl mx-auto px-4 py-6 space-y-6">
    @if(session('success'))
        <div class="p-4 rounded-xl bg-emerald-500/10 border border-emerald-500/30 text-emerald-300 text-sm font-medium">
            ✓ {{ session('success') }}
        </div>
    @endif

    @if(session('error'))
        <div class="p-4 rounded-xl bg-rose-500/10 border border-rose-500/30 text-rose-300 text-sm font-medium">
            ✕ {{ session('error') }}
        </div>
    @endif

    @if($errors->any())
        <div class="p-4 rounded-xl bg-rose-500/10 border border-rose-500/30 text-rose-300 text-sm">
            {{ $errors->first() }}
        </div>
    @endif

    @yield('content')
</main>
</body>
</html>