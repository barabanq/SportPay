<?php

namespace Database\Seeders;

use App\Models\Lot;
use App\Models\Order;
use App\Models\Review;
use App\Models\User;
use App\Models\WalletTransaction;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $seller = User::create([
            'username' => 'Coach_Alex',
            'email' => 'seller@sportpay.local',
            'password' => Hash::make('password'),
            'phone' => '+79991112233',
            'wallet_balance' => 4500.00,
            'rating' => 5.00,
            'status' => 'active',
        ]);

        $buyer = User::create([
            'username' => 'Runner_Max',
            'email' => 'buyer@sportpay.local',
            'password' => Hash::make('password'),
            'phone' => '+79994445566',
            'wallet_balance' => 19000.00,
            'rating' => 5.00,
            'status' => 'active',
        ]);

        WalletTransaction::create([
            'user_id' => $buyer->id,
            'order_id' => null,
            'amount' => 25000.00,
            'type' => 'deposit',
            'balance_after' => 25000.00,
            'status' => 'success',
        ]);

        $lotsData = [
            ['title' => 'Индивидуальная программа гипертрофии (Зал, мезоцикл 8 недель)', 'type' => 'training_plan', 'price' => 3000],
            ['title' => 'Онлайн-разбор техники жима, тяги и приседа по видео', 'type' => 'coaching', 'price' => 1500],
            ['title' => 'План подготовки к полумарафону 21.1 км без травм', 'type' => 'training_plan', 'price' => 2500],
            ['title' => 'Слот на Забег.РФ (дистанция 10 км, переоформление)', 'type' => 'race_slot', 'price' => 4000],
            ['title' => 'Пояс для пауэрлифтинга SBD 13 мм (состояние нового)', 'type' => 'equipment', 'price' => 14000],
            ['title' => 'Ведение по питанию, КБЖУ и спортивным добавкам (1 месяц)', 'type' => 'coaching', 'price' => 5000],
        ];

        $createdLots = [];
        foreach ($lotsData as $item) {
            $createdLots[] = Lot::create([
                'seller_id' => $seller->id,
                'title' => $item['title'],
                'product_type' => $item['type'],
                'description' => 'Официальная сделка через гарант SportPay. Деньги резервируются на площадке и переводятся продавцу только после того, как вы проверите и подтвердите получение услуги или товара.',
                'price' => $item['price'],
                'stock_quantity' => 5,
                'status' => 'active',
            ]);
        }

        // 1 завершенный заказ (3000 руб) с отзывом
        $completedOrder = Order::create([
            'lot_id' => $createdLots[0]->id,
            'buyer_id' => $buyer->id,
            'seller_id' => $seller->id,
            'quantity' => 1,
            'unit_price' => 3000.00,
            'total_price' => 3000.00,
            'status' => 'completed',
        ]);

        WalletTransaction::create([
            'user_id' => $buyer->id,
            'order_id' => $completedOrder->id,
            'amount' => -3000.00,
            'type' => 'purchase',
            'balance_after' => 22000.00,
            'status' => 'success',
        ]);

        WalletTransaction::create([
            'user_id' => $seller->id,
            'order_id' => $completedOrder->id,
            'amount' => 3000.00,
            'type' => 'payout',
            'balance_after' => 3000.00,
            'status' => 'success',
        ]);

        Review::create([
            'order_id' => $completedOrder->id,
            'author_id' => $buyer->id,
            'seller_id' => $seller->id,
            'lot_id' => $createdLots[0]->id,
            'rating' => 5,
            'comment' => 'Отличный тренер! Программа расписана по подходам и весам, прогресс пошел со второй недели.',
        ]);

        // 1 активный заказ в холде (1500 руб — деньги списаны у покупателя, ждут подтверждения)
        $activeOrder = Order::create([
            'lot_id' => $createdLots[1]->id,
            'buyer_id' => $buyer->id,
            'seller_id' => $seller->id,
            'quantity' => 1,
            'unit_price' => 1500.00,
            'total_price' => 1500.00,
            'status' => 'paid',
        ]);

        WalletTransaction::create([
            'user_id' => $buyer->id,
            'order_id' => $activeOrder->id,
            'amount' => -1500.00,
            'type' => 'purchase',
            'balance_after' => 19000.00,
            'status' => 'success',
        ]);
    }
}
