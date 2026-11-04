@extends('layouts.app')

@section('title', __('Вход в SportPay'))

@section('content')
<div class="max-w-md mx-auto bg-slate-900 border border-slate-800 rounded-2xl p-5 sm:p-6 space-y-6 mt-6">
    <div class="text-center space-y-1">
        <h1 class="text-xl sm:text-2xl font-extrabold text-white">{{ __('Вход в SportPay') }}</h1>
        <p class="text-xs text-slate-400">{{ __('Введите ваш логин (или email) и пароль для входа на биржу') }}</p>
    </div>

    <form action="{{ route('login.submit') }}" method="POST" class="space-y-4 text-sm">
        @csrf
        <div>
            <label class="block text-xs text-slate-400 font-semibold mb-1">{{ __('Имя пользователя или Email') }}</label>
            <input type="text" name="login" value="{{ old('login') }}" required autofocus placeholder="buyer@sportpay.local" class="w-full bg-slate-950 border border-slate-800 rounded-xl px-4 py-2.5 text-white focus:outline-none focus:border-emerald-500">
        </div>

        <div>
            <label class="block text-xs text-slate-400 font-semibold mb-1">{{ __('Пароль') }}</label>
            <input type="password" name="password" required placeholder="••••••••" class="w-full bg-slate-950 border border-slate-800 rounded-xl px-4 py-2.5 text-white focus:outline-none focus:border-emerald-500">
        </div>

        <label class="flex items-center gap-2 text-xs text-slate-400 cursor-pointer">
            <input type="checkbox" name="remember" class="rounded bg-slate-950 border-slate-700 text-emerald-500">
            <span>{{ __('Запомнить меня') }}</span>
        </label>

        <button type="submit" class="w-full py-3 rounded-xl bg-emerald-500 hover:bg-emerald-400 text-slate-950 font-extrabold text-xs uppercase tracking-wider transition">
            {{ __('Войти на биржу') }}
        </button>
    </form>

    <div class="pt-4 border-t border-slate-800 text-center text-xs text-slate-400">
        {{ __('Еще нет аккаунта на SportPay?') }}
        <a href="{{ route('register') }}" class="text-emerald-400 font-bold hover:underline ml-1">{{ __('Зарегистрироваться') }}</a>
    </div>
</div>
@endsection
