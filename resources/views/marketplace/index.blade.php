@extends('layouts.app')

@section('content')
<div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
    <div class="lg:col-span-2 bg-slate-900 border border-slate-800 rounded-2xl p-5 space-y-4">
        <div class="flex flex-wrap items-center justify-between gap-2">
            <h2 class="text-lg font-bold text-white">Витрина спортивных лотов ({{ $lots->count() }})</h2>
            <div class="flex flex-wrap gap-1.5 text-xs">
                <a href="{{ route('home', array_filter(['search' => request('search'), 'sort' => request('sort')])) }}" class="px-3 py-1.5 rounded-lg {{ !request('type') ? 'bg-emerald-500 text-slate-950 font-bold' : 'bg-slate-800 text-slate-300' }}">Все</a>
                <a href="{{ route('home', array_filter(['type' => 'training_plan', 'search' => request('search'), 'sort' => request('sort')])) }}" class="px-3 py-1.5 rounded-lg {{ request('type') === 'training_plan' ? 'bg-emerald-500 text-slate-950 font-bold' : 'bg-slate-800 text-slate-300' }}">Программы</a>
                <a href="{{ route('home', array_filter(['type' => 'coaching', 'search' => request('search'), 'sort' => request('sort')])) }}" class="px-3 py-1.5 rounded-lg {{ request('type') === 'coaching' ? 'bg-emerald-500 text-slate-950 font-bold' : 'bg-slate-800 text-slate-300' }}">Тренерство</a>
                <a href="{{ route('home', array_filter(['type' => 'race_slot', 'search' => request('search'), 'sort' => request('sort')])) }}" class="px-3 py-1.5 rounded-lg {{ request('type') === 'race_slot' ? 'bg-emerald-500 text-slate-950 font-bold' : 'bg-slate-800 text-slate-300' }}">Слоты</a>
                <a href="{{ route('home', array_filter(['type' => 'equipment', 'search' => request('search'), 'sort' => request('sort')])) }}" class="px-3 py-1.5 rounded-lg {{ request('type') === 'equipment' ? 'bg-emerald-500 text-slate-950 font-bold' : 'bg-slate-800 text-slate-300' }}">Экипировка</a>
            </div>
        </div>

        <form action="{{ route('home') }}" method="GET" class="grid grid-cols-1 sm:grid-cols-12 gap-2 pt-1">
            @if(request('type'))
                <input type="hidden" name="type" value="{{ request('type') }}">
            @endif
            <div class="sm:col-span-6 relative">
                <input type="text" id="lot-search-input" name="search" value="{{ request('search') }}" placeholder="Поиск по названию или описанию..." class="w-full bg-slate-950 border border-slate-800 rounded-xl px-3.5 py-2 text-xs text-white focus:outline-none focus:border-emerald-500">
            </div>
            <div class="sm:col-span-4">
                <select name="sort" onchange="this.form.submit()" class="w-full bg-slate-950 border border-slate-800 rounded-xl px-3 py-2 text-xs text-slate-200">
                    <option value="latest" @selected(request('sort', 'latest') === 'latest')>Сначала новые</option>
                    <option value="price_asc" @selected(request('sort') === 'price_asc')>Дешевле (Цена ↑)</option>
                    <option value="price_desc" @selected(request('sort') === 'price_desc')>Дороже (Цена ↓)</option>
                    <option value="rating_desc" @selected(request('sort') === 'rating_desc')>По рейтингу тренера ★</option>
                </select>
            </div>
            <div class="sm:col-span-2 flex gap-1">
                <button type="submit" class="flex-1 py-2 rounded-xl bg-slate-800 hover:bg-slate-700 text-xs font-bold text-emerald-400">Найти</button>
                @if(request()->anyFilled(['search', 'type', 'sort']))
                    <a href="{{ route('home') }}" title="Сбросить фильтры" class="px-2.5 py-2 rounded-xl bg-slate-800 hover:bg-rose-500/20 text-xs text-slate-400 hover:text-rose-300">✕</a>
                @endif
            </div>
        </form>

        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse text-sm">
                <thead>
                    <tr class="border-b border-slate-800 text-xs text-slate-400 uppercase">
                        <th class="py-3 px-2">Тип</th>
                        <th class="py-3 px-2">Лот</th>
                        <th class="py-3 px-2">Продавец</th>
                        <th class="py-3 px-2 text-right">Цена</th>
                        <th class="py-3 px-2 text-right">Действие</th>
                    </tr>
                </thead>
                <tbody id="lots-table-body" class="divide-y divide-slate-800/70">
                    @if($lots->isEmpty())
                        <tr>
                            <td colspan="5" class="py-8 text-center text-slate-500 text-xs">По вашему запросу лотов не найдено.</td>
                        </tr>
                    @else
                        @foreach($lots as $lot)
                        <tr class="lot-row hover:bg-slate-800/40">
                            <td class="py-3 px-2"><span class="text-xs px-2 py-1 rounded bg-slate-800 text-slate-300">{{ $lot->product_type }}</span></td>
                            <td class="py-3 px-2">
                            <div class="font-semibold text-white flex items-center gap-1.5">
                            <span>{{ $lot->title }}</span>
                            @if($lot->hasAutoDelivery())
                                <span title="Мгновенная автовыдача после оплаты" class="px-1.5 py-0.5 text-[10px] font-extrabold rounded bg-emerald-500/20 text-emerald-300 border border-emerald-500/30">
                                    ⚡ Автовыдача
                                </span>
                            @endif
                        </div>
                                <div class="text-xs text-slate-400">{{ $lot->description }} ({{ $lot->stock_quantity }} шт.)</div>
                            </td>
                            <td class="py-3 px-2">
                                <a href="{{ route('users.show', $lot->seller_id) }}" class="font-medium text-emerald-400 hover:underline">{{ $lot->seller->username ?? '-' }}</a>
                                <div class="text-xs text-amber-400">★ {{ number_format((float) ($lot->seller->rating ?? 5), 2) }}</div>
                            </td>
                            <td class="py-3 px-2 text-right font-bold text-emerald-400">{{ number_format((float) $lot->price, 0, '.', ' ') }} ₽</td>
                            <td class="py-3 px-2 text-right">
                                @if($user && $lot->seller_id !== $user->id)
                                    <form action="{{ route('lots.purchase', $lot) }}" method="POST">
                                        @csrf
                                        <button type="submit" class="px-3 py-1.5 rounded-lg bg-emerald-500 hover:bg-emerald-400 text-slate-950 font-bold text-xs">Купить</button>
                                    </form>
                                @else
                                    <div class="flex items-center justify-end gap-1.5">
                                        <a href="{{ route('lots.edit', $lot) }}" class="px-2.5 py-1 rounded-lg bg-slate-800 hover:bg-slate-700 border border-slate-700 text-xs text-slate-200">✎ Изм.</a>
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
    </div>

    <div class="space-y-6">
        <div class="bg-slate-900 border border-slate-800 rounded-2xl p-5">
            <h3 class="text-base font-bold text-white mb-3">Кошелек</h3>
            <div class="grid grid-cols-2 gap-3 mb-4">
                <form action="{{ route('wallet.deposit') }}" method="POST" class="space-y-2">
                    @csrf
                    <input type="number" name="amount" value="2000" min="100" class="w-full bg-slate-800 border border-slate-700 rounded-lg px-3 py-1.5 text-sm text-white">
                    <button type="submit" class="w-full py-2 rounded-lg bg-emerald-500 text-slate-950 font-bold text-xs">+ Пополнить</button>
                </form>
                <form action="{{ route('wallet.withdraw') }}" method="POST" class="space-y-2">
                    @csrf
                    <input type="number" name="amount" value="1000" min="100" class="w-full bg-slate-800 border border-slate-700 rounded-lg px-3 py-1.5 text-sm text-white">
                    <button type="submit" class="w-full py-2 rounded-lg bg-slate-800 text-slate-200 font-bold text-xs">→ Вывести</button>
                </form>
            </div>
            <div class="space-y-1.5 max-h-40 overflow-y-auto">
                @foreach($myTransactions as $tx)
                    <div class="flex items-center justify-between text-xs py-1.5 px-2 rounded bg-slate-800/60">
                        <span class="font-semibold text-slate-200">{{ $tx->type->label() }}</span>
                        <span class="font-bold {{ $tx->amount >= 0 ? 'text-emerald-400' : 'text-rose-400' }}">
                            {{ number_format((float) $tx->amount, 0, '.', ' ') }} ₽
                        </span>
                    </div>
                @endforeach
            </div>
        </div>

        <div class="bg-slate-900 border border-slate-800 rounded-2xl p-5">
            <h3 class="text-base font-bold text-white mb-3">Выставить лот</h3>
            <form action="{{ route('lots.store') }}" method="POST" class="space-y-3 text-sm">
                @csrf
                <input type="text" name="title" required placeholder="Название лота..." class="w-full bg-slate-800 border border-slate-700 rounded-lg px-3 py-2 text-white">
                <div class="grid grid-cols-2 gap-2">
                    <select name="product_type" class="bg-slate-800 border border-slate-700 rounded-lg px-3 py-2 text-white">
                        <option value="training_plan">Программа</option>
                        <option value="coaching">Тренерство</option>
                        <option value="race_slot">Слот на забег</option>
                        <option value="equipment">Экипировка</option>
                    </select>
                    <input type="number" name="price" value="2000" min="50" required class="bg-slate-800 border border-slate-700 rounded-lg px-3 py-2 text-white">
                </div>
                <input type="hidden" name="stock_quantity" value="5">
                <textarea name="description" rows="2" required placeholder="Описание..." class="w-full bg-slate-800 border border-slate-700 rounded-lg px-3 py-2 text-white"></textarea>
                <textarea name="auto_delivery_text" rows="2" placeholder="⚡ Секретный текст/ссылка для автовыдачи (необязательно)..." class="w-full bg-slate-950 border border-emerald-500/40 rounded-lg px-3 py-2 text-xs text-emerald-300 placeholder-slate-500"></textarea>
                <button type="submit" class="w-full py-2.5 rounded-xl bg-indigo-500 text-white font-bold text-xs">Опубликовать</button>
            </form>
        </div>
    </div>
