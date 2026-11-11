<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Enums\OrderStatus;
use App\Models\Order;
use App\Models\User;
use App\Services\ProfileAnalyticsService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class ProfileController extends Controller
{
    public function show(Request $request, User $profileUser): View
    {
        app(ProfileAnalyticsService::class)->recordView($profileUser, $this->currentUser(), $request, 'profile_page');

        $profileUser->load([
            'lots' => fn ($q) => $q->where('stock_quantity', '>', 0)->latest(),
            'receivedReviews' => fn ($q) => $q->with(['author', 'lot'])->latest(),
        ]);

        $completedSalesCount = Order::where('seller_id', $profileUser->id)
            ->where('status', OrderStatus::Completed)
            ->count();

        return view('users.show', [
            'user' => $this->currentUser(),
            'allUsers' => User::orderBy('id')->get(),
            'profileUser' => $profileUser,
            'completedSalesCount' => max($completedSalesCount, $profileUser->receivedReviews->count()),
        ]);
    }

    public function updateAvatar(Request $request, User $profileUser): RedirectResponse
    {
        $currentUser = $this->currentUser();

        if ($currentUser->id !== $profileUser->id && !$currentUser->isArbiter()) {
            abort(403, 'Вы можете менять аватар только в своем профиле.');
        }

        if ($request->boolean('remove_avatar')) {
            if ($profileUser->avatar_path) {
                Storage::disk('public')->delete($profileUser->avatar_path);
            }
            $profileUser->update(['avatar_path' => null]);

            return back()->with('success', 'Аватар удален.');
        }

        $request->validate([
            'avatar' => ['required', 'image', 'max:3072'],
        ]);

        if ($profileUser->avatar_path) {
            Storage::disk('public')->delete($profileUser->avatar_path);
        }

        $path = $request->file('avatar')->store('avatars', 'public');
        $profileUser->update([
            'avatar_path' => $path,
            'last_seen_at' => now(),
        ]);

        return back()->with('success', 'Аватар профиля успешно обновлен!');
    }

    public function modal(Request $request, User $profileUser): JsonResponse
    {
        app(ProfileAnalyticsService::class)->recordView($profileUser, $this->currentUser(), $request, 'seller_modal');

        $profileUser->load([
            'lots' => fn ($q) => $q->where('stock_quantity', '>', 0)->latest()->limit(5),
            'receivedReviews' => fn ($q) => $q->with(['author', 'lot'])->latest()->limit(5),
        ]);

        $completedOrders = Order::where('seller_id', $profileUser->id)
            ->where('status', OrderStatus::Completed)
            ->count();

        $reviewsCount = $profileUser->receivedReviews()->count();

        return response()->json([
            'id' => $profileUser->id,
            'username' => $profileUser->username,
            'initial' => strtoupper(substr($profileUser->username, 0, 1)),
            'avatar_url' => $profileUser->avatarUrl(),
            'is_online' => $profileUser->isOnline(),
            'online_label' => $profileUser->isOnline() ? __('Онлайн') : __('Офлайн'),
            'rating' => number_format((float) $profileUser->rating, 2),
            'status_label' => $profileUser->status->label(),
            'registered_at' => $profileUser->created_at?->format('d.m.Y') ?? '2026',
            'completed_orders' => max($completedOrders, $reviewsCount),
            'reviews_count' => $reviewsCount,
            'active_lots_count' => $profileUser->lots()->where('stock_quantity', '>', 0)->count(),
            'profile_url' => route('users.show', $profileUser->id),
            'filter_seller_url' => route('home', ['seller' => $profileUser->username]),
            'reviews' => $profileUser->receivedReviews->map(fn ($rev): array => [
                'author' => $rev->author->username ?? 'Покупатель',
                'rating' => (int) $rev->rating,
                'lot_title' => $rev->lot->title ?? 'Лот',
                'comment' => $rev->comment,
                'date' => $rev->created_at?->format('d.m.Y') ?? '',
            ]),
        ]);
    }
}