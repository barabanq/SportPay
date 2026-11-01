@extends('layouts.app')

@section('title', 'Панель Арбитра — SportPay')

@section('content')
<div class="bg-slate-900 border border-slate-800 rounded-2xl p-6 space-y-6">
    <div class="flex flex-wrap items-center justify-between gap-4 border-b border-slate-800 pb-4">
        <div>
            <h1 class="text-xl font-extrabold text-white flex items-center gap-2">
                ⚖️ Панель Арбитража SportPay
            </h1>
            <p class="text-xs text-slate-400 mt-1">
                Разрешение открытых споров между покупателями и продавцами. Средства находятся в эскроу-холде.
            </p>
        </div>

        @if(!$user->isArbiter())
            <div class="px-4 py-2 rounded-xl bg-amber-500/10 border border-amber-500/30 text-xs text-amber-300">
                Для вынесения решений переключитесь в шапке на аккаунт <b>Arbiter_Judge</b>
            </div>
        @else
            <div class="px-4 py-2 rounded-xl bg-emerald-500/10 border border-emerald-500/30 text-xs text-emerald-300 font-bold">
                ✓ Вы вошли как официальный Арбитр ({{ $user->username }})
            </div>
        @endif
    </div>

    @if($disputedOrders->isEmpty())
        <div class="py-12 text-center text-sm text-slate-500">
            Активных споров сейчас нет. Все сделки проходят штатно!
        </div>
    @else
        <div class="space-y-4">
            @foreach($disputedOrders as $order)
                <div class="p-5 rounded-2xl bg-slate-950/70 border border-rose-500/30 space-y-4">
                    <div class="flex flex-wrap items-center justify-between gap-3">
                        <div class="flex items-center gap-3">
                            <span class="px-2.5 py-1 rounded text-xs font-extrabold bg-rose-500/20 text-rose-300 border border-rose-500/40">
                                СПОР ПО СДЕЛКЕ #{{ $order->id }}
                            </span>
                            <span class="font-bold text-white text-base">{{ $order->lot->title ?? 'Лот' }}</span>
                        </div>
                        <div class="text-lg font-extrabold text-emerald-400">
                            В холде: {{ number_format((float) $order->total_price, 2, '.', ' ') }} ₽
                        </div>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4 text-xs bg-slate-900 p-3.5 rounded-xl border border-slate-800">
                        <div>
                            <span class="text-slate-400">Покупатель:</span>
                            <a href="{{ route('users.show', $order->buyer_id) }}" class="ml-1 font-bold text-indigo-400 hover:underline">{{ $order->buyer->username }}</a>
                        </div>
                        <div>
                            <span class="text-slate-400">Продавец:</span>
                            <a href="{{ route('users.show', $order->seller_id) }}" class="ml-1 font-bold text-emerald-400 hover:underline">{{ $order->seller->username }}</a>
                        </div>
                        <div class="md:text-right">
                            <a href="{{ route('orders.show', $order) }}" class="text-amber-400 font-bold hover:underline">
                                💬 Открыть полный чат сделки →
                            </a>
                        </div>
                    </div>

                    @if($order->latestMessage)
                        <div class="text-xs text-slate-300 bg-slate-900/80 px-3.5 py-2.5 rounded-xl border border-slate-800">
                            <span class="text-slate-500 font-semibold">Последнее сообщение в чате:</span>
                            {{ $order->latestMessage->body }}
                        </div>
                    @endif

                    @if($user->isArbiter())
                        <form action="{{ route('orders.resolve', $order) }}" method="POST" class="pt-3 border-t border-slate-800 flex flex-wrap items-center gap-3">
                            @csrf
                            <input type="text" name="comment" placeholder="Официальный комментарий арбитра к решению..." class="flex-1 min-w-[240px] bg-slate-900 border border-slate-700 rounded-xl px-3.5 py-2 text-xs text-white">

                            <button type="submit" name="decision" value="refund_buyer" class="px-4 py-2 rounded-xl bg-rose-500 hover:bg-rose-400 text-white font-extrabold text-xs">
                                ↺ Вернуть 100% покупателю ({{ number_format((float) $order->total_price, 0, '.', ' ') }} ₽)
                            </button>

                            <button type="submit" name="decision" value="release_seller" class="px-4 py-2 rounded-xl bg-emerald-500 hover:bg-emerald-400 text-slate-950 font-extrabold text-xs">
                                ✓ Выплатить продавцу (-5% комиссия)
                            </button>
                        </form>
                    @endif
                </div>
            @endforeach
        </div>
    @endif
</div>
@endsection