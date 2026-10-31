<?php

namespace App\Enums;

enum TransactionStatus: string
{
    case PENDING = 'pending';
    case SUCCESS = 'success';
    case REJECTED = 'rejected';
}
