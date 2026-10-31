<?php

declare(strict_types=1);

namespace App\Enums;

enum TransactionType: string
{
    case Deposit = 'deposit';
    case Withdraw = 'withdraw';
    case Purchase = 'purchase';
    case Payout = 'payout';

    public function label(): string
    {
        return match ($this) {
            self::Deposit => 'ПОПОЛНЕНИЕ',
            self::Withdraw => 'ВЫВОД',
            self::Purchase => 'ПОКУПКА (ХОЛД)',
            self::Payout => 'ВЫПЛАТА С ПРОДАЖИ',
        };
    }
}