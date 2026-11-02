<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Enums\LotStatus;
use App\Models\Lot;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class LotController extends Controller
{
    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'product_type' => ['required', 'string', 'in:training_plan,coaching,race_slot,equipment'],
            'description' => ['required', 'string', 'max:2000'],
            'auto_delivery_text' => ['nullable', 'string', 'max:2000'],
            'price' => ['required', 'numeric', 'min:50', 'max:1000000'],
            'stock_quantity' => ['required', 'integer', 'min:1', 'max:1000'],
        ]);

        Lot::create([
            ...$data,
            'seller_id' => $this->currentUser()->id,
            'status' => LotStatus::Active,
        ]);

        return redirect()->route('home')->with('success', 'Лот успешно опубликован на витрине!');
    }

    public function edit(Lot $lot): View
    {
        $this->authorizeAction('update', $lot);

        return view('lots.edit', [
            'user' => $this->currentUser(),
            'lot' => $lot,
        ]);
    }

    public function update(Request $request, Lot $lot): RedirectResponse
    {
        $this->authorizeAction('update', $lot);

        $data = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'product_type' => ['required', 'string', 'in:training_plan,coaching,race_slot,equipment'],
            'description' => ['required', 'string', 'max:2000'],
            'auto_delivery_text' => ['nullable', 'string', 'max:2000'],
            'price' => ['required', 'numeric', 'min:50', 'max:1000000'],
            'stock_quantity' => ['required', 'integer', 'min:0', 'max:1000'],
        ]);

        $lot->update($data);

        return redirect()->route('home')->with('success', 'Лот успешно обновлен!');
    }

    public function destroy(Lot $lot): RedirectResponse
    {
        $this->authorizeAction('delete', $lot);

        $lot->delete();

        return redirect()->route('home')->with('success', 'Лот удален.');
    }
}