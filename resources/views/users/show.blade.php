@extends('layouts.app')

@section('title', "Профиль {$profileUser->username} — SportPay")

@section('content')
<div class="bg-slate-900 border border-slate-800 rounded-2xl p-6 flex flex-wrap items-center justify-between gap-6">
    <div class="flex items-center gap-4">
        <div class="w-16 h-16 rounded-2xl bg-emerald-500/20 border border-emerald-500/40 flex items-center justify-center text-2xl font-extrabold text-emerald-400">
            {{ strtoupper(substr($profileUser->username, 0, 1)) }}
        </div>
        <div>
            <div class="flex items-center gap-2">
                <h1 class="text-2xl font-extrabold text-white">{{ $profileUser->username }}</h1>
                <span class="text-xs px-2.5 py-0.5 rounded-full bg-slate-800 text-slate-300 border border-slate-700">
                    {{ $profileUser->status->label() }}
                </span>
            </div>
            <p class="text-xs text-slate-400 mt-1">
                На SportPay с {{ $profileUser->created_at?->format('d.m.Y') ?? '2026' }}
            </p>
        </div>
    </div>

    <div class="flex items-center gap-6 bg-slate-950/70 px-6 py-3 rounded-xl border border-slate-800">
        <div class="text-center">
            <div class="text-xs text-slate-400">Рейтинг продавца</div>
            <div class="text-xl font-extrabold text-amber-400">★ {{ number_format((float) $profileUser->rating, 2) }}</div>
        </div>
        <div class="h-8 w-px bg-slate-800"></div>
        <div class="text-center">
            <div class="text-xs text-slate-400">Отзывов</div>
            <div class="text-xl font-extrabold text-white">{{ $completedSalesCount }}</div>
        </div>
        <div class="h-8 w-px bg-slate-800"></div>
        <div class="text-center">
            <div class="text-xs text-slate-400">Активных лотов</div>
            <div class="text-xl font-extrabold text-emerald-400">{{ $profileUser->lots->count() }}</div>
        </div>
    </div>
</div>

<div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
    <div class="lg:col-span-2 bg-slate-900 border border-slate-800 rounded-2xl p-5">
        <h2 class="text-lg font-bold text-white mb-4">Активные лоты продавца {{ $profileUser->username }}</h2>
        <div class="space-y-3">
            @if($profileUser->lots->isEmpty())
                <div class="text-sm text-slate-500 py-6 text-center">У пользователя нет активных лотов.</div>
            @else
                @foreach($profileUser->lots as $lot)
                    <div class="p-4 rounded-xl bg-slate-800/50 border border-slate-700/60 flex items-center justify-between gap-4">
                        <div>
                            <span class="text-xs px-2 py-0.5 rounded bg-slate-800 text-slate-300">{{ $lot->product_type }}</span>
                            <div class="font-bold text-white mt-1">{{ $lot->title }}</div>
                            <div class="text-xs text-slate-400 mt-0.5">{{ $lot->description }}</div>
                        </div>
                        <div class="text-right shrink-0 space-y-2">
                            <div class="text-lg font-extrabold text-emerald-400">{{ number_format((float) $lot->price, 0, '.', ' ') }} ₽</div>
                            @if($user->id !== $profileUser->id)
                                <form action="{{ route('lots.purchase', $lot) }}" method="POST">
                                    @csrf
                                    <button type="submit" class="px-4 py-1.5 rounded-lg bg-emerald-500 hover:bg-emerald-400 text-slate-950 font-bold text-xs">
                                        Купить
                                    </button>
                                </form>
                            @else
                                <a href="{{ route('lots.edit', $lot) }}" class="inline-block px-3 py-1 rounded-lg bg-slate-800 hover:bg-slate-700 border border-slate-700 text-xs text-slate-200">
                                    ✎ Изм.
                                </a>
                            @endif
                        </div>
                    </div>
                @endforeach
            @endif
        </div>
    </div>

    <div class="bg-slate-900 border border-slate-800 rounded-2xl p-5">
        <h2 class="text-lg font-bold text-white mb-4">Отзывы покупателей ({{ $completedSalesCount }})</h2>
        <div class="space-y-3">
            @if($profileUser->receivedReviews->isEmpty())
                <div class="text-sm text-slate-500 py-6 text-center">Отзывов пока нет.</div>
            @else
                @foreach($profileUser->receivedReviews as $review)
                    <div class="p-3.5 rounded-xl bg-slate-800/60 border border-slate-700/60 space-y-1.5">
                        <div class="flex items-center justify-between text-xs">
                            <a href="{{ route('users.show', $review->author_id) }}" class="font-bold text-emerald-400 hover:underline">
                                {{ $review->author->username ?? 'Покупатель' }}
                            </a>
                            <span class="text-amber-400 font-bold">{{ str_repeat('★', $review->rating) }}</span>
                        </div>
                        <div class="text-xs text-slate-400">Лот: {{ $review->lot->title ?? 'Удаленный лот' }}</div>
                        <p class="text-sm text-slate-200">{{ $review->comment }}</p>
                    </div>
                @endforeach
            @endif
        </div>
    </div>
</div>
@endsection