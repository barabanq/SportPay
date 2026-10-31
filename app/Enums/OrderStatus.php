<?php

declare(strict_types=1);

namespace App\Enums;

enum OrderStatus: string
{
    case Created = 'created';
    case Paid = 'paid';
    case Shipped = 'shipped';
    case Completed = 'completed';
    case Cancelled = 'cancelled';

    public function label(): string
    {
        return match ($this) {
            self::Created => 'СОЗДАН',
            self::Paid => 'В ХОЛДЕ (ОПЛАЧЕН)',
            self::Shipped => 'ПЕРЕДАН',
            self::Completed => 'ЗАВЕРШЕН',
            self::Cancelled => 'ОТМЕНЕН',
        };
    }

    public function badgeClasses(): string
    {
        return match ($this) {
            self::Completed => 'bg-emerald-500/20 text-emerald-300',
            self::Cancelled => 'bg-rose-500/20 text-rose-300',
            default => 'bg-amber-500/20 text-amber-300',
        };
    }

    public function isConfirmable(): bool
    {
        return in_array($this, [self::Paid, self::Shipped], true);
    }
}