<?php

namespace App\Services;

use App\Models\User;
use App\Models\WalletTransaction;
use DomainException;
use Illuminate\Support\Facades\DB;

class WalletService
{
    public function deposit(int $userId, float $amount): WalletTransaction
    {
        if ($amount <= 0) {
            throw new DomainException('Сумма пополнения должна быть больше нуля.');
        }

        return DB::transaction(function () use ($userId, $amount) {
            $user = User::where('id', $userId)->lockForUpdate()->firstOrFail();
            $user->wallet_balance += $amount;
            $user->save();

            return WalletTransaction::create([
                'user_id' => $user->id,
                'order_id' => null,
                'amount' => $amount,
                'type' => 'deposit',
                'balance_after' => $user->wallet_balance,
                'status' => 'success',
            ]);
        });
    }

    public function withdraw(int $userId, float $amount): WalletTransaction
    {
        if ($amount <= 0) {
            throw new DomainException('Сумма вывода должна быть больше нуля.');
        }

        return DB::transaction(function () use ($userId, $amount) {
            $user = User::where('id', $userId)->lockForUpdate()->firstOrFail();

            if ($user->wallet_balance < $amount) {
                throw new DomainException('Недостаточно средств на балансе для вывода.');
            }

            $user->wallet_balance -= $amount;
            $user->save();

            return WalletTransaction::create([
                'user_id' => $user->id,
                'order_id' => null,
                'amount' => -$amount,
                'type' => 'withdraw',
                'balance_after' => $user->wallet_balance,
                'status' => 'success',
            ]);
        });
    }
}