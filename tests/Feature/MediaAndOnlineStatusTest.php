<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Enums\LotStatus;
use App\Enums\UserStatus;
use App\Models\Lot;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class MediaAndOnlineStatusTest extends TestCase
{
    use RefreshDatabase;

    private function createUser(string $username, ?\DateTimeInterface $lastSeenAt = null): User
    {
        return User::create([
            'username' => $username,
            'email' => strtolower($username) . '@sportpay.local',
            'password' => 'secret123',
            'wallet_balance' => 1000.0,
            'rating' => 5.00,
            'status' => UserStatus::Active,
            'last_seen_at' => $lastSeenAt,
        ]);
    }

    private function fakePngFile(string $filename): UploadedFile
    {
        // Минимальный валидный 1x1 PNG в бинарном виде (проходит проверку MIME image/png без php-gd)
        $pngBinary = base64_decode(
            'iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mNk+M9QDwADhgGAWjR9awAAAABJRU5ErkJggg=='
        );

        return UploadedFile::fake()->createWithContent($filename, $pngBinary);
    }

    public function test_seller_can_upload_lot_photo_and_user_can_upload_avatar(): void
    {
        Storage::fake('public');

        $seller = $this->createUser('Photo_Seller', now());

        // 1. Загрузка фото лота
        $lotImage = $this->fakePngFile('garmin-watch.png');

        $this->actingAs($seller)->post(route('lots.store'), [
            'title' => 'Часы Garmin Forerunner 965',
            'product_type' => 'equipment',
            'description' => 'Новые с коробкой',
            'image' => $lotImage,
            'price' => 45000,
            'stock_quantity' => 2,
        ])->assertRedirect(route('home'));

        $lot = Lot::firstOrFail();
        $this->assertNotNull($lot->image_path);
        Storage::disk('public')->assertExists($lot->image_path);

        // 2. Загрузка аватарки пользователя
        $avatarFile = $this->fakePngFile('avatar.png');

        $this->actingAs($seller)->post(route('users.avatar.update', $seller), [
            'avatar' => $avatarFile,
        ])->assertRedirect();

        $seller->refresh();
        $this->assertNotNull($seller->avatar_path);
        Storage::disk('public')->assertExists($seller->avatar_path);
    }

    public function test_marketplace_can_filter_and_sort_by_online_sellers(): void
    {
        $offlineSeller = $this->createUser('Offline_Coach', now()->subMinutes(30));
        $onlineSeller = $this->createUser('Online_Runner', now());
        $viewer = $this->createUser('Buyer_Viewer', now());

        Lot::create([
            'seller_id' => $offlineSeller->id,
            'title' => 'Лот от офлайн-тренера',
            'product_type' => 'coaching',
            'price' => 1500,
            'stock_quantity' => 5,
            'status' => LotStatus::Active,
        ]);

        Lot::create([
            'seller_id' => $onlineSeller->id,
            'title' => 'Лот от онлайн-продавца',
            'product_type' => 'training_plan',
            'price' => 1500,
            'stock_quantity' => 5,
            'status' => LotStatus::Active,
        ]);

        // Проверка фильтра "Только онлайн" (online_only=1)
        $filterResponse = $this->actingAs($viewer)->get(route('home', [
            'online_only' => 1,
        ]));

        $filterResponse->assertOk();
        $filterResponse->assertSee('Лот от онлайн-продавца');
        $filterResponse->assertDontSee('Лот от офлайн-тренера');

        // Проверка сортировки "Сначала продавцы онлайн" (sort=online_first)
        $sortedLots = Lot::query()->sortBy('online_first')->get();
        $this->assertSame('Лот от онлайн-продавца', $sortedLots->first()->title);
        $this->assertSame('Лот от офлайн-тренера', $sortedLots->last()->title);
    }
}
