<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Enums\LotStatus;
use App\Enums\OrderStatus;
use App\Enums\UserStatus;
use App\Models\Lot;
use App\Models\Order;
use App\Models\SupportTicket;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class AuthAndSupportTest extends TestCase
{
    use RefreshDatabase;

    private function createActiveUser(string $username, float $balance = 0.0, ?string $email = null): User
    {
        return User::create([
            'username' => $username,
            'email' => $email ?? (strtolower($username) . '@sportpay.local'),
            'password' => Hash::make('secret123'),
            'wallet_balance' => $balance,
            'rating' => 5.00,
            'status' => UserStatus::Active,
        ]);
    }

    public function test_user_can_register_and_receives_welcome_bonus(): void
    {
        $response = $this->post(route('register.submit'), [
            'username' => 'New_Athlete',
            'email' => 'athlete@sportpay.local',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ]);

        $response->assertRedirect(route('home'));
        $this->assertAuthenticated();

        $user = User::where('username', 'New_Athlete')->firstOrFail();
        $this->assertEquals(1000.00, (float) $user->wallet_balance);
        $this->assertSame(UserStatus::Active, $user->status);
    }

    public function test_user_can_login_with_username_or_email_and_logout(): void
    {
        $user = $this->createActiveUser('Coach_Login', 500.0, 'coach@sportpay.local');

        // Выходим из дефолтной сессии
        $this->post(route('logout'))->assertRedirect(route('login'));
        $this->assertGuest();

        // Вход по username
        $loginByUsername = $this->post(route('login.submit'), [
            'login' => 'Coach_Login',
            'password' => 'secret123',
        ]);
        $loginByUsername->assertRedirect(route('home'));
        $this->assertAuthenticatedAs($user);

        // Выход и повторный вход по email
        $this->post(route('logout'));
        $loginByEmail = $this->post(route('login.submit'), [
            'login' => 'coach@sportpay.local',
            'password' => 'secret123',
        ]);
        $loginByEmail->assertRedirect(route('home'));
        $this->assertAuthenticatedAs($user);
    }

    public function test_support_rules_and_help_center_pages_render_correctly(): void
    {
        $user = $this->createActiveUser('Reader_User');

        $this->actingAs($user)
            ->get(route('support.rules'))
            ->assertOk()
            ->assertSee('Правила биржи SportPay');

        $this->actingAs($user)
            ->get(route('support.help'))
            ->assertOk()
            ->assertSee('Центр помощи SportPay');
    }

    public function test_user_can_create_support_ticket_linked_to_order(): void
    {
        $seller = $this->createActiveUser('Ticket_Seller');
        $buyer = $this->createActiveUser('Ticket_Buyer', 5000.0);

        $lot = Lot::create([
            'seller_id' => $seller->id,
            'title' => 'Велотренажер Tacx',
            'product_type' => 'equipment',
            'description' => 'В отличном состоянии',
            'price' => 2000,
            'stock_quantity' => 1,
            'status' => LotStatus::Active,
        ]);

        $order = Order::create([
            'lot_id' => $lot->id,
            'buyer_id' => $buyer->id,
            'seller_id' => $seller->id,
            'quantity' => 1,
            'unit_price' => 2000,
            'total_price' => 2000,
            'status' => OrderStatus::Paid,
        ]);

        $response = $this->actingAs($buyer)->post(route('support.tickets.store'), [
            'category' => 'order_issue',
            'order_id' => $order->id,
            'subject' => 'Вопрос по доставке тренажера',
            'message' => 'Продавец еще не скинул трек-номер отправления.',
        ]);

        $response->assertRedirect(route('support.tickets.index'));

        $ticket = SupportTicket::firstOrFail();
        $this->assertSame($buyer->id, $ticket->user_id);
        $this->assertSame($order->id, $ticket->order_id);
        $this->assertSame('order_issue', $ticket->category);
        $this->assertSame('open', $ticket->status);
    }

    public function test_only_arbiter_can_reply_to_support_ticket(): void
    {
        $user = $this->createActiveUser('Regular_User');
        $arbiter = $this->createActiveUser('Arbiter_Judge', 0.0, 'arbiter@sportpay.local');

        $ticket = SupportTicket::create([
            'user_id' => $user->id,
            'category' => 'payment',
            'subject' => 'Не пришел вывод средств',
            'message' => 'Ожидаю вывод уже более двух часов, проверьте транзакцию.',
            'status' => 'open',
        ]);

        // Обычный юзер получает 403 при попытке ответить от лица поддержки
        $this->actingAs($user)->post(route('support.tickets.reply', $ticket), [
            'staff_reply' => 'Пытаюсь ответить сам себе',
            'status' => 'answered',
        ])->assertForbidden();

        // Официальный арбитр успешно отвечает и меняет статус тикета
        $this->actingAs($arbiter)->post(route('support.tickets.reply', $ticket), [
            'staff_reply' => 'Проверили шлюз, средства успешно отправлены на вашу карту.',
            'status' => 'answered',
        ])->assertRedirect();

        $ticket->refresh();
        $this->assertSame('answered', $ticket->status);
        $this->assertSame('Проверили шлюз, средства успешно отправлены на вашу карту.', $ticket->staff_reply);
    }
}
