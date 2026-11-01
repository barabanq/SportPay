<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Enums\TransactionStatus;
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
        $this->walletService = $this->app->make(WalletService::class);
    }

    public function test_user_can_deposit_funds_and_audit_trail_is_recorded(): void
    {
        // Arrange
        $user = $this->createUser(balance: 1000.00);

        // Act
        $transaction = $this->walletService->deposit($user->id, 2500.50);

        // Assert
        $this->assertSame(3500.50, (float) $user->fresh()->wallet_balance);
        $this->assertSame(TransactionType::Deposit, $transaction->type);
        $this->assertSame(TransactionStatus::Success, $transaction->status);
        $this->assertSame(2500.50, (float) $transaction->amount);
        $this->assertSame(3500.50, (float) $transaction->balance_after);
    }

    public function test_user_can_withdraw_funds_when_balance_is_sufficient(): void
    {
        // Arrange
        $user = $this->createUser(balance: 5000.00);

        // Act
        $transaction = $this->walletService->withdraw($user->id, 1800.00);

        // Assert
        $this->assertSame(3200.00, (float) $user->fresh()->wallet_balance);
        $this->assertSame(TransactionType::Withdraw, $transaction->type);
        $this->assertSame(-1800.00, (float) $transaction->amount);
        $this->assertSame(3200.00, (float) $transaction->balance_after);
    }

    public function test_withdraw_throws_exception_on_insufficient_funds_and_keeps_balance_intact(): void
    {
        // Arrange
        $user = $this->createUser(balance: 500.00);

        // Assert & Act
        $this->expectException(DomainException::class);
        $this->expectExceptionMessage('Недостаточно средств на балансе для вывода.');

        try {
            $this->walletService->withdraw($user->id, 1000.00);
        } finally {
            $this->assertSame(500.00, (float) $user->fresh()->wallet_balance);
            $this->assertDatabaseCount('wallet_transactions', 0);
        }
    }

    private function createUser(float $balance): User
    {
        return User::create([
            'username' => 'user_' . uniqid(),
            'email' => uniqid() . '@sportpay.local',
            'password' => 'secret123',
            'wallet_balance' => $balance,
            'rating' => 5.00,
            'status' => UserStatus::Active,
        ]);
    }
}