</div>

<div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
    <div class="bg-slate-900 border border-slate-800 rounded-2xl p-5">
        <h3 class="text-base font-bold text-white mb-3">Мои покупки</h3>
        <div class="space-y-3">
            @foreach($myPurchases as $order)
                <div class="p-3.5 rounded-xl bg-slate-800/60 border border-slate-700/70 flex items-center justify-between">
                    <div>
                        <a href="{{ route('orders.show', $order) }}" class="font-bold text-emerald-400 hover:underline text-sm">
                            Заказ #{{ $order->id }}: {{ $order->lot->title ?? 'Лот' }}
                        </a>
                        <div class="text-xs text-slate-400">Продавец: {{ $order->seller->username ?? '-' }} • {{ number_format((float) $order->total_price, 0, '.', ' ') }} ₽</div>
                    </div>
                    <a href="{{ route('orders.show', $order) }}" class="px-3 py-1.5 rounded-lg bg-slate-700 hover:bg-slate-600 text-xs text-white font-semibold">💬 Чат сделки</a>
                </div>
            @endforeach
        </div>
    </div>

    <div class="bg-slate-900 border border-slate-800 rounded-2xl p-5">
        <h3 class="text-base font-bold text-white mb-3">Мои продажи</h3>
        <div class="space-y-3">
            @foreach($mySales as $order)
                <div class="p-3.5 rounded-xl bg-slate-800/60 border border-slate-700/70 flex items-center justify-between">
                    <div>
                        <a href="{{ route('orders.show', $order) }}" class="font-bold text-emerald-400 hover:underline text-sm">
                            Заказ #{{ $order->id }}: {{ $order->lot->title ?? 'Лот' }}
                        </a>
                        <div class="text-xs text-slate-400">Покупатель: {{ $order->buyer->username ?? '-' }} • +{{ number_format((float) $order->total_price, 0, '.', ' ') }} ₽</div>
                    </div>
                    <a href="{{ route('orders.show', $order) }}" class="px-3 py-1.5 rounded-lg bg-slate-700 hover:bg-slate-600 text-xs text-white font-semibold">💬 Чат сделки</a>
                </div>
            @endforeach
        </div>
    </div>
</div>

<script>
document.getElementById('lot-search-input')?.addEventListener('input', function (e) {
    const term = e.target.value.toLowerCase();
    document.querySelectorAll('#lots-table-body .lot-row').forEach(row => {
        row.style.display = row.textContent.toLowerCase().includes(term) ? '' : 'none';
    });
});
</script>
@endsection