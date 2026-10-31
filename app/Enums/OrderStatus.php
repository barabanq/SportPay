<?php

namespace App\Enums;

enum OrderStatus: string
{
    case CREATED = 'created';
    case PAID = 'paid';
    case SHIPPED = 'shipped';
    case COMPLETED = 'completed';
    case CANCELLED = 'cancelled';
}
