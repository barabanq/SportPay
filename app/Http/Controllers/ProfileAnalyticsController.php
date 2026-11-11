<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\User;
use App\Services\ProfileAnalyticsService;
use Illuminate\Contracts\View\View;

class ProfileAnalyticsController extends Controller
{
    public function __construct(
        private readonly ProfileAnalyticsService $analyticsService
    ) {
    }

    public function index(): View
    {
        $user = $this->currentUser();
        $stats = $this->analyticsService->getDashboardStats($user);

        return view('analytics.index', [
            'user'     => $user,
            'allUsers' => User::orderBy('id')->get(),
            'stats'    => $stats,
        ]);
    }
}