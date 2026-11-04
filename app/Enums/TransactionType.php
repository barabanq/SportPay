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
            self::Deposit => __('ПОПОЛНЕНИЕ'),
            self::Withdraw => __('ВЫВОД'),
            self::Purchase => __('ПОКУПКА (ХОЛД)'),
            self::Payout => __('ВЫПЛАТА С ПРОДАЖИ'),
        };
    }
}
