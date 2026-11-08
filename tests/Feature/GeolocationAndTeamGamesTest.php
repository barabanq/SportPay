<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Enums\UserStatus;
use App\Models\Lot;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class GeolocationAndTeamGamesTest extends TestCase
{
    use RefreshDatabase;

    public function test_organizer_can_create_team_game_lot_with_custom_address_and_coordinates(): void
    {
        $organizer = User::create([
            'username' => 'Volley_Organizer',
            'email' => 'volley@sportpay.local',
            'password' => 'secret123',
            'wallet_balance' => 1000,
            'rating' => 5.00,
            'status' => UserStatus::Active,
        ]);

        $response = $this->actingAs($organizer)->post(route('lots.store'), [
            'title' => 'Волейбол 6х6 уровень Лайт+, 2 часа',
            'product_type' => 'team_game',
            'description' => 'Ищем 4 человек в команду, зал с паркетом и душевыми',
            'location_name' => 'Казань, СК Олимпиец, Горьковское шоссе 160',
            'latitude' => 55.8294120,
            'longitude' => 48.9765430,
            'price' => 450,
            'stock_quantity' => 4,
        ]);

        $response->assertRedirect(route('home'));

        $lot = Lot::firstOrFail();
        $this->assertSame('team_game', $lot->product_type);
        $this->assertSame('Казань, СК Олимпиец, Горьковское шоссе 160', $lot->location_name);
        $this->assertEqualsWithDelta(55.8294120, (float) $lot->latitude, 0.0001);
        $this->assertEqualsWithDelta(48.9765430, (float) $lot->longitude, 0.0001);
        $this->assertTrue($lot->hasLocation());

        // Проверяем, что произвольный адрес виден на главной витрине и в карточке лота
        $this->actingAs($organizer)->get(route('home'))
            ->assertOk()
            ->assertSee('Казань, СК Олимпиец, Горьковское шоссе 160');

        $this->actingAs($organizer)->get(route('lots.show', $lot))
            ->assertOk()
            ->assertSee('Казань, СК Олимпиец, Горьковское шоссе 160')
            ->assertSee('lot-show-map');
    }

    public function test_purchased_team_game_order_displays_exact_location_and_interactive_map(): void
    {
        $organizer = User::create([
            'username' => 'Football_Host',
            'email' => 'football@sportpay.local',
            'password' => 'secret123',
            'wallet_balance' => 500,
            'rating' => 5.00,
            'status' => UserStatus::Active,
        ]);

        $player = User::create([
            'username' => 'Player_One',
            'email' => 'player@sportpay.local',
            'password' => 'secret123',
            'wallet_balance' => 3000,
            'rating' => 5.00,
            'status' => UserStatus::Active,
        ]);

        $lot = Lot::create([
            'seller_id' => $organizer->id,
            'title' => 'Футбол 5х5 в манеже',
            'product_type' => 'team_game',
            'description' => 'Играем 90 минут, манишки есть',
            'location_name' => 'Нижний Новгород, Манеж Красная Этна',
            'latitude' => 56.2912340,
            'longitude' => 43.9123450,
            'price' => 600,
            'stock_quantity' => 10,
        ]);

        $purchaseResponse = $this->actingAs($player)->post(route('lots.purchase', $lot), [
            'quantity' => 2,
        ]);

        $order = $lot->orders()->firstOrFail();
        $purchaseResponse->assertRedirect(route('orders.show', $order));

        // Внутри заказа должны отображаться адрес манежа, координаты и карта order-location-map
        $this->actingAs($player)->get(route('orders.show', $order))
            ->assertOk()
            ->assertSee('Нижний Новгород, Манеж Красная Этна')
            ->assertSee('56.29123')
            ->assertSee('43.91235')
            ->assertSee('order-location-map');
    }

    public function test_organizer_can_update_lot_location_to_any_custom_address(): void
    {
        $seller = User::create([
            'username' => 'Coach_Updater',
            'email' => 'updater@sportpay.local',
            'password' => 'secret123',
            'wallet_balance' => 1000,
            'rating' => 5.00,
            'status' => UserStatus::Active,
        ]);

        $lot = Lot::create([
            'seller_id' => $seller->id,
            'title' => 'Вечерний волейбол',
            'product_type' => 'team_game',
            'location_name' => 'Старый зал',
            'latitude' => 55.7000000,
            'longitude' => 37.5000000,
            'price' => 500,
            'stock_quantity' => 6,
        ]);

        $this->actingAs($seller)->put(route('lots.update', $lot), [
            'title' => 'Вечерний волейбол (новый зал)',
            'product_type' => 'team_game',
            'description' => 'Перенесли в другой ФОК',
            'location_name' => 'Сочи, Сириус Арена, Олимпийский проспект 15',
            'latitude' => 43.4025110,
            'longitude' => 39.9541220,
            'price' => 550,
            'stock_quantity' => 8,
        ])->assertRedirect(route('home'));

        $lot->refresh();
        $this->assertSame('Сочи, Сириус Арена, Олимпийский проспект 15', $lot->location_name);
        $this->assertEqualsWithDelta(43.4025110, (float) $lot->latitude, 0.0001);
        $this->assertEqualsWithDelta(39.9541220, (float) $lot->longitude, 0.0001);
    }
}
