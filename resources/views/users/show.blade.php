@extends('layouts.app')

@section('title', "{$profileUser->username} — SportPay")

@section('content')
@php
    $isOnline = $profileUser->isOnline();
@endphp
<div class="bg-slate-900 border border-slate-800 rounded-2xl p-4 sm:p-6 space-y-5">
    <div class="flex flex-wrap items-center justify-between gap-5">
        <div class="flex items-center gap-4">
            <div class="relative shrink-0">
                @if($profileUser->avatarUrl())
                    <img src="{{ $profileUser->avatarUrl() }}" alt="{{ $profileUser->username }}" class="w-16 h-16 sm:w-20 sm:h-20 rounded-2xl object-cover border-2 border-emerald-500/40">
                @else
                    <div class="w-16 h-16 sm:w-20 sm:h-20 rounded-2xl bg-emerald-500/20 border border-emerald-500/40 flex items-center justify-center text-2xl font-extrabold text-emerald-400">
                        {{ strtoupper(substr($profileUser->username, 0, 1)) }}
                    </div>
                @endif
                <span class="absolute -bottom-1 -right-1 w-4 h-4 rounded-full border-2 border-slate-900 {{ $isOnline ? 'bg-emerald-400' : 'bg-slate-600' }}"></span>
            </div>
            <div>
                <div class="flex flex-wrap items-center gap-2">
                    <h1 class="text-xl sm:text-2xl font-extrabold text-white">{{ $profileUser->username }}</h1>
                    <span class="text-xs font-bold px-2.5 py-0.5 rounded-full {{ $isOnline ? 'bg-emerald-500/20 text-emerald-300 border border-emerald-500/30' : 'bg-slate-800 text-slate-400 border border-slate-700' }}">
                        {{ $isOnline ? '🟢 ' . __('Онлайн') : '⚪ ' . __('Офлайн') }}
                    </span>
                    <span class="text-xs px-2.5 py-0.5 rounded-full bg-slate-800 text-slate-300 border border-slate-700">
                        {{ $profileUser->status->label() }}
                    </span>
                </div>
                <p class="text-xs text-slate-400 mt-1">
                    {{ __('На SportPay с') }} {{ $profileUser->created_at?->format('d.m.Y') ?? '2026' }}
                </p>
            </div>
        </div>

        <div class="flex items-center gap-4 sm:gap-6 bg-slate-950/70 px-4 sm:px-6 py-3 rounded-xl border border-slate-800 w-full sm:w-auto justify-around">
            <div class="text-center">
                <div class="text-[11px] text-slate-400">{{ __('Рейтинг продавца') }}</div>
                <div class="text-lg sm:text-xl font-extrabold text-amber-400">★ {{ number_format((float) $profileUser->rating, 2) }}</div>
            </div>
            <div class="h-8 w-px bg-slate-800"></div>
            <div class="text-center">
                <div class="text-[11px] text-slate-400">{{ __('Отзывов') }}</div>
                <div class="text-lg sm:text-xl font-extrabold text-white">{{ $completedSalesCount }}</div>
            </div>
            <div class="h-8 w-px bg-slate-800"></div>
            <div class="text-center">
                <div class="text-[11px] text-slate-400">{{ __('Активных лотов') }}</div>
                <div class="text-lg sm:text-xl font-extrabold text-emerald-400">{{ $profileUser->lots->count() }}</div>
            </div>
        </div>
    </div>

    <!-- Форма загрузки аватарки (только для владельца профиля или арбитра) -->
    @if($user && ($user->id === $profileUser->id || $user->isArbiter()))
        <div class="pt-4 border-t border-slate-800 flex flex-wrap items-center justify-between gap-3">
            <form action="{{ route('users.avatar.update', $profileUser) }}" method="POST" enctype="multipart/form-data" class="flex flex-wrap items-center gap-2.5">
                @csrf
                <label class="text-xs font-semibold text-slate-300">🖼️ {{ __('Аватар профиля:') }}</label>
                <input type="file" name="avatar" accept="image/*" required class="text-xs text-slate-300 file:mr-2.5 file:py-1.5 file:px-3 file:rounded-lg file:border-0 file:text-xs file:font-bold file:bg-slate-800 file:text-emerald-400 hover:file:bg-slate-700 cursor-pointer">
                <button type="submit" class="px-3.5 py-1.5 rounded-xl bg-emerald-500 hover:bg-emerald-400 text-slate-950 font-extrabold text-xs transition">
                    {{ __('Загрузить аватар') }}
                </button>
            </form>

            @if($profileUser->avatarUrl())
                <form action="{{ route('users.avatar.update', $profileUser) }}" method="POST">
                    @csrf
                    <input type="hidden" name="remove_avatar" value="1">
                    <button type="submit" class="px-3 py-1.5 rounded-xl bg-rose-500/15 hover:bg-rose-500/25 text-rose-300 text-xs font-bold transition">
                        ✕ {{ __('Удалить аватар') }}
                    </button>
                </form>
            @endif
        </div>
    @endif
