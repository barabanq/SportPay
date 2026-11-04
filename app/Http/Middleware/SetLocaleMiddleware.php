<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Schema;
use Symfony\Component\HttpFoundation\Response;

class SetLocaleMiddleware
{
    public function handle(Request $request, Closure $next): Response
    {
        $locale = (string) $request->session()->get('locale', 'ru');

        if (in_array($locale, ['ru', 'en'], true)) {
            App::setLocale($locale);
        }

        if (Auth::check() && Schema::hasColumn('users', 'last_seen_at')) {
            /** @var \App\Models\User $user */
            $user = Auth::user();
            if ($user->last_seen_at === null || $user->last_seen_at->lt(now()->subMinute())) {
                $user->timestamps = false;
                $user->update(['last_seen_at' => now()]);
                $user->timestamps = true;
            }
        }

        return $next($request);
    }
}
