<!DOCTYPE html>
<html lang="ru" class="dark">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SportPay — Биржа спортивных товаров и услуг</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-slate-950 text-slate-100 min-h-screen font-sans">

<header class="border-b border-slate-800 bg-slate-900/80 backdrop-blur sticky top-0 z-30">
    <div class="max-w-7xl mx-auto px-4 py-3 flex flex-wrap items-center justify-between gap-4">
        <div class="flex items-center gap-3">
            <a href="{{ route('home') }}" class="text-2xl font-extrabold tracking-tight text-emerald-400">
                SPORT<span class="text-white">PAY</span>
            </a>
            <span class="text-xs px-2.5 py-1 rounded-full bg-emerald-500/10 text-emerald-400 border border-emerald-500/20">
                P2P Escrow Биржа
            </span>
        </div>

        @if($user)
        <div class="flex flex-wrap items-center gap-4">
            <div class="flex items-center gap-4 bg-slate-800/90 px-4 py-2 rounded-xl border border-slate-700">
                <div>
                    <div class="text-xs text-slate-400">Аккаунт</div>
                    <div class="font-bold text-sm text-white flex items-center gap-1.5">
                        {{ $user->username }}
                        <span class="text-amber-400 text-xs">★ {{ number_format((float)$user->rating, 2) }}</span>
                    </div>
                </div>
                <div class="h-7 w-px bg-slate-700"></div>
                <div>
                    <div class="text-xs text-slate-400">Баланс кошелька</div>
                    <div class="font-extrabold text-emerald-400 text-sm">
                        {{ number_format((float)$user->wallet_balance, 2, '.', ' ') }} ₽
                    </div>
                </div>
            </div>

            <form action="{{ route('user.switch') }}" method="POST" class="flex items-center gap-2">
                @csrf
                <select name="user_id" onchange="this.form.submit()" class="bg-slate-800 border border-slate-700 text-xs rounded-lg px-3 py-2 text-slate-200 focus:outline-none focus:border-emerald-500">
                    @foreach($allUsers as $u)
                        <option value="{{ $u->id }}" @selected($u->id === $user->id)>
                            Юзер: {{ $u->username }} ({{ number_format((float)$u->wallet_balance, 0, '.', ' ') }} ₽)
                        </option>
                    @endforeach
                </select>
            </form>
        </div>
        @endif
    </div>
</header>

