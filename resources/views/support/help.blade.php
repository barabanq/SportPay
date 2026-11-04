@extends('layouts.app')

@section('title', __('Центр помощи SportPay (FAQ)'))

@section('content')
<div class="max-w-4xl mx-auto bg-slate-900 border border-slate-800 rounded-2xl p-4 sm:p-6 space-y-6">
    <div class="flex flex-wrap items-center justify-between gap-4 border-b border-slate-800 pb-4">
        <div>
            <h1 class="text-lg sm:text-xl font-extrabold text-white">💡 {{ __('Центр помощи SportPay (FAQ)') }}</h1>
            <p class="text-xs text-slate-400 mt-1">{{ __('Ответы на частые вопросы о работе эскроу, автовыдачи и арбитража') }}</p>
        </div>
        <div class="flex gap-2">
            <a href="{{ route('support.tickets.index') }}" class="px-3.5 py-2 rounded-xl bg-slate-800 hover:bg-slate-700 text-xs font-bold text-slate-200 transition">
                📂 {{ __('Мои запросы') }}
            </a>
            <a href="{{ route('support.tickets.create') }}" class="px-4 py-2 rounded-xl bg-emerald-500 hover:bg-emerald-400 text-slate-950 font-extrabold text-xs transition">
                ✉️ {{ __('Отправить запрос') }}
            </a>
        </div>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-2 gap-4 text-xs">
        <div class="p-4 rounded-xl bg-slate-950/70 border border-slate-800 space-y-2">
            <div class="font-bold text-white text-sm">{{ __('Как работает защита покупателя (Эскроу)?') }}</div>
            <p class="text-slate-400 leading-relaxed">
                {{ __('При нажатии кнопки «Купить» деньги не уходят продавцу сразу — они замораживаются на специальном эскроу-счете SportPay. Продавец получает выплату только после того, как вы нажмете «Подтвердить получение».') }}
            </p>
        </div>

        <div class="p-4 rounded-xl bg-slate-950/70 border border-slate-800 space-y-2">
            <div class="font-bold text-white text-sm">{{ __('Что такое ⚡ Автовыдача?') }}</div>
            <p class="text-slate-400 leading-relaxed">
                {{ __('Если продавец заранее загрузил ссылку на программу тренировок или промокод слота в поле автовыдачи, бот SportPay отправит вам товар в чат сделки в ту же секунду после оплаты, даже если продавец офлайн.') }}
            </p>
        </div>

        <div class="p-4 rounded-xl bg-slate-950/70 border border-slate-800 space-y-2">
            <div class="font-bold text-white text-sm">{{ __('Что делать, если продавец не выходит на связь?') }}</div>
            <p class="text-slate-400 leading-relaxed">
                {{ __('Откройте карточку сделки и нажмите «Открыть спор (вызвать Арбитра)». Арбитр изучит переписку и вернет вам 100% замороженных средств на кошелек.') }}
            </p>
        </div>

        <div class="p-4 rounded-xl bg-slate-950/70 border border-slate-800 space-y-2">
            <div class="font-bold text-white text-sm">{{ __('Какая комиссия у платформы?') }}</div>
            <p class="text-slate-400 leading-relaxed">
                {{ __('Пополнение баланса и покупка лотов для покупателя без комиссии. При успешном завершении сделки удерживается 5% сервисного сбора с выплаты продавцу.') }}
            </p>
        </div>
    </div>
</div>
@endsection
