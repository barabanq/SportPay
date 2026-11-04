@extends('layouts.app')

@section('title', __('arbitration.header') . ' — SportPay')

@section('content')
<div class="bg-slate-900 border border-slate-800 rounded-2xl p-4 sm:p-6 space-y-6">
    <div class="flex flex-wrap items-center justify-between gap-4 border-b border-slate-800 pb-4">
        <div>
            <h1 class="text-lg sm:text-xl font-extrabold text-white flex items-center gap-2">
                ⚖️ {{ __('arbitration.header') }}
            </h1>
            <p class="text-xs text-slate-400 mt-1">
                {{ __('arbitration.desc') }}
            </p>
        </div>

        @if(!$user->isArbiter())
            <div class="px-4 py-2 rounded-xl bg-amber-500/10 border border-amber-500/30 text-xs text-amber-300">
                {{ __('arbitration.switch_arbiter') }} <b>Arbiter_Judge</b>
            </div>
        @else
            <div class="px-4 py-2 rounded-xl bg-emerald-500/10 border border-emerald-500/30 text-xs text-emerald-300 font-bold">
                ✓ {{ __('arbitration.logged_arbiter') }} ({{ $user->username }})
            </div>
        @endif
    </div>

    @if($disputedOrders->isEmpty())
        <div class="py-12 text-center text-sm text-slate-500">
            {{ __('arbitration.no_disputes') }}
        </div>
    @else
        <div class="space-y-4">
            @foreach ($disputedOrders as $order)
                <div class="p-4 sm:p-5 rounded-2xl bg-slate-950/70 border border-rose-500/30 space-y-4">
                    <div class="flex flex-wrap items-center justify-between gap-3">
                        <div class="flex flex-wrap items-center gap-2.5">
                            <span class="px-2.5 py-1 rounded text-xs font-extrabold bg-rose-500/20 text-rose-300 border border-rose-500/40">
                                {{ __('arbitration.dispute_badge') }} #{{ $order->id }}
                            </span>
                            <span class="font-bold text-white text-sm sm:text-base">{{ $order->lot->title ?? 'Lot' }}</span>
                        </div>
                        <div class="text-base sm:text-lg font-extrabold text-emerald-400">
                            {{ __('arbitration.in_hold') }} {{ number_format((float) $order->total_price, 2, '.', ' ') }} ₽
                        </div>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-3 gap-3 text-xs bg-slate-900 p-3.5 rounded-xl border border-slate-800">
                        <div>
                            <span class="text-slate-400">{{ __('arbitration.buyer') }}</span>
                            <a href="{{ route('users.show', $order->buyer_id) }}" class="ml-1 font-bold text-indigo-400 hover:underline">{{ $order->buyer->username }}</a>
                        </div>
                        <div>
                            <span class="text-slate-400">{{ __('arbitration.seller') }}</span>
                            <a href="{{ route('users.show', $order->seller_id) }}" class="ml-1 font-bold text-emerald-400 hover:underline">{{ $order->seller->username }}</a>
                        </div>
                        <div class="md:text-right">
                            <a href="{{ route('orders.show', $order) }}" class="text-amber-400 font-bold hover:underline">
                                {{ __('arbitration.open_chat') }}
                            </a>
                        </div>
                    </div>

                    @if($order->latestMessage)
                        <div class="text-xs text-slate-300 bg-slate-900/80 px-3.5 py-2.5 rounded-xl border border-slate-800">
                            <span class="text-slate-500 font-semibold">{{ __('arbitration.last_msg') }}</span>
                            {{ $order->latestMessage->body }}
                        </div>
                    @endif

                    @if($user->isArbiter())
                        <form action="{{ route('orders.resolve', $order) }}" method="POST" class="pt-3 border-t border-slate-800 flex flex-wrap items-center gap-2.5">
                            @csrf
                            <input type="text" name="comment" placeholder="{{ __('arbitration.placeholder_comment') }}" class="flex-1 min-w-[220px] bg-slate-900 border border-slate-700 rounded-xl px-3.5 py-2 text-xs text-white">

                            <button type="submit" name="decision" value="refund_buyer" class="px-4 py-2 rounded-xl bg-rose-500 hover:bg-rose-400 text-white font-extrabold text-xs">
                                {{ __('arbitration.refund_buyer') }} ({{ number_format((float) $order->total_price, 0, '.', ' ') }} ₽)
                            </button>

                            <button type="submit" name="decision" value="release_seller" class="px-4 py-2 rounded-xl bg-emerald-500 hover:bg-emerald-400 text-slate-950 font-extrabold text-xs">
                                {{ __('arbitration.release_seller') }}
                            </button>
                        </form>
                    @endif
                </div>
            @endforeach
        </div>
    @endif
</div>
@endsection
