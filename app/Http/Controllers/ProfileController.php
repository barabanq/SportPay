<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Contracts\View\View;

class ProfileController extends Controller
{
    public function show(User $profileUser): View
    {
        $profileUser->load([
            'lots' => fn ($q) => $q->where('stock_quantity', '>', 0)->latest(),
            'receivedReviews' => fn ($q) => $q->with(['author', 'lot'])->latest(),
        ]);

        $completedSalesCount = $profileUser->receivedReviews->count();

        return view('users.show', [
            'user' => $this->currentUser(),
            'allUsers' => User::orderBy('id')->get(),
            'profileUser' => $profileUser,
            'completedSalesCount' => $completedSalesCount,
        ]);
    }
}