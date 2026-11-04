@extends('layouts.app')

@section('title', __('Финансы') . ' — SportPay')

@section('content')
<div class="grid grid-cols-1 lg:grid-cols-3 gap-5 sm:gap-6">
    <!-- Левая колонка: Сводка по балансу и операции -->
    <div class="space-y-5 sm:space-y-6">
        <div class="bg-slate-900 border border-slate-800 rounded-2xl p-5 sm:p-6 space-y-4">
            <div class="text-xs font-bold uppercase tracking-wider text-slate-400">{{ __('Доступный баланс') }}</div>
            <div class="text-2xl sm:text-3xl font-extrabold text-emerald-400">
                {{ number_format((float) $user->wallet_balance, 2, '.', ' ') }} ₽
            </div>

            <div class="grid grid-cols-2 gap-3 pt-3 border-t border-slate-800 text-xs">
                <div class="p-3 rounded-xl bg-slate-950 border border-slate-800">
                    <div class="text-slate-400">{{ __('Заморожено в покупках') }}</div>
                    <div class="font-bold text-amber-400 text-sm mt-1">
                        {{ number_format($frozenAsBuyer, 2, '.', ' ') }} ₽
                    </div>
                </div>
                <div class="p-3 rounded-xl bg-slate-950 border border-slate-800">
                    <div class="text-slate-400">{{ __('Ожидает выплаты (−5%)') }}</div>
                    <div class="font-bold text-indigo-400 text-sm mt-1">
                        {{ number_format($pendingAsSeller, 2, '.', ' ') }} ₽
                    </div>
                </div>
            </div>
        </div>

        <div class="bg-slate-900 border border-slate-800 rounded-2xl p-5 sm:p-6 space-y-4">
            <h2 class="text-sm font-extrabold uppercase tracking-wider text-emerald-400">{{ __('Пополнить баланс') }}</h2>
            <form action="{{ route('wallet.deposit') }}" method="POST" class="space-y-3">
                @csrf
                <div>
                    <label class="block text-xs text-slate-400 mb-1">{{ __('Сумма пополнения (₽)') }}</label>
                    <input type="number" name="amount" value="2500" min="100" max="500000" step="100" required class="w-full bg-slate-950 border border-slate-800 rounded-xl px-3.5 py-2.5 text-sm text-white font-bold focus:outline-none focus:border-emerald-500">
                </div>
                <button type="submit" class="w-full py-2.5 rounded-xl bg-emerald-500 hover:bg-emerald-400 text-slate-950 font-extrabold text-xs transition">
                    {{ __('+ Пополнить кошелек') }}
                </button>
            </form>
        </div>

        <div class="bg-slate-900 border border-slate-800 rounded-2xl p-5 sm:p-6 space-y-4">
            <h2 class="text-sm font-extrabold uppercase tracking-wider text-slate-300">{{ __('Вывод средств') }}</h2>
            <form action="{{ route('wallet.withdraw') }}" method="POST" class="space-y-3">
                @csrf
                <div>
                    <label class="block text-xs text-slate-400 mb-1">{{ __('Сумма к выводу (₽)') }}</label>
                    <input type="number" name="amount" value="1000" min="100" max="500000" step="100" required class="w-full bg-slate-950 border border-slate-800 rounded-xl px-3.5 py-2.5 text-sm text-white font-bold focus:outline-none focus:border-indigo-500">
                </div>
                <button type="submit" class="w-full py-2.5 rounded-xl bg-slate-800 hover:bg-slate-700 border border-slate-700 text-slate-200 font-bold text-xs transition">
                    {{ __('→ Вывести средства') }}
                </button>
            </form>
        </div>
    </div>

    <!-- Правая колонка: Аудиторская история транзакций -->
    <div class="lg:col-span-2 bg-slate-900 border border-slate-800 rounded-2xl overflow-hidden flex flex-col">
        <div class="p-4 sm:p-5 border-b border-slate-800 flex items-center justify-between">
            <h1 class="text-sm sm:text-base font-extrabold text-white">{{ __('История финансовых операций') }}</h1>
            <span class="text-xs text-slate-400">#{{ $transactions->count() }}</span>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse text-xs sm:text-sm">
                <thead>
                    <tr class="border-b border-slate-800 text-[11px] uppercase tracking-wider text-slate-400 bg-slate-950/60">
                        <th class="py-3 px-3 sm:px-4">ID</th>
                        <th class="py-3 px-3 sm:px-4">{{ __('Тип операции') }}</th>
                        <th class="py-3 px-3 sm:px-4">{{ __('Заказ') }}</th>
                        <th class="py-3 px-3 sm:px-4 text-right">{{ __('Сумма') }}</th>
                        <th class="py-3 px-3 sm:px-4 text-right hidden sm:table-cell">{{ __('Остаток после') }}</th>
                        <th class="py-3 px-3 sm:px-4 text-right">{{ __('Дата') }}</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-800/70">
                    @if($transactions->isEmpty())
                        <tr>
                            <td colspan="6" class="py-12 text-center text-xs text-slate-500">
                                {{ __('Финансовых операций пока не было.') }}
                            </td>
                        </tr>
                    @else
                        @foreach ($transactions as $tx)
                            @php
                                $isPositive = (float) $tx->amount >= 0;
                                $typeLabels = [
                                    'deposit' => __('Пополнение / Возврат'),
                                    'withdraw' => __('Вывод средств'),
                                    'purchase' => __('Оплата заказа (Эскроу)'),
                                    'payout' => __('Выплата продавцу (−5%)'),
                                ];
                                $typeKey = $tx->type instanceof \BackedEnum ? $tx->type->value : (string) $tx->type;
                            @endphp
                            <tr class="hover:bg-slate-800/40 transition">
                                <td class="py-3 px-3 sm:px-4 text-xs text-slate-500 font-mono">#{{ $tx->id }}</td>
                                <td class="py-3 px-3 sm:px-4 font-medium text-slate-200">
                                    {{ $typeLabels[$typeKey] ?? $typeKey }}
                                </td>
                                <td class="py-3 px-3 sm:px-4 text-xs">
                                    @if($tx->order_id)
                                        <a href="{{ route('orders.show', $tx->order_id) }}" class="text-indigo-400 hover:underline font-bold">
                                            #{{ $tx->order_id }}
                                        </a>
                                    @else
                                        <span class="text-slate-600">—</span>
                                    @endif
                                </td>
                                <td class="py-3 px-3 sm:px-4 text-right font-extrabold {{ $isPositive ? 'text-emerald-400' : 'text-rose-400' }}">
                                    {{ $isPositive ? '+' : '' }}{{ number_format((float) $tx->amount, 2, '.', ' ') }} ₽
                                </td>
                                <td class="py-3 px-3 sm:px-4 text-right text-xs font-semibold text-slate-300 hidden sm:table-cell">
                                    {{ number_format((float) $tx->balance_after, 2, '.', ' ') }} ₽
                                </td>
                                <td class="py-3 px-3 sm:px-4 text-right text-xs text-slate-400">
                                    {{ $tx->created_at?->format('d.m H:i') }}
                                </td>
                            </tr>
                        @endforeach
                    @endif
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