</div>

<div class="grid grid-cols-1 lg:grid-cols-3 gap-5 sm:gap-6">
    <div class="lg:col-span-2 bg-slate-900 border border-slate-800 rounded-2xl p-4 sm:p-5">
        <h2 class="text-base sm:text-lg font-bold text-white mb-4">{{ __('Активные лоты продавца') }} {{ $profileUser->username }}</h2>
        <div class="space-y-3">
            @if($profileUser->lots->isEmpty())
                <div class="text-sm text-slate-500 py-6 text-center">{{ __('У пользователя нет активных лотов.') }}</div>
            @else
                @foreach ($profileUser->lots as $lot)
                    <div class="p-4 rounded-xl bg-slate-800/50 border border-slate-700/60 flex flex-wrap items-center justify-between gap-4">
                        <div class="flex items-center gap-3">
                            @if($lot->imageUrl())
                                <a href="{{ route('lots.show', $lot) }}" class="shrink-0">
                                    <img src="{{ $lot->imageUrl() }}" alt="{{ $lot->title }}" class="w-12 h-12 rounded-xl object-cover border border-slate-700">
                                </a>
                            @endif
                            <div>
                                <span class="text-xs px-2 py-0.5 rounded bg-slate-800 text-slate-300">{{ $lot->product_type }}</span>
                                <a href="{{ route('lots.show', $lot) }}" class="block font-bold text-white hover:text-emerald-400 mt-1">{{ $lot->title }}</a>
                                <div class="text-xs text-slate-400 mt-0.5">{{ $lot->description }}</div>
                            </div>
                        </div>
                        <div class="text-right shrink-0 space-y-2">
                            <div class="text-lg font-extrabold text-emerald-400">{{ number_format((float) $lot->price, 0, '.', ' ') }} ₽</div>
                            @if($user->id !== $profileUser->id)
                                <form action="{{ route('lots.purchase', $lot) }}" method="POST">
                                    @csrf
                                    <button type="submit" class="px-4 py-1.5 rounded-lg bg-emerald-500 hover:bg-emerald-400 text-slate-950 font-bold text-xs">
                                        {{ __('Купить') }}
                                    </button>
                                </form>
                            @else
                                <a href="{{ route('lots.edit', $lot) }}" class="inline-block px-3 py-1 rounded-lg bg-slate-800 hover:bg-slate-700 border border-slate-700 text-xs text-slate-200">
                                    ✎ {{ __('Изм.') }}
                                </a>
                            @endif
                        </div>
                    </div>
                @endforeach
            @endif
        </div>
    </div>

    <div class="bg-slate-900 border border-slate-800 rounded-2xl p-4 sm:p-5">
        <h2 class="text-base sm:text-lg font-bold text-white mb-4">{{ __('Отзывы покупателей') }} ({{ $completedSalesCount }})</h2>
        <div class="space-y-3">
            @if($profileUser->receivedReviews->isEmpty())
                <div class="text-sm text-slate-500 py-6 text-center">{{ __('Отзывов пока нет.') }}</div>
            @else
                @foreach ($profileUser->receivedReviews as $review)
                    <div class="p-3.5 rounded-xl bg-slate-800/60 border border-slate-700/60 space-y-1.5">
                        <div class="flex items-center justify-between text-xs">
                            <a href="{{ route('users.show', $review->author_id) }}" class="font-bold text-emerald-400 hover:underline">
                                {{ $review->author->username ?? 'Buyer' }}
                            </a>
                            <span class="text-amber-400 font-bold">{{ str_repeat('★', $review->rating) }}</span>
                        </div>
                        <div class="text-xs text-slate-400">{{ __('Лот') }}: {{ $review->lot->title ?? '—' }}</div>
                        <p class="text-sm text-slate-200">{{ $review->comment }}</p>
                    </div>
                @endforeach
            @endif
        </div>
    </div>
</div>
@endsection
