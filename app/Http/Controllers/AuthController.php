<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Enums\UserStatus;
use App\Models\User;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

class AuthController extends Controller
{
    public function showLogin(): View
    {
        return view('auth.login');
    }

    public function login(Request $request): RedirectResponse
    {
        $credentials = $request->validate([
            'login' => ['required', 'string'],
            'password' => ['required', 'string'],
        ]);

        $field = filter_var($credentials['login'], FILTER_VALIDATE_EMAIL) ? 'email' : 'username';

        if (Auth::attempt([$field => $credentials['login'], 'password' => $credentials['password']], $request->boolean('remember'))) {
            $request->session()->regenerate();
            $request->session()->forget('logged_out');

            return redirect()->route('home')->with('success', 'Добро пожаловать в SportPay!');
        }

        return back()->withErrors([
            'login' => 'Неверный логин/email или пароль.',
        ])->onlyInput('login');
    }

    public function showRegister(): View
    {
        return view('auth.register');
    }

    public function register(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'username' => ['required', 'string', 'min:3', 'max:50', 'unique:users,username'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', 'string', 'min:6', 'confirmed'],
        ]);

        $user = User::create([
            'username' => $data['username'],
            'email' => strtolower($data['email']),
            'password' => Hash::make($data['password']),
            'wallet_balance' => 1000.00, // Приветственный бонус для теста сделок
            'rating' => 5.00,
            'status' => UserStatus::Active,
        ]);

        Auth::login($user);
        $request->session()->regenerate();
        $request->session()->forget('logged_out');

        return redirect()->route('home')->with('success', "Аккаунт {$user->username} создан! Начислено 1 000 ₽ тестового бонуса.");
    }

    public function logout(Request $request): RedirectResponse
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();
        $request->session()->put('logged_out', true);

        return redirect()->route('login')->with('success', 'Вы вышли из аккаунта.');
    }
}
