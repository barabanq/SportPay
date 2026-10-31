<?php

namespace App\Enums;

enum TransactionType: string
{
    case DEPOSIT = 'deposit';
    case WITHDRAW = 'withdraw';
    case PURCHASE = 'purchase';
    case PAYOUT = 'payout';
}
