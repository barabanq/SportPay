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
    </script>
</head>
<body class="bg-slate-950 text-slate-100 min-h-screen font-sans">

<header class="border-b border-slate-800 bg-slate-900/80 backdrop-blur sticky top-0 z-30">
    <div class="max-w-7xl mx-auto px-4 py-3 flex flex-wrap items-center justify-between gap-4">
        <div class="flex flex-wrap items-center gap-2">
            <a href="{{ route('home') }}" class="text-2xl font-extrabold tracking-tight text-emerald-400 mr-2">
                SPORT<span class="text-white">PAY</span>
            </a>

            <a href="{{ route('home') }}" class="text-xs px-3 py-2 rounded-lg {{ request()->routeIs('home') ? 'bg-slate-800 text-white font-bold' : 'text-slate-300 hover:bg-slate-800/60' }} transition">
                Каталог лотов
            </a>

            <a href="{{ route('chat.index') }}" class="text-xs px-3.5 py-2 rounded-lg {{ request()->routeIs('chat.index') ? 'bg-slate-800 text-white font-bold' : 'text-slate-300 hover:bg-slate-800/60' }} transition flex items-center gap-1.5">
                <span>💬 Сообщения</span>
                @if(!empty($unreadMessagesCount))
                    <span class="px-1.5 py-0.5 text-[10px] font-extrabold rounded-full bg-rose-500 text-white">
                        +{{ $unreadMessagesCount }}
                    </span>
                @endif
            </a>

            <a href="{{ route('chat.global') }}" class="text-xs px-3.5 py-2 rounded-lg {{ request()->routeIs('chat.global') ? 'bg-slate-800 text-white font-bold' : 'text-slate-300 hover:bg-slate-800/60' }} transition">
                🌍 Общий чат
            </a>

            <a href="{{ route('wallet.index') }}" class="text-xs px-3.5 py-2 rounded-lg {{ request()->routeIs('wallet.*') ? 'bg-slate-800 text-white font-bold' : 'text-slate-300 hover:bg-slate-800/60' }} transition">
                💳 Финансы
            </a>

            <a href="{{ route('arbitration.index') }}" class="text-xs px-3.5 py-2 rounded-lg {{ request()->routeIs('arbitration.*') ? 'bg-slate-800 text-white font-bold' : 'text-slate-300 hover:bg-slate-800/60' }} transition flex items-center gap-1.5">
                <span>⚖️ Арбитраж</span>
                @if(!empty($disputedOrdersCount))
                    <span class="px-1.5 py-0.5 text-[10px] font-extrabold rounded-full bg-amber-500 text-slate-950">
                        {{ $disputedOrdersCount }}
                    </span>
                @endif
            </a>
        </div>

        <div class="flex items-center gap-3">
            @if(isset($user))
                <a href="{{ route('users.show', $user->id) }}" class="text-xs text-slate-300 hover:text-emerald-400 font-bold px-2.5 py-1.5 rounded-lg bg-slate-800/60 border border-slate-800 transition">
                    👤 {{ $user->username }}
                </a>

                <a href="{{ route('wallet.index') }}" class="bg-slate-800/90 hover:bg-slate-800 px-3.5 py-1.5 rounded-xl border border-slate-700 transition">
                    <div class="text-[10px] text-slate-400">Баланс кошелька</div>
                    <div class="font-extrabold text-emerald-400 text-xs">
                        {{ number_format((float) $user->wallet_balance, 2, '.', ' ') }} ₽
                    </div>
                </a>
            @endif

            @if(isset($allUsers) && $allUsers->isNotEmpty())
                <form action="{{ route('user.switch') }}" method="POST" class="flex items-center gap-2 bg-slate-800/90 px-3 py-1.5 rounded-xl border border-slate-700">
                    @csrf
                    <label for="switch-user-select" class="text-[10px] text-slate-400 uppercase font-bold">Аккаунт:</label>
                    <select id="switch-user-select" name="user_id" onchange="this.form.submit()" class="bg-slate-950 border border-slate-700 rounded-lg px-2.5 py-1 text-xs text-white font-semibold focus:outline-none focus:border-emerald-500 cursor-pointer">
                        @foreach ($allUsers as $u)
                            <option value="{{ $u->id }}" @selected(isset($user) && $user->id === $u->id)>
                                {{ $u->username }} ({{ number_format((float) $u->wallet_balance, 0, '.', ' ') }} ₽)
                            </option>
                        @endforeach
                    </select>
                </form>
            @endif
        </div>
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
