@extends('layouts.app')

@section('title', __('Отправить запрос в поддержку'))

@section('content')
<div class="max-w-2xl mx-auto bg-slate-900 border border-slate-800 rounded-2xl p-4 sm:p-6 space-y-6">
    <div class="flex items-center justify-between border-b border-slate-800 pb-4">
        <div>
            <h1 class="text-lg sm:text-xl font-extrabold text-white">✉️ {{ __('Отправить запрос в поддержку') }}</h1>
            <p class="text-xs text-slate-400 mt-1">{{ __('Опишите вашу проблему или вопрос — команда SportPay ответит в разделе «Мои запросы»') }}</p>
        </div>
        <a href="{{ route('support.tickets.index') }}" class="text-xs text-emerald-400 font-bold hover:underline">
            📂 {{ __('Мои запросы') }} →
        </a>
    </div>

    <form action="{{ route('support.tickets.store') }}" method="POST" class="space-y-4 text-sm">
        @csrf

        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
            <div>
                <label class="block text-xs text-slate-400 font-semibold mb-1">{{ __('Категория обращения') }}</label>
                <select name="category" class="w-full bg-slate-950 border border-slate-800 rounded-xl px-3.5 py-2.5 text-white text-xs">
                    <option value="order_issue">{{ __('Проблема с заказом / сделкой') }}</option>
                    <option value="payment">{{ __('Пополнение или вывод средств') }}</option>
                    <option value="violation">{{ __('Жалоба на нарушение правил') }}</option>
                    <option value="account">{{ __('Вопросы по аккаунту') }}</option>
                    <option value="other">{{ __('Другой вопрос') }}</option>
                </select>
            </div>

            <div>
                <label class="block text-xs text-slate-400 font-semibold mb-1">{{ __('Связанный заказ (необязательно)') }}</label>
                <select name="order_id" class="w-full bg-slate-950 border border-slate-800 rounded-xl px-3.5 py-2.5 text-white text-xs">
                    <option value="">{{ __('— Без привязки к заказу —') }}</option>
                    @foreach ($myOrders as $ord)
                        <option value="{{ $ord->id }}">
                            {{ __('Заказ') }} #{{ $ord->id }} — {{ $ord->lot->title ?? __('Лот') }} ({{ number_format((float) $ord->total_price, 0, '.', ' ') }} ₽)
                        </option>
                    @endforeach
                </select>
            </div>
        </div>

        <div>
            <label class="block text-xs text-slate-400 font-semibold mb-1">{{ __('Тема запроса') }}</label>
            <input type="text" name="subject" value="{{ old('subject') }}" required minlength="5" placeholder="{{ __('Кратко укажите суть обращения...') }}" class="w-full bg-slate-950 border border-slate-800 rounded-xl px-4 py-2.5 text-white focus:outline-none focus:border-emerald-500">
        </div>

        <div>
            <label class="block text-xs text-slate-400 font-semibold mb-1">{{ __('Подробное сообщение') }}</label>
            <textarea name="message" rows="5" required minlength="10" placeholder="{{ __('Опишите ситуацию максимально подробно...') }}" class="w-full bg-slate-950 border border-slate-800 rounded-xl px-4 py-2.5 text-white focus:outline-none focus:border-emerald-500">{{ old('message') }}</textarea>
        </div>

        <div class="flex justify-end pt-2">
            <button type="submit" class="px-6 py-2.5 rounded-xl bg-emerald-500 hover:bg-emerald-400 text-slate-950 font-extrabold text-xs transition">
                {{ __('Отправить запрос') }}
            </button>
        </div>
    </form>
</div>
@endsection
