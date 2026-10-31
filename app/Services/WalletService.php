<?php

declare(strict_types=1);

namespace App\Services;

use App\Enums\TransactionStatus;
use App\Enums\TransactionType;
use App\Models\User;
use App\Models\WalletTransaction;
use DomainException;
use Illuminate\Support\Facades\DB;

class WalletService
{
    public function deposit(int $userId, float $amount, ?int $orderId = null): WalletTransaction
    {
        if ($amount <= 0) {
            throw new DomainException('Сумма пополнения должна быть больше нуля.');
        }

        return DB::transaction(function () use ($userId, $amount, $orderId): WalletTransaction {
            $user = User::where('id', $userId)->lockForUpdate()->firstOrFail();

            return $this->applyBalanceChange($user, $amount, TransactionType::Deposit, $orderId);
        });
    }

    public function withdraw(int $userId, float $amount): WalletTransaction
    {
        if ($amount <= 0) {
            throw new DomainException('Сумма вывода должна быть больше нуля.');
        }

        return DB::transaction(function () use ($userId, $amount): WalletTransaction {
            $user = User::where('id', $userId)->lockForUpdate()->firstOrFail();

            if ((float) $user->wallet_balance < $amount) {
                throw new DomainException('Недостаточно средств на балансе для вывода.');
            }

            return $this->applyBalanceChange($user, -$amount, TransactionType::Withdraw);
        });
    }

    /**
     * Единая точка изменения баланса и записи аудиторского следа (DRY).
     * Вызывается внутри активной БД-транзакции с заблокированным User.
     */
    public function applyBalanceChange(
        User $lockedUser,
        float $signedAmount,
        TransactionType $type,
        ?int $orderId = null
    ): WalletTransaction {
        $newBalance = round((float) $lockedUser->wallet_balance + $signedAmount, 2);

        if ($newBalance < 0) {
            throw new DomainException('Недостаточно средств на кошельке.');
        }

        $lockedUser->update(['wallet_balance' => $newBalance]);

        return WalletTransaction::create([
            'user_id' => $lockedUser->id,
            'order_id' => $orderId,
            'amount' => $signedAmount,
            'type' => $type,
            'balance_after' => $newBalance,
            'status' => TransactionStatus::Success,
        ]);
    }
}