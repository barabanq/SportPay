@extends('layouts.app')

@section('title', __('Регистрация на SportPay'))

@section('content')
<div class="max-w-md mx-auto bg-slate-900 border border-slate-800 rounded-2xl p-5 sm:p-6 space-y-6 mt-6">
    <div class="text-center space-y-1">
        <h1 class="text-xl sm:text-2xl font-extrabold text-white">{{ __('Регистрация на SportPay') }}</h1>
        <p class="text-xs text-slate-400">{{ __('Создайте аккаунт продавца или покупателя (+1 000 ₽ тестовый бонус)') }}</p>
    </div>

    <form action="{{ route('register.submit') }}" method="POST" class="space-y-4 text-sm">
        @csrf
        <div>
            <label class="block text-xs text-slate-400 font-semibold mb-1">{{ __('Никнейм на бирже (Username)') }}</label>
            <input type="text" name="username" value="{{ old('username') }}" required minlength="3" placeholder="Coach_Pro" class="w-full bg-slate-950 border border-slate-800 rounded-xl px-4 py-2.5 text-white focus:outline-none focus:border-emerald-500">
        </div>

        <div>
            <label class="block text-xs text-slate-400 font-semibold mb-1">{{ __('Электронная почта (Email)') }}</label>
            <input type="email" name="email" value="{{ old('email') }}" required placeholder="you@example.com" class="w-full bg-slate-950 border border-slate-800 rounded-xl px-4 py-2.5 text-white focus:outline-none focus:border-emerald-500">
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
            <div>
                <label class="block text-xs text-slate-400 font-semibold mb-1">{{ __('Пароль') }}</label>
                <input type="password" name="password" required minlength="6" placeholder="••••••" class="w-full bg-slate-950 border border-slate-800 rounded-xl px-4 py-2.5 text-white focus:outline-none focus:border-emerald-500">
            </div>
            <div>
                <label class="block text-xs text-slate-400 font-semibold mb-1">{{ __('Повтор пароля') }}</label>
                <input type="password" name="password_confirmation" required minlength="6" placeholder="••••••" class="w-full bg-slate-950 border border-slate-800 rounded-xl px-4 py-2.5 text-white focus:outline-none focus:border-emerald-500">
            </div>
        </div>

        <button type="submit" class="w-full py-3 rounded-xl bg-emerald-500 hover:bg-emerald-400 text-slate-950 font-extrabold text-xs uppercase tracking-wider transition">
            {{ __('Создать аккаунт') }}
        </button>
    </form>

    <div class="pt-4 border-t border-slate-800 text-center text-xs text-slate-400">
        {{ __('Уже зарегистрированы?') }}
        <a href="{{ route('login') }}" class="text-emerald-400 font-bold hover:underline ml-1">{{ __('Войти') }}</a>
    </div>
</div>
@endsection
