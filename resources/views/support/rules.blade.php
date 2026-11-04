@extends('layouts.app')

@section('title', __('Правила биржи SportPay'))

@section('content')
<div class="max-w-4xl mx-auto bg-slate-900 border border-slate-800 rounded-2xl p-4 sm:p-6 space-y-6">
    <div class="flex flex-wrap items-center justify-between gap-3 border-b border-slate-800 pb-4">
        <div>
            <h1 class="text-lg sm:text-xl font-extrabold text-white">📜 {{ __('Правила биржи SportPay') }}</h1>
            <p class="text-xs text-slate-400 mt-1">{{ __('Обязательный регламент для покупателей, тренеров и продавцов спортивных товаров') }}</p>
        </div>
        <a href="{{ route('support.tickets.create') }}" class="px-4 py-2 rounded-xl bg-emerald-500 hover:bg-emerald-400 text-slate-950 font-extrabold text-xs transition">
            ✉️ {{ __('Отправить запрос') }}
        </a>
    </div>

    <div class="space-y-4 text-sm text-slate-300 leading-relaxed">
        <div class="p-4 rounded-xl bg-slate-950/70 border border-slate-800 space-y-1.5">
            <h2 class="font-bold text-emerald-400">{{ __('1. Безопасность сделок и Эскроу') }}</h2>
            <p class="text-xs text-slate-300">
                {{ __('Все расчеты проводятся строго через гарант SportPay. Запрещено передавать реквизиты для прямой оплаты в обход системы или уводить покупателя в сторонние мессенджеры до оплаты заказа.') }}
            </p>
        </div>

        <div class="p-4 rounded-xl bg-slate-950/70 border border-slate-800 space-y-1.5">
            <h2 class="font-bold text-emerald-400">{{ __('2. Обязанности продавцов и тренеров') }}</h2>
            <p class="text-xs text-slate-300">
                {{ __('Продавец обязан указывать достоверные параметры тренировочных программ, коучинга, слотов на соревнования и спортивной экипировки. При использовании автовыдачи (⚡) передаваемые ссылки и материалы должны быть рабочими.') }}
            </p>
        </div>

        <div class="p-4 rounded-xl bg-slate-950/70 border border-slate-800 space-y-1.5">
            <h2 class="font-bold text-emerald-400">{{ __('3. Порядок подтверждения и Арбитраж') }}</h2>
            <p class="text-xs text-slate-300">
                {{ __('Покупатель подтверждает выполнение заказа только после полной проверки полученного товара или услуги. В случае несоответствия описанию любая сторона вправе открыть спор и вызвать официального арбитра SportPay. Комиссия биржи при успешной выплате продавцу составляет 5%.') }}
            </p>
        </div>
    </div>
</div>
@endsection
