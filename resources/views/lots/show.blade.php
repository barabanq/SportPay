@extends('layouts.app')

@section('title', "{$lot->title} — SportPay")

@section('content')
@php
    $typeLabels = [
        'training_plan' => __('Программа тренировок'),
        'coaching' => __('Тренерство / Коучинг'),
        'race_slot' => __('Слот на соревнование'),
        'equipment' => __('Спортивная экипировка'),
    ];
    $sellerOnline = $lot->seller?->isOnline() ?? false;
@endphp

<div class="mb-2">
    <a href="{{ route('home') }}" class="text-xs text-slate-400 hover:text-emerald-400 transition">
        {{ __('← Вернуться в каталог лотов') }}
    </a>
</div>

<div class="grid grid-cols-1 lg:grid-cols-3 gap-5 sm:gap-6">
    <!-- Левая колонка: Фото, Информация о лоте и отзывы -->
    <div class="lg:col-span-2 space-y-5 sm:space-y-6">
        <div class="bg-slate-900 border border-slate-800 rounded-2xl p-4 sm:p-6 space-y-4 sm:space-y-5">
            <div class="flex flex-wrap items-center justify-between gap-2">
                <div class="flex flex-wrap items-center gap-2">
                    <span class="text-xs px-2.5 py-1 rounded-lg bg-slate-800 text-slate-300 font-semibold">
                        {{ $typeLabels[$lot->product_type] ?? $lot->product_type }}
                    </span>
                    @if($lot->hasAutoDelivery())
                        <span class="px-2.5 py-1 text-xs font-extrabold rounded-lg bg-emerald-500/20 text-emerald-300 border border-emerald-500/30">
                            ⚡ {{ __('Мгновенная автовыдача') }}
                        </span>
                    @endif
                </div>
                <span class="text-xs text-slate-400">
                    #{{ $lot->id }} • {{ __('В наличии:') }} <b class="text-white">{{ $lot->stock_quantity }} {{ __('шт.') }}</b>
                </span>
            </div>

            <h1 class="text-xl sm:text-2xl font-extrabold text-white">{{ $lot->title }}</h1>

            @if($lot->imageUrl())
                <div class="rounded-2xl overflow-hidden border border-slate-800 bg-slate-950">
                    <img src="{{ $lot->imageUrl() }}" alt="{{ $lot->title }}" class="w-full max-h-96 object-cover">
                </div>
            @endif

            @if(filled($lot->description))
                <div class="p-3.5 sm:p-4 rounded-xl bg-slate-950/80 border border-slate-800/80 text-sm text-slate-200 whitespace-pre-line leading-relaxed">{{ $lot->description }}</div>
            @else
                <div class="p-3.5 sm:p-4 rounded-xl bg-slate-950/40 border border-slate-800/50 text-xs text-slate-500 italic">
                    {{ __('Продавец не указал подробное описание для этого лота.') }}
                </div>
            @endif

            <div class="grid grid-cols-1 sm:grid-cols-3 gap-3 pt-2 text-xs">
                <div class="p-3 rounded-xl bg-slate-950/60 border border-slate-800">
                    <div class="text-slate-400">{{ __('Защита сделки') }}</div>
                    <div class="font-bold text-emerald-400 mt-0.5">{{ __('🛡️ 100% Эскроу SportPay') }}</div>
                </div>
                <div class="p-3 rounded-xl bg-slate-950/60 border border-slate-800">
                    <div class="text-slate-400">{{ __('Способ получения') }}</div>
                    <div class="font-bold text-white mt-0.5">
                        {{ $lot->hasAutoDelivery() ? __('⚡ Автовыдача в чат сразу после оплаты') : __('💬 В чате сделки с продавцом') }}
                    </div>
                </div>
                <div class="p-3 rounded-xl bg-slate-950/60 border border-slate-800">
                    <div class="text-slate-400">{{ __('Статус лота') }}</div>
                    <div class="font-bold text-slate-200 mt-0.5">{{ $lot->status->label() }}</div>
                </div>
            </div>
        </div>

        <!-- Отзывы по этому лоту -->
        <div class="bg-slate-900 border border-slate-800 rounded-2xl p-4 sm:p-6 space-y-4">
            <h2 class="text-base font-extrabold text-white">
                {{ __('Отзывы покупателей по лоту') }} ({{ $lot->reviews->count() }})
            </h2>

            @if($lot->reviews->isEmpty())
                <div class="py-8 text-center text-xs text-slate-500">
                    {{ __('По этому лоту пока нет отзывов. Станьте первым покупателем!') }}
                </div>
            @else
                <div class="space-y-3">
                    @foreach ($lot->reviews as $review)
                        <div class="p-3.5 sm:p-4 rounded-xl bg-slate-950/70 border border-slate-800 space-y-1.5">
                            <div class="flex items-center justify-between text-xs">
                                <a href="{{ route('users.show', $review->author_id) }}" class="font-bold text-emerald-400 hover:underline">
                                    {{ $review->author->username ?? 'Buyer' }}
                                </a>
                                <span class="text-amber-400 font-bold">{{ str_repeat('★', (int) $review->rating) }}</span>
                            </div>
                            <p class="text-sm text-slate-200">{{ $review->comment }}</p>
                            <div class="text-[11px] text-slate-500">{{ $review->created_at?->format('d.m.Y H:i') }}</div>
                        </div>
                    @endforeach
                </div>
            @endif
        </div>
    </div>

    <!-- Правая колонка: Покупка и карточка продавца с аватаром и онлайн-статусом -->
    <div class="space-y-5 sm:space-y-6">
        <div class="bg-slate-900 border border-slate-800 rounded-2xl p-4 sm:p-6 space-y-5">
            <div class="flex items-baseline justify-between">
                <div>
                    <div class="text-xs text-slate-400 uppercase font-bold">{{ __('Цена за 1 шт.') }}</div>
                    <div class="text-2xl sm:text-3xl font-extrabold text-emerald-400 mt-1">
                        {{ number_format((float) $lot->price, 0, '.', ' ') }} ₽
                    </div>
                </div>
                <div class="text-right">
                    <div class="text-xs text-slate-400">{{ __('Доступно') }}</div>
                    <div class="text-sm font-bold text-white">{{ $lot->stock_quantity }} {{ __('шт.') }}</div>
                </div>
            </div>

            @if($user && $lot->seller_id !== $user->id)
                @if($lot->stock_quantity > 0)
                    <form action="{{ route('lots.purchase', $lot) }}" method="POST" class="space-y-4">
                        @csrf
                        <div>
                            <label for="purchase-qty" class="block text-xs text-slate-400 font-semibold mb-1.5">
                                {{ __('Выберите количество') }} (1 — {{ $lot->stock_quantity }} {{ __('шт.') }})
                            </label>
                            <div class="flex items-center justify-between gap-3">
                                <input
                                    type="number"
                                    id="purchase-qty"
                                    name="quantity"
                                    value="1"
                                    min="1"
                                    max="{{ $lot->stock_quantity }}"
                                    required
                                    class="w-24 sm:w-28 bg-slate-950 border border-slate-700 rounded-xl px-3.5 py-2.5 text-sm font-bold text-white focus:outline-none focus:border-emerald-500"
                                >
                                <div class="text-xs text-slate-400 text-right">
                                    {{ __('К оплате:') }} <b id="total-price-preview" class="text-emerald-400 text-sm block sm:inline">{{ number_format((float) $lot->price, 0, '.', ' ') }} ₽</b>
                                </div>
                            </div>
                        </div>

                        <button type="submit" class="w-full py-3 rounded-xl bg-emerald-500 hover:bg-emerald-400 text-slate-950 font-extrabold text-sm transition">
                            {{ __('Купить безопасно через SportPay') }}
                        </button>
                    </form>
                    <p class="text-[11px] text-slate-400 leading-relaxed">
                        {{ __('Продавец получит средства только после того, как вы проверите услугу/товар и подтвердите выполнение заказа.') }}
                    </p>
                @else
                    <div class="p-3 rounded-xl bg-rose-500/10 border border-rose-500/30 text-center text-xs text-rose-300 font-bold">
                        {{ __('Товар временно закончился') }}
                    </div>
                @endif
            @else
                <div class="space-y-2 pt-2 border-t border-slate-800">
                    <div class="text-xs text-slate-400 mb-2">{{ __('Это ваш собственный лот') }}</div>
                    <a href="{{ route('lots.edit', $lot) }}" class="block w-full py-2.5 rounded-xl bg-slate-800 hover:bg-slate-700 border border-slate-700 text-center text-xs font-bold text-white transition">
                        {{ __('✎ Редактировать лот') }}
                    </a>
                    <form action="{{ route('lots.destroy', $lot) }}" method="POST" onsubmit="return confirm('Удалить этот лот?');">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="w-full py-2.5 rounded-xl bg-rose-500/10 hover:bg-rose-500/20 border border-rose-500/30 text-rose-300 font-bold text-xs transition">
                            {{ __('✕ Удалить лот') }}
                        </button>
                    </form>
                </div>
            @endif
        </div>

        @if($lot->seller)
        <div class="bg-slate-900 border border-slate-800 rounded-2xl p-4 sm:p-6 space-y-4">
            <div class="flex items-center justify-between">
                <div class="text-xs text-slate-400 uppercase font-bold">{{ __('Продавец') }}</div>
                <span class="text-[11px] font-bold px-2 py-0.5 rounded-full {{ $sellerOnline ? 'bg-emerald-500/20 text-emerald-300 border border-emerald-500/30' : 'bg-slate-800 text-slate-400' }}">
                    {{ $sellerOnline ? '🟢 ' . __('Онлайн') : '⚪ ' . __('Офлайн') }}
                </span>
            </div>
            <div class="flex items-center justify-between">
                <div class="flex items-center gap-3">
                    <div class="relative shrink-0">
                        @if($lot->seller->avatarUrl())
                            <img src="{{ $lot->seller->avatarUrl() }}" alt="{{ $lot->seller->username }}" class="w-11 h-11 rounded-xl object-cover border border-emerald-500/40">
                        @else
                            <div class="w-11 h-11 rounded-xl bg-emerald-500/20 border border-emerald-500/40 flex items-center justify-center text-base font-extrabold text-emerald-400">
                                {{ strtoupper(substr($lot->seller->username, 0, 1)) }}
                            </div>
                        @endif
                        <span class="absolute -bottom-0.5 -right-0.5 w-3 h-3 rounded-full border-2 border-slate-900 {{ $sellerOnline ? 'bg-emerald-400' : 'bg-slate-600' }}"></span>
                    </div>
                    <div>
                        <a href="{{ route('users.show', $lot->seller_id) }}" class="font-extrabold text-white hover:text-emerald-400 transition">
                            {{ $lot->seller->username }}
                        </a>
                        <div class="text-xs text-amber-400 font-bold mt-0.5">
                            ★ {{ number_format((float) $lot->seller->rating, 2) }}
                        </div>
                    </div>
                </div>
                <a href="{{ route('users.show', $lot->seller_id) }}" class="px-3 py-1.5 rounded-lg bg-slate-800 hover:bg-slate-700 text-xs text-slate-200 font-semibold transition">
                    {{ __('Профиль →') }}
                </a>
            </div>
        </div>
        @endif
    </div>
</div>

<script>
(function () {
    const qtyInput = document.getElementById('purchase-qty');
    const preview = document.getElementById('total-price-preview');
    const unitPrice = {{ (float) $lot->price }};
    const maxStock = {{ (int) $lot->stock_quantity }};

    if (qtyInput && preview) {
        qtyInput.addEventListener('input', function () {
            let val = parseInt(this.value || '1', 10);
            if (isNaN(val) || val < 1) val = 1;
            if (val > maxStock) val = maxStock;
            const total = Math.round(val * unitPrice);
            preview.textContent = total.toLocaleString('ru-RU') + ' ₽';
        });
    }
})();
</script>
@endsection
