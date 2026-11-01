<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Enums\LotStatus;
use App\Enums\UserStatus;
use App\Models\Lot;
use App\Models\User;
use App\Services\EscrowService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LotFilterTest extends TestCase
{
    use RefreshDatabase;

    public function test_scope_filter_returns_only_active_in_stock_lots_matching_category_and_search(): void
    {
        $seller = $this->createUser('Coach_Pro', rating: 5.00);

        // Целевой лот
        $targetLot = $this->createLot($seller, 'Подготовка к марафону 42 км', 'training_plan', 3000, stock: 5);

        // Другая категория
        $this->createLot($seller, 'Слот на забег в Москве', 'race_slot', 1500, stock: 2);

        // Нет в наличии (stock = 0)
        $this->createLot($seller, 'Марафон за 3 часа', 'training_plan', 2500, stock: 0);

        // В архиве
        $this->createLot($seller, 'Марафон архивный', 'training_plan', 2000, stock: 5, status: LotStatus::Archived);

        $results = Lot::query()
            ->filter(type: 'training_plan', search: 'марафон')
            ->get();

        $this->assertCount(1, $results);
        $this->assertTrue($results->first()->is($targetLot));
    }

    public function test_scope_sort_by_orders_lots_by_price_and_seller_rating(): void
    {
        $lowRatedSeller = $this->createUser('Coach_Novice', rating: 3.20);
        $topRatedSeller = $this->createUser('Coach_Legend', rating: 4.95);

        $expensiveLot = $this->createLot($lowRatedSeller, 'Дорогой коучинг', 'coaching', 8000);
        $cheapLot = $this->createLot($topRatedSeller, 'Бюджетный разбор техники', 'coaching', 1200);

        // 1. Проверка price_asc (сначала дешевые)
        $byPriceAsc = Lot::query()->filter(null, null)->sortBy('price_asc')->pluck('id')->all();
        $this->assertSame([$cheapLot->id, $expensiveLot->id], $byPriceAsc);

        // 2. Проверка price_desc (сначала дорогие)
        $byPriceDesc = Lot::query()->filter(null, null)->sortBy('price_desc')->pluck('id')->all();
        $this->assertSame([$expensiveLot->id, $cheapLot->id], $byPriceDesc);

        // 3. Проверка rating_desc (сначала лоты продавца с рейтингом 4.95)
        $byRatingDesc = Lot::query()->filter(null, null)->sortBy('rating_desc')->pluck('id')->all();
        $this->assertSame([$cheapLot->id, $expensiveLot->id], $byRatingDesc);
    }

    public function test_buyer_can_rate_seller_with_1_or_2_stars_and_lower_seller_rating(): void
    {
        $seller = $this->createUser('Coach_Bad', rating: 5.00);
        $buyer = $this->createUser('Runner_Angry', rating: 5.00, balance: 5000.00);
        $lot = $this->createLot($seller, 'Консультация по питанию', 'coaching', 1000);

        /** @var EscrowService $escrow */
        $escrow = $this->app->make(EscrowService::class);

        $order = $escrow->purchaseLot($buyer->id, $lot->id);
        $escrow->confirmOrder($order->id, $buyer->id, rating: 1, comment: 'Опоздал на созвон на 40 минут');

        $this->assertSame(1.00, (float) $seller->fresh()->rating);
        $this->assertDatabaseHas('reviews', [
            'order_id' => $order->id,
            'rating' => 1,
        ]);
    }

    private function createUser(string $username, float $rating, float $balance = 1000.00): User
    {
        return User::create([
            'username' => $username . '_' . uniqid(),
            'email' => uniqid() . '@sportpay.local',
            'password' => 'secret123',
            'wallet_balance' => $balance,
            'rating' => $rating,
            'status' => UserStatus::Active,
        ]);
    }

    private function createLot(
        User $seller,
        string $title,
        string $type,
        float $price,
        int $stock = 5,
        LotStatus $status = LotStatus::Active
    ): Lot {
        return Lot::create([
            'seller_id' => $seller->id,
            'title' => $title,
            'product_type' => $type,
            'description' => 'Описание: ' . $title,
            'price' => $price,
            'stock_quantity' => $stock,
            'status' => $status,
        ]);
    }
}