@extends('layouts.app')

@section('title', __('Аналитика профиля') . ' — SportPay')

@section('content')
<div class="space-y-6">
    <div class="bg-slate-900 border border-slate-800 rounded-2xl p-4 sm:p-6 flex flex-wrap items-center justify-between gap-4">
        <div>
            <div class="flex items-center gap-2.5">
                <span class="text-xl">📊</span>
                <h1 class="text-lg sm:text-xl font-extrabold text-white">
                    {{ __('Аналитика просмотров профиля') }} — {{ $user->username }}
                </h1>
                <span class="px-2.5 py-0.5 text-[11px] font-extrabold rounded-full bg-emerald-500/20 text-emerald-300 border border-emerald-500/30">
                    PRO Статистика
                </span>
            </div>
            <p class="text-xs text-slate-400 mt-1">
                {{ __('Отслеживайте, сколько спортсменов и покупателей заходили в ваш профиль, откуда шел трафик и кто именно интересовался вашими лотами') }}
            </p>
        </div>

        <a href="{{ route('users.show', $user->id) }}" class="px-4 py-2 rounded-xl bg-slate-800 hover:bg-slate-700 border border-slate-700 text-xs font-bold text-slate-200 transition">
            👤 {{ __('Открыть мой публичный профиль →') }}
        </a>
    </div>

    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
        <div class="bg-slate-900 border border-slate-800 rounded-2xl p-4 sm:p-5 space-y-1.5">
            <div class="text-xs font-bold text-slate-400 uppercase tracking-wider">{{ __('Просмотры сегодня') }}</div>
            <div class="text-2xl sm:text-3xl font-extrabold text-white">{{ $stats['views_today'] }}</div>
            <div class="text-[11px] text-emerald-400 font-semibold">
                👁 {{ __('Всего за все время:') }} {{ $stats['views_total'] }}
            </div>
        </div>

        <div class="bg-slate-900 border border-slate-800 rounded-2xl p-4 sm:p-5 space-y-1.5">
            <div class="text-xs font-bold text-slate-400 uppercase tracking-wider">{{ __('За последние 7 дней') }}</div>
            <div class="flex items-baseline gap-2.5">
                <span class="text-2xl sm:text-3xl font-extrabold text-emerald-400">{{ $stats['views_7d'] }}</span>
                @php $trend = $stats['trend_7d_percent']; @endphp
                <span class="px-2 py-0.5 rounded-lg text-[11px] font-extrabold {{ $trend >= 0 ? 'bg-emerald-500/15 text-emerald-300 border border-emerald-500/30' : 'bg-rose-500/15 text-rose-300 border border-rose-500/30' }}">
                    {{ $trend >= 0 ? '+' : '' }}{{ $trend }}%
                </span>
            </div>
            <div class="text-[11px] text-slate-400">
                {{ __('Предыдущие 7 дней:') }} {{ $stats['views_prev_7d'] }}
            </div>
        </div>

        <div class="bg-slate-900 border border-slate-800 rounded-2xl p-4 sm:p-5 space-y-1.5">
            <div class="text-xs font-bold text-slate-400 uppercase tracking-wider">{{ __('Уникальные зрители (30 дн.)') }}</div>
            <div class="text-2xl sm:text-3xl font-extrabold text-sky-400">{{ $stats['unique_visitors_30d'] }}</div>
            <div class="text-[11px] text-slate-400">
                {{ __('Всего просмотров за 30 дн.:') }} {{ $stats['views_30d'] }}
            </div>
        </div>

        <div class="bg-slate-900 border border-slate-800 rounded-2xl p-4 sm:p-5 space-y-1.5">
            <div class="text-xs font-bold text-slate-400 uppercase tracking-wider">{{ __('Конверсия в сделку (30 дн.)') }}</div>
            <div class="text-2xl sm:text-3xl font-extrabold text-amber-400">{{ $stats['conversion_rate'] }}%</div>
            <div class="text-[11px] text-slate-400">
                {{ __('Оформлено заказов:') }} <b class="text-white">{{ $stats['orders_30d'] }}</b>
            </div>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-5 sm:gap-6">
        <div class="lg:col-span-2 bg-slate-900 border border-slate-800 rounded-2xl p-4 sm:p-6 space-y-4">
            <div>
                <h2 class="text-sm sm:text-base font-extrabold text-white">📈 {{ __('Динамика просмотров по дням (14 дней)') }}</h2>
                <p class="text-xs text-slate-400">{{ __('Наведите на столбец, чтобы увидеть общее число визитов и уникальных пользователей') }}</p>
            </div>

            <div class="h-52 pt-6 pb-2 px-2 rounded-xl bg-slate-950/80 border border-slate-800/80 flex items-end justify-between gap-1.5 sm:gap-2.5">
                @foreach($stats['daily_chart'] as $point)
                    <div class="flex-1 flex flex-col items-center gap-1.5 group relative h-full justify-end">
                        <div class="opacity-0 group-hover:opacity-100 transition pointer-events-none absolute -top-6 bg-slate-800 border border-slate-700 text-[10px] text-white font-bold px-2 py-1 rounded-lg shadow-lg whitespace-nowrap z-10">
                            {{ $point['label'] }}: {{ $point['total'] }} просм. ({{ $point['uniques'] }} уник.)
                        </div>

                        <div class="text-[10px] font-bold {{ $point['total'] > 0 ? 'text-emerald-400' : 'text-slate-600' }}">
                            {{ $point['total'] }}
                        </div>

                        <div class="w-full max-w-[32px] bg-slate-900 rounded-t-lg overflow-hidden flex items-end h-32">
                            <div style="height: {{ $point['height_pct'] }}%;"
                                 class="w-full rounded-t-lg transition-all duration-300 {{ $point['total'] > 0 ? 'bg-gradient-to-t from-emerald-600 to-emerald-400 group-hover:from-emerald-500 group-hover:to-teal-300 shadow-[0_0_12px_rgba(16,185,129,0.35)]' : 'bg-slate-800/70' }}">
                            </div>
                        </div>

                        <div class="text-[10px] text-slate-400 font-mono leading-none">{{ $point['label'] }}</div>
                        <div class="text-[9px] text-slate-500 leading-none">{{ $point['day_ru'] }}</div>
                    </div>
                @endforeach
            </div>
        </div>

        <div class="bg-slate-900 border border-slate-800 rounded-2xl p-4 sm:p-6 space-y-4">
            <div>
                <h2 class="text-sm sm:text-base font-extrabold text-white">🧭 {{ __('Источники переходов') }}</h2>
                <p class="text-xs text-slate-400">{{ __('Откуда пользователи открывали вашу карточку за 30 дней') }}</p>
            </div>

            @if(empty($stats['sources_breakdown']))
                <div class="py-10 text-center text-xs text-slate-500">
                    {{ __('Пока нет данных по источникам переходов.') }}
                </div>
            @else
                <div class="space-y-3.5">
                    @foreach($stats['sources_breakdown'] as $src)
                        <div class="space-y-1.5">
                            <div class="flex items-center justify-between text-xs">
                                <span class="font-semibold text-slate-200">{{ $src['label'] }}</span>
                                <span class="font-extrabold text-emerald-400">{{ $src['count'] }} ({{ $src['percent'] }}%)</span>
                            </div>
                            <div class="w-full h-2 rounded-full bg-slate-950 overflow-hidden border border-slate-800">
                                <div class="h-full bg-gradient-to-r from-emerald-500 to-teal-400 rounded-full" style="width: {{ max(4, $src['percent']) }}%;"></div>
                            </div>
                        </div>
                    @endforeach
                </div>
            @endif
        </div>
    </div>

    <div class="bg-slate-900 border border-slate-800 rounded-2xl overflow-hidden">
        <div class="p-4 sm:p-5 border-b border-slate-800 flex flex-wrap items-center justify-between gap-2">
            <div>
                <h2 class="text-sm sm:text-base font-extrabold text-white">👀 {{ __('Кто заходил в ваш профиль') }}</h2>
                <p class="text-xs text-slate-400">{{ __('Последние посетители вашей страницы и карточки продавца') }}</p>
            </div>
            <span class="px-2.5 py-1 rounded-xl bg-slate-950 border border-slate-800 text-xs text-slate-300 font-bold">
                {{ __('Показано:') }} {{ $stats['recent_visitors']->count() }}
            </span>
        </div>

        @if($stats['recent_visitors']->isEmpty())
            <div class="p-10 text-center text-xs text-slate-500">
                {{ __('Ваш профиль пока никто не просматривал. Переключитесь на другой аккаунт в панели Dev и откройте свой профиль, чтобы проверить трекер!') }}
            </div>
        @else
            <div class="divide-y divide-slate-800/70">
                @foreach($stats['recent_visitors'] as $visit)
                    @php
                        $viewer = $visit->viewer;
                        $online = $viewer?->isOnline() ?? false;
                    @endphp
                    <div class="p-3.5 sm:p-4 hover:bg-slate-800/40 transition flex flex-wrap items-center justify-between gap-3">
                        <div class="flex items-center gap-3">
                            @if($viewer)
                                <div class="relative shrink-0">
                                    @if($viewer->avatarUrl())
                                        <img src="{{ $viewer->avatarUrl() }}" alt="{{ $viewer->username }}" class="w-10 h-10 rounded-xl object-cover border border-slate-700">
                                    @else
                                        <div class="w-10 h-10 rounded-xl bg-slate-800 border border-slate-700 flex items-center justify-center text-xs font-extrabold text-emerald-400">
                                            {{ strtoupper(substr($viewer->username, 0, 1)) }}
                                        </div>
                                    @endif
                                    <span class="absolute -bottom-0.5 -right-0.5 w-2.5 h-2.5 rounded-full border-2 border-slate-900 {{ $online ? 'bg-emerald-400' : 'bg-slate-600' }}"></span>
                                </div>
                                <div>
                                    <div class="flex items-center gap-2">
                                        <a href="{{ route('users.show', $viewer->id) }}" class="font-bold text-sm text-white hover:text-emerald-400 transition">
                                            {{ $viewer->username }}
                                        </a>
                                        <span class="text-xs text-amber-400 font-bold">★ {{ number_format((float) $viewer->rating, 2) }}</span>
                                        <span class="text-[10px] {{ $online ? 'text-emerald-400' : 'text-slate-500' }}">
                                            • {{ $online ? __('Онлайн') : __('Офлайн') }}
                                        </span>
                                    </div>
                                    <div class="text-xs text-slate-400 mt-0.5">
                                        {{ \App\Models\ProfileView::sourceLabel($visit->source) }}
                                    </div>
                                </div>
                            @else
                                <div class="w-10 h-10 rounded-xl bg-slate-800 border border-slate-700 flex items-center justify-center text-sm text-slate-400">
                                    🕵️
                                </div>
                                <div>
                                    <div class="font-bold text-sm text-slate-300">{{ __('Гость биржи') }}</div>
                                    <div class="text-xs text-slate-400">{{ \App\Models\ProfileView::sourceLabel($visit->source) }}</div>
                                </div>
                            @endif
                        </div>

                        <div class="flex items-center gap-3 text-xs text-slate-400">
                            <span>🕒 {{ $visit->created_at?->diffForHumans() ?? $visit->created_at?->format('d.m.Y H:i') }}</span>
                            @if($viewer)
                                <a href="{{ route('users.show', $viewer->id) }}" class="px-3 py-1.5 rounded-xl bg-slate-800 hover:bg-slate-700 text-slate-200 font-semibold transition">
                                    {{ __('Профиль →') }}
                                </a>
                            @endif
                        </div>
                    </div>
                @endforeach
            </div>
        @endif
    </div>
</div>
@endsection