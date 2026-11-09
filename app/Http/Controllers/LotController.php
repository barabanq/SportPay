<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Enums\LotStatus;
use App\Models\Lot;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class LotController extends Controller
{
    private const ALLOWED_TYPES = 'team_game,training_plan,coaching,gym_membership,event_ticket,free_listing,equipment';

    public function store(Request $request): RedirectResponse
    {
        $minPrice = $request->input('product_type') === 'free_listing' ? 0 : 50;

        $data = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'product_type' => ['required', 'string', 'in:' . self::ALLOWED_TYPES],
            'description' => ['nullable', 'string', 'max:2000'],
            'location_name' => ['nullable', 'string', 'max:255'],
            'latitude' => ['nullable', 'numeric', 'between:-90,90'],
            'longitude' => ['nullable', 'numeric', 'between:-180,180'],
            'image' => ['nullable', 'image', 'max:4096'],
            'auto_delivery_text' => ['nullable', 'string', 'max:2000'],
            'price' => ['required', 'numeric', "min:{$minPrice}", 'max:1000000'],
            'stock_quantity' => ['required', 'integer', 'min:1', 'max:1000'],
        ]);

        if ($data['product_type'] === 'free_listing') {
            $data['price'] = 0;
        }

        unset($data['image']);

        if ($request->hasFile('image')) {
            $data['image_path'] = $request->file('image')->store('lots', 'public');
        }

        $seller = $this->currentUser();
        $seller->update(['last_seen_at' => now()]);

        $lot = Lot::create([
            ...$data,
            'seller_id' => $seller->id,
            'status' => LotStatus::Active,
        ]);

        app(\App\Services\TelegramBotService::class)->notifySubscribersAboutNewLot($lot);

        return redirect()->route('home')->with('success', __('Лот успешно опубликован на витрине!'));
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

        $minPrice = $request->input('product_type') === 'free_listing' ? 0 : 50;

        $data = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'product_type' => ['required', 'string', 'in:' . self::ALLOWED_TYPES],
            'description' => ['nullable', 'string', 'max:2000'],
            'location_name' => ['nullable', 'string', 'max:255'],
            'latitude' => ['nullable', 'numeric', 'between:-90,90'],
            'longitude' => ['nullable', 'numeric', 'between:-180,180'],
            'image' => ['nullable', 'image', 'max:4096'],
            'remove_image' => ['nullable', 'boolean'],
            'auto_delivery_text' => ['nullable', 'string', 'max:2000'],
            'price' => ['required', 'numeric', "min:{$minPrice}", 'max:1000000'],
            'stock_quantity' => ['required', 'integer', 'min:0', 'max:1000'],
        ]);

        if ($data['product_type'] === 'free_listing') {
            $data['price'] = 0;
        }

        unset($data['image'], $data['remove_image']);

        if ($request->boolean('remove_image') && $lot->image_path) {
            Storage::disk('public')->delete($lot->image_path);
            $data['image_path'] = null;
        }

        if ($request->hasFile('image')) {
            if ($lot->image_path) {
                Storage::disk('public')->delete($lot->image_path);
            }
            $data['image_path'] = $request->file('image')->store('lots', 'public');
        }

        $lot->update($data);

        return redirect()->route('home')->with('success', __('Лот успешно обновлен!'));
    }

    public function destroy(Lot $lot): RedirectResponse
    {
        $this->authorizeAction('delete', $lot);

        if ($lot->image_path) {
            Storage::disk('public')->delete($lot->image_path);
        }

        $lot->delete();

        return redirect()->route('home')->with('success', __('Лот удален.'));
    }

    public function show(Lot $lot): View
    {
        $lot->load(['seller', 'reviews.author']);

        return view('lots.show', [
            'user' => $this->currentUser(),
            'lot' => $lot,
        ]);
    }
}
