<?php

declare(strict_types=1);

namespace App\Services;

use App\Enums\TransactionType;
use App\Models\User;
use App\Models\WalletTransaction;
use DomainException;
use Illuminate\Support\Facades\DB;

class WalletService
{
    public function deposit(User|int $userId, float $amount): WalletTransaction
    {
        if ($amount <= 0) {
            throw new DomainException('Сумма пополнения должна быть больше нуля.');
        }

        $targetUserId = $userId instanceof User ? $userId->id : $userId;

        return DB::transaction(function () use ($targetUserId, $userId, $amount): WalletTransaction {
            /** @var User $lockedUser */
            $lockedUser = User::whereKey($targetUserId)->lockForUpdate()->firstOrFail();

            if (! $lockedUser->status->canTrade()) {
                throw new DomainException('Ваш аккаунт заморожен или заблокирован.');
            }

            $newBalance = round((float) $lockedUser->wallet_balance + $amount, 2);
            $lockedUser->update(['wallet_balance' => $newBalance]);

            /** @var WalletTransaction $tx */
            $tx = WalletTransaction::create([
                'user_id' => $lockedUser->id,
                'order_id' => null,
                'type' => TransactionType::Deposit,
                'amount' => round($amount, 2),
                'balance_after' => $newBalance,
            ]);

            if ($userId instanceof User) {
                $userId->refresh();
            }

            return $tx;
        });
    }

    public function withdraw(User|int $userId, float $amount): WalletTransaction
    {
        if ($amount <= 0) {
            throw new DomainException('Сумма вывода должна быть больше нуля.');
        }

        $targetUserId = $userId instanceof User ? $userId->id : $userId;

        return DB::transaction(function () use ($targetUserId, $userId, $amount): WalletTransaction {
            /** @var User $lockedUser */
            $lockedUser = User::whereKey($targetUserId)->lockForUpdate()->firstOrFail();

            if (! $lockedUser->status->canTrade()) {
                throw new DomainException('Вывод средств недоступен для вашего статуса аккаунта.');
            }

            if ((float) $lockedUser->wallet_balance < $amount) {
                throw new DomainException('Недостаточно средств для вывода.');
            }

            $newBalance = round((float) $lockedUser->wallet_balance - $amount, 2);
            $lockedUser->update(['wallet_balance' => $newBalance]);

            /** @var WalletTransaction $tx */
            $tx = WalletTransaction::create([
                'user_id' => $lockedUser->id,
                'order_id' => null,
                'type' => TransactionType::Withdraw,
                'amount' => -round($amount, 2),
                'balance_after' => $newBalance,
            ]);

            if ($userId instanceof User) {
                $userId->refresh();
            }

            return $tx;
        });
    }
}
