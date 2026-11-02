<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Enums\LotStatus;
use App\Enums\OrderStatus;
use App\Enums\UserStatus;
use App\Models\Lot;
use App\Models\Order;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LotAndPurchaseFlowTest extends TestCase
{
    use RefreshDatabase;

    private function createActiveUser(string $username, float $balance = 0.0): User
    {
        return User::create([
            'username' => $username,
            'email' => strtolower($username) . '@sportpay.local',
            'password' => 'secret123',
            'wallet_balance' => $balance,
            'rating' => 5.00,
            'status' => UserStatus::Active,
        ]);
    }

    public function test_seller_can_create_and_update_lot_without_description(): void
    {
        $seller = $this->createActiveUser('Coach_NoDesc');

        $response = $this->actingAs($seller)->post(route('lots.store'), [
            'title' => 'Индивидуальный план подготовки',
            'product_type' => 'training_plan',
            'description' => '',
            'price' => 1500,
            'stock_quantity' => 10,
        ]);

        $response->assertRedirect(route('home'));

        $lot = Lot::firstOrFail();
        $this->assertSame('Индивидуальный план подготовки', $lot->title);
        $this->assertNull($lot->description);
        $this->assertSame(10, $lot->stock_quantity);

        $updateResponse = $this->actingAs($seller)->put(route('lots.update', $lot), [
            'title' => 'Обновленный план без описания',
            'product_type' => 'coaching',
            'description' => null,
            'price' => 2000,
            'stock_quantity' => 7,
        ]);

        $updateResponse->assertRedirect(route('home'));
        $lot->refresh();

        $this->assertSame('Обновленный план без описания', $lot->title);
        $this->assertNull($lot->description);
        $this->assertSame(7, $lot->stock_quantity);
    }

    public function test_lot_show_page_renders_correctly_even_without_description(): void
    {
        $seller = $this->createActiveUser('Seller_Show');

        $lot = Lot::create([
            'seller_id' => $seller->id,
            'title' => 'Слот на марафон 42.2 км',
            'product_type' => 'race_slot',
            'description' => null,
            'price' => 3500,
            'stock_quantity' => 4,
            'status' => LotStatus::Active,
        ]);

        $response = $this->actingAs($seller)->get(route('lots.show', $lot));

        $response->assertOk();
        $response->assertSee('Слот на марафон 42.2 км');
    }

    public function test_buyer_can_purchase_multiple_quantities_and_cancel_restores_full_stock(): void
    {
        $seller = $this->createActiveUser('Equip_Seller', 0.0);
        $buyer = $this->createActiveUser('Equip_Buyer', 10000.0);

        $lot = Lot::create([
            'seller_id' => $seller->id,
            'title' => 'Изотонический гель (упаковка)',
            'product_type' => 'equipment',
            'description' => null,
            'price' => 1200,
            'stock_quantity' => 5,
            'status' => LotStatus::Active,
        ]);

        $response = $this->actingAs($buyer)->post(route('lots.purchase', $lot), [
            'quantity' => 3,
        ]);

        $order = Order::firstOrFail();
        $response->assertRedirect(route('orders.show', $order));

        $this->assertSame(3, $order->quantity);
        $this->assertEquals(1200.00, (float) $order->unit_price);
        $this->assertEquals(3600.00, (float) $order->total_price);
        $this->assertSame(OrderStatus::Paid, $order->status);

        $this->assertSame(2, $lot->fresh()->stock_quantity);
        $this->assertEquals(6400.00, (float) $buyer->fresh()->wallet_balance);

        // Продавец отменяет заказ — все 3 шт. и 3600 ₽ должны вернуться
        $this->actingAs($seller)->post(route('orders.cancel', $order));

        $this->assertSame(OrderStatus::Cancelled, $order->fresh()->status);
        $this->assertSame(5, $lot->fresh()->stock_quantity);
        $this->assertEquals(10000.00, (float) $buyer->fresh()->wallet_balance);
    }

    public function test_buyer_cannot_purchase_more_than_available_stock(): void
    {
        $seller = $this->createActiveUser('Stock_Seller', 0.0);
        $buyer = $this->createActiveUser('Stock_Buyer', 50000.0);

        $lot = Lot::create([
            'seller_id' => $seller->id,
            'title' => 'Редкий велокомпьютер',
            'product_type' => 'equipment',
            'description' => 'В наличии всего 2 штуки',
            'price' => 5000,
            'stock_quantity' => 2,
            'status' => LotStatus::Active,
        ]);

        $response = $this->actingAs($buyer)
            ->from(route('lots.show', $lot))
            ->post(route('lots.purchase', $lot), [
                'quantity' => 5,
            ]);

        $response->assertRedirect(route('lots.show', $lot));
        $response->assertSessionHas('error');

        $this->assertDatabaseCount('orders', 0);
        $this->assertSame(2, $lot->fresh()->stock_quantity);
        $this->assertEquals(50000.00, (float) $buyer->fresh()->wallet_balance);
    }
}
