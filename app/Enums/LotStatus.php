<?php

declare(strict_types=1);

namespace App\Enums;

enum LotStatus: string
{
    case Draft = 'draft';
    case Active = 'active';
    case Reserved = 'reserved';
    case Sold = 'sold';
    case Archived = 'archived';

    public function label(): string
    {
        return match ($this) {
            self::Draft => __('Черновик'),
            self::Active => __('Активен'),
            self::Reserved => __('Забронирован'),
            self::Sold => __('Продан'),
            self::Archived => __('В архиве'),
        };
    }
}
