<?php

declare(strict_types=1);

namespace App\Enums;

enum OrderStatus: string
{
    case Created = 'created';
    case Paid = 'paid';
    case Shipped = 'shipped';
    case Disputed = 'disputed';
    case Completed = 'completed';
    case Cancelled = 'cancelled';

    public function label(): string
    {
        return match ($this) {
            self::Created => 'СОЗДАН',
            self::Paid => 'В ХОЛДЕ (ОПЛАЧЕН)',
            self::Shipped => 'ПЕРЕДАН',
            self::Disputed => 'АРБИТРАЖ (СПОР)',
            self::Completed => 'ЗАВЕРШЕН',
            self::Cancelled => 'ОТМЕНЕН (ВОЗВРАТ)',
        };
    }

    public function badgeClasses(): string
    {
        return match ($this) {
            self::Completed => 'bg-emerald-500/20 text-emerald-300',
            self::Cancelled => 'bg-slate-700 text-slate-300',
            self::Disputed => 'bg-rose-500/20 text-rose-300 border border-rose-500/40',
            default => 'bg-amber-500/20 text-amber-300',
        };
    }

    public function isConfirmable(): bool
    {
        return in_array($this, [self::Paid, self::Shipped, self::Disputed], true);
    }

    public function isDisputable(): bool
    {
        return in_array($this, [self::Paid, self::Shipped], true);
    }
}