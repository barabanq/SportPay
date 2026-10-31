<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Enums\LotStatus;
use App\Http\Requests\StoreLotRequest;
use App\Models\Lot;
use Illuminate\Http\RedirectResponse;

class LotController extends Controller
{
    public function store(StoreLotRequest $request): RedirectResponse
    {
        $user = $this->currentUser();

        Lot::create([
            ...$request->validated(),
            'seller_id' => $user->id,
            'status' => LotStatus::Active,
        ]);

        return redirect()->route('home')->with('success', 'Лот успешно выставлен на витрину SportPay!');
    }
}