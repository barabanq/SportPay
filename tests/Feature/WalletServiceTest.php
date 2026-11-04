<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Enums\TransactionType;
use App\Enums\UserStatus;
use App\Models\User;
use App\Services\WalletService;
use DomainException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class WalletServiceTest extends TestCase
{
    use RefreshDatabase;

    private WalletService $walletService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->walletService = app(WalletService::class);
    }

    private function createUser(float $balance = 0.0, UserStatus $status = UserStatus::Active): User
    {
        return User::create([
            'username' => 'Wallet_Tester_'.uniqid(),
            'email' => uniqid('wallet_').'@sportpay.local',
            'password' => 'secret123',
            'wallet_balance' => $balance,
            'rating' => 5.00,
            'status' => $status,
        ]);
    }

    public function test_deposit_increases_balance_and_creates_audit_transaction(): void
    {
        $user = $this->createUser(1000.00);

        $tx = $this->walletService->deposit($user, 2500.50);

        $this->assertEquals(3500.50, (float) $user->fresh()->wallet_balance);
        $this->assertSame(TransactionType::Deposit, $tx->type);
        $this->assertEquals(2500.50, (float) $tx->amount);
        $this->assertEquals(3500.50, (float) $tx->balance_after);
    }

    public function test_withdraw_decreases_balance_and_records_negative_transaction(): void
    {
        $user = $this->createUser(5000.00);

        $tx = $this->walletService->withdraw($user, 1800.00);

        $this->assertEquals(3200.00, (float) $user->fresh()->wallet_balance);
        $this->assertSame(TransactionType::Withdraw, $tx->type);
        $this->assertEquals(-1800.00, (float) $tx->amount);
        $this->assertEquals(3200.00, (float) $tx->balance_after);
    }

    public function test_withdraw_throws_exception_when_balance_is_insufficient(): void
    {
        $user = $this->createUser(500.00);

        $this->expectException(DomainException::class);
        $this->walletService->withdraw($user, 1000.00);
    }

    public function test_banned_user_cannot_deposit_or_withdraw(): void
    {
        $user = $this->createUser(1000.00, UserStatus::Banned);

        $this->expectException(DomainException::class);
        $this->walletService->withdraw($user, 200.00);
    }
}
