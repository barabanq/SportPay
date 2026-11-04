@extends('layouts.app')

@section('title', __('Мои запросы в поддержку'))

@section('content')
<div class="max-w-4xl mx-auto bg-slate-900 border border-slate-800 rounded-2xl p-4 sm:p-6 space-y-6">
    <div class="flex flex-wrap items-center justify-between gap-4 border-b border-slate-800 pb-4">
        <div>
            <h1 class="text-lg sm:text-xl font-extrabold text-white">
                📂 {{ $user->isArbiter() ? __('Все запросы пользователей в поддержку') : __('Мои запросы в поддержку') }} ({{ $tickets->count() }})
            </h1>
            <p class="text-xs text-slate-400 mt-1">{{ __('История ваших обращений и ответы специалистов SportPay') }}</p>
        </div>
        <a href="{{ route('support.tickets.create') }}" class="px-4 py-2 rounded-xl bg-emerald-500 hover:bg-emerald-400 text-slate-950 font-extrabold text-xs transition">
            {{ __('+ Новый запрос') }}
        </a>
    </div>

    @if($tickets->isEmpty())
        <div class="py-12 text-center text-xs text-slate-500">
            {{ __('У вас пока нет ни одного запроса в службу поддержки.') }}
        </div>
    @else
        <div class="space-y-4">
            @foreach ($tickets as $ticket)
                <div class="p-4 sm:p-5 rounded-2xl bg-slate-950/70 border border-slate-800 space-y-3">
                    <div class="flex flex-wrap items-center justify-between gap-2">
                        <div class="flex items-center gap-2.5">
                            <span class="text-xs font-mono text-slate-500">#{{ $ticket->id }}</span>
                            <span class="font-bold text-white text-sm">{{ $ticket->subject }}</span>
                            @if($ticket->order_id)
                                <a href="{{ route('orders.show', $ticket->order_id) }}" class="text-xs text-indigo-400 hover:underline font-semibold">
                                    ({{ __('Заказ') }} #{{ $ticket->order_id }})
                                </a>
                            @endif
                        </div>
                        <span class="text-[11px] font-bold px-2.5 py-0.5 rounded {{ $ticket->statusBadgeClasses() }}">
                            {{ __($ticket->statusLabel()) }}
                        </span>
                    </div>

                    <div class="text-xs text-slate-300 bg-slate-900 p-3.5 rounded-xl border border-slate-800/80 whitespace-pre-line">{{ $ticket->message }}</div>

                    <div class="flex items-center justify-between text-[11px] text-slate-500">
                        <span>{{ __('Автор:') }} <b class="text-slate-300">{{ $ticket->user->username ?? $user->username }}</b></span>
                        <span>{{ $ticket->created_at?->format('d.m.Y H:i') }}</span>
                    </div>

                    @if($ticket->staff_reply)
                        <div class="p-3.5 rounded-xl bg-emerald-500/10 border border-emerald-500/30 space-y-1">
                            <div class="text-xs font-extrabold text-emerald-400">🛡️ {{ __('Ответ поддержки SportPay:') }}</div>
                            <div class="text-xs text-slate-200 whitespace-pre-line">{{ $ticket->staff_reply }}</div>
                        </div>
                    @endif

                    @if($user->isArbiter())
                        <form action="{{ route('support.tickets.reply', $ticket) }}" method="POST" class="pt-3 border-t border-slate-800 flex flex-wrap items-center gap-2">
                            @csrf
                            <input type="text" name="staff_reply" value="{{ $ticket->staff_reply }}" required placeholder="{{ __('Написать официальный ответ поддержки...') }}" class="flex-1 min-w-[220px] bg-slate-900 border border-slate-700 rounded-xl px-3 py-2 text-xs text-white">
                            <select name="status" class="bg-slate-900 border border-slate-700 rounded-xl px-3 py-2 text-xs text-white">
                                <option value="answered" @selected($ticket->status === 'answered')>{{ __('Отвечен') }}</option>
                                <option value="closed" @selected($ticket->status === 'closed')>{{ __('Закрыт') }}</option>
                            </select>
                            <button type="submit" class="px-4 py-2 rounded-xl bg-emerald-500 hover:bg-emerald-400 text-slate-950 font-extrabold text-xs">
                                {{ __('Ответить') }}
                            </button>
                        </form>
                    @endif
                </div>
            @endforeach
        </div>
    @endif
</div>
@endsection