<main class="max-w-7xl mx-auto px-4 py-6 space-y-8">
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

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <!-- Витрина лотов -->
        <div class="lg:col-span-2 bg-slate-900 border border-slate-800 rounded-2xl p-5">
            <div class="flex items-center justify-between mb-4">
                <h2 class="text-lg font-bold text-white">Витрина лотов</h2>
                <div class="flex gap-1.5 text-xs">
                    <a href="{{ route('home') }}" class="px-3 py-1.5 rounded-lg {{ !request('type') ? 'bg-emerald-500 text-slate-950 font-bold' : 'bg-slate-800 text-slate-300' }}">Все</a>
                    <a href="{{ route('home', ['type' => 'training_plan']) }}" class="px-3 py-1.5 rounded-lg {{ request('type') === 'training_plan' ? 'bg-emerald-500 text-slate-950 font-bold' : 'bg-slate-800 text-slate-300' }}">Программы</a>
                    <a href="{{ route('home', ['type' => 'coaching']) }}" class="px-3 py-1.5 rounded-lg {{ request('type') === 'coaching' ? 'bg-emerald-500 text-slate-950 font-bold' : 'bg-slate-800 text-slate-300' }}">Тренерство</a>
                </div>
            </div>

            <div class="overflow-x-auto">
                <table class="w-full text-left border-collapse">
                    <thead>
                        <tr class="border-b border-slate-800 text-xs text-slate-400 uppercase">
                            <th class="py-3 px-2">Тип</th>
                            <th class="py-3 px-2">Название</th>
                            <th class="py-3 px-2">Продавец</th>
                            <th class="py-3 px-2 text-center">Наличие</th>
                            <th class="py-3 px-2 text-right">Цена</th>
                            <th class="py-3 px-2 text-right">Действие</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-800/70 text-sm">
                        @forelse($lots as $lot)
                        <tr class="hover:bg-slate-800/40">
                            <td class="py-3 px-2"><span class="text-xs px-2 py-1 rounded bg-slate-800 text-slate-300">{{ $lot->product_type }}</span></td>
                            <td class="py-3 px-2">
                                <div class="font-semibold text-white">{{ $lot->title }}</div>
                                <div class="text-xs text-slate-400 line-clamp-1">{{ $lot->description }}</div>
                            </td>
                            <td class="py-3 px-2">
                                <div class="font-medium text-slate-200">{{ $lot->seller->username ?? '-' }}</div>
                                <div class="text-xs text-amber-400">★ {{ number_format((float)($lot->seller->rating ?? 5), 2) }}</div>
                            </td>
                            <td class="py-3 px-2 text-center text-xs text-slate-300">{{ $lot->stock_quantity }} шт.</td>
                            <td class="py-3 px-2 text-right font-bold text-emerald-400">{{ number_format((float)$lot->price, 0, '.', ' ') }} ₽</td>
                            <td class="py-3 px-2 text-right">
                                @if($user && $lot->seller_id !== $user->id)
                                    <form action="{{ route('lots.purchase', $lot) }}" method="POST">
                                        @csrf
                                        <button type="submit" class="px-3 py-1.5 rounded-lg bg-emerald-500 hover:bg-emerald-400 text-slate-950 font-bold text-xs">Купить</button>
                                    </form>
                                @else
                                    <span class="text-xs text-slate-500">Ваш лот</span>
                                @endif
                            </td>
                        </tr>
                        @empty
                        <tr><td colspan="6" class="py-8 text-center text-slate-500">Нет доступных лотов.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Кошелек и создание лота -->
        <div class="space-y-6">
            <div class="bg-slate-900 border border-slate-800 rounded-2xl p-5">
                <h3 class="text-base font-bold text-white mb-3">Кошелек</h3>
                <div class="grid grid-cols-2 gap-3 mb-4">
                    <form action="{{ route('wallet.deposit') }}" method="POST" class="space-y-2">
                        @csrf
                        <input type="number" name="amount" value="2000" min="100" class="w-full bg-slate-800 border border-slate-700 rounded-lg px-3 py-1.5 text-sm text-white">
                        <button type="submit" class="w-full py-2 rounded-lg bg-emerald-500 hover:bg-emerald-400 text-slate-950 font-bold text-xs">+ Пополнить</button>
                    </form>
                    <form action="{{ route('wallet.withdraw') }}" method="POST" class="space-y-2">
                        @csrf
                        <input type="number" name="amount" value="1000" min="100" class="w-full bg-slate-800 border border-slate-700 rounded-lg px-3 py-1.5 text-sm text-white">
                        <button type="submit" class="w-full py-2 rounded-lg bg-slate-800 hover:bg-slate-700 text-slate-200 font-bold text-xs">→ Вывести</button>
                    </form>
                </div>

                <div class="text-xs text-slate-400 font-semibold uppercase mb-2">История операций</div>
                <div class="space-y-1.5 max-h-40 overflow-y-auto pr-1">
                    @forelse($myTransactions as $tx)
                        <div class="flex items-center justify-between text-xs py-1 px-2 rounded bg-slate-800/60">
                            <span class="font-semibold text-slate-200">{{ strtoupper($tx->type->value ?? $tx->type) }}</span>
                            <span class="font-bold {{ $tx->amount >= 0 ? 'text-emerald-400' : 'text-rose-400' }}">
                                {{ $tx->amount >= 0 ? '+' : '' }}{{ number_format((float)$tx->amount, 0, '.', ' ') }} ₽
                            </span>
                        </div>
                    @empty
                        <div class="text-xs text-slate-500">Операций нет.</div>
                    @endforelse
                </div>
            </div>

            <div class="bg-slate-900 border border-slate-800 rounded-2xl p-5">
                <h3 class="text-base font-bold text-white mb-3">Выставить лот</h3>
                <form action="{{ route('lots.store') }}" method="POST" class="space-y-3 text-sm">
                    @csrf
                    <input type="text" name="title" required placeholder="Название..." class="w-full bg-slate-800 border border-slate-700 rounded-lg px-3 py-2 text-white text-sm">
                    <div class="grid grid-cols-2 gap-2">
                        <select name="product_type" class="bg-slate-800 border border-slate-700 rounded-lg px-3 py-2 text-white text-sm">
                            <option value="training_plan">Программа</option>
                            <option value="coaching">Тренерство</option>
                        </select>
                        <input type="number" name="price" value="2000" min="50" required class="bg-slate-800 border border-slate-700 rounded-lg px-3 py-2 text-white text-sm">
                    </div>
                    <input type="hidden" name="stock_quantity" value="5">
                    <textarea name="description" rows="2" required placeholder="Описание..." class="w-full bg-slate-800 border border-slate-700 rounded-lg px-3 py-2 text-white text-sm"></textarea>
                    <button type="submit" class="w-full py-2.5 rounded-xl bg-indigo-500 hover:bg-indigo-400 text-white font-bold text-xs">Опубликовать</button>
                </form>
            </div>
        </div>
    </div>

    <!-- Покупки и продажи -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        <div class="bg-slate-900 border border-slate-800 rounded-2xl p-5">
            <h3 class="text-base font-bold text-white mb-3">Мои покупки</h3>
            <div class="space-y-3">
                @forelse($myPurchases as $order)
                    <div class="p-3.5 rounded-xl bg-slate-800/60 border border-slate-700/70 space-y-2">
                        <div class="flex items-center justify-between text-xs">
                            <span class="font-bold text-slate-300">Заказ #{{ $order->id }} • Продавец: {{ $order->seller->username ?? '-' }}</span>
                            <span class="px-2 py-0.5 rounded font-semibold bg-amber-500/20 text-amber-300">
                                {{ strtoupper(is_object($order->status) ? $order->status->value : $order->status) }}
                            </span>
                        </div>
                        <div class="flex items-center justify-between text-sm">
                            <span class="font-semibold text-white">{{ $order->lot->title ?? 'Лот' }}</span>
                            <span class="font-bold text-emerald-400">{{ number_format((float)$order->total_price, 0, '.', ' ') }} ₽</span>
                        </div>
                        @if(in_array($order->status, ['paid', 'shipped']))
                            <form action="{{ route('orders.confirm', $order) }}" method="POST" class="pt-2 border-t border-slate-700/60 flex gap-2">
                                @csrf
                                <input type="text" name="comment" placeholder="Отзыв..." class="flex-1 bg-slate-900 border border-slate-700 rounded px-2.5 py-1 text-xs text-white">
                                <button type="submit" class="px-3 py-1 rounded bg-emerald-500 hover:bg-emerald-400 text-slate-950 font-bold text-xs">Подтвердить</button>
                            </form>
                        @endif
                    </div>
                @empty
                    <div class="text-sm text-slate-500">Покупок нет.</div>
                @endforelse
            </div>
        </div>

        <div class="bg-slate-900 border border-slate-800 rounded-2xl p-5">
            <h3 class="text-base font-bold text-white mb-3">Мои продажи</h3>
            <div class="space-y-3">
                @forelse($mySales as $order)
                    <div class="p-3.5 rounded-xl bg-slate-800/60 border border-slate-700/70 space-y-1.5">
                        <div class="flex items-center justify-between text-xs">
                            <span class="font-bold text-slate-300">Заказ #{{ $order->id }} • Покупатель: {{ $order->buyer->username ?? '-' }}</span>
                            <span class="px-2 py-0.5 rounded font-semibold bg-emerald-500/20 text-emerald-300">{{ strtoupper($order->status) }}</span>
                        </div>
                        <div class="flex items-center justify-between text-sm">
                            <span class="font-semibold text-white">{{ $order->lot->title ?? 'Лот' }}</span>
                            <span class="font-bold text-emerald-400">+{{ number_format((float)$order->total_price, 0, '.', ' ') }} ₽</span>
                        </div>
                    </div>
                @empty
                    <div class="text-sm text-slate-500">Продаж нет.</div>
                @endforelse
            </div>
        </div>
    </div>
</main>
</body>
</html>