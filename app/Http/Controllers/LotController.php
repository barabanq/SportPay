<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Enums\LotStatus;
use App\Http\Requests\StoreLotRequest;
use App\Http\Requests\UpdateLotRequest;
use App\Models\Lot;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;

class LotController extends Controller
{
    public function store(StoreLotRequest $request): RedirectResponse
    {
        Lot::create([
            ...$request->validated(),
            'seller_id' => $this->currentUser()->id,
            'status' => LotStatus::Active,
        ]);

        return redirect()->route('home')->with('success', 'Лот успешно выставлен на витрину SportPay!');
    }

    public function edit(Lot $lot): View
    {
        $this->ensureOwnership($lot);

        return view('lots.edit', compact('lot'));
    }

    public function update(UpdateLotRequest $request, Lot $lot): RedirectResponse
    {
        $this->ensureOwnership($lot);

        $data = $request->validated();
        $status = (int) $data['stock_quantity'] > 0 ? LotStatus::Active : LotStatus::Archived;

        $lot->update([
            ...$data,
            'status' => $status,
        ]);

        return redirect()->route('home')->with('success', "Лот «{$lot->title}» успешно обновлен!");
    }

    public function destroy(Lot $lot): RedirectResponse
    {
        $this->ensureOwnership($lot);

        // Если по лоту уже были сделки — архивируем для сохранения истории заказов, иначе удаляем из БД
        if ($lot->orders()->exists()) {
            $lot->update([
                'stock_quantity' => 0,
                'status' => LotStatus::Archived,
            ]);

            return redirect()->route('home')->with('success', 'Лот снят с витрины и перенесен в архив (так как по нему есть история сделок).');
        }

        $lot->delete();

        return redirect()->route('home')->with('success', 'Лот полностью удален.');
    }

    private function ensureOwnership(Lot $lot): void
    {
        if ($lot->seller_id !== $this->currentUser()->id) {
            abort(403, 'Вы можете редактировать или удалять только собственные лоты.');
        }
    }
}