<?php

declare(strict_types=1);

namespace App\Enums;

enum UserStatus: string
{
    case Active = 'active';
    case Frozen = 'frozen';
    case Banned = 'banned';
    case Unverified = 'unverified';

    public function label(): string
    {
        return match ($this) {
            self::Active => __('Активен'),
            self::Frozen => __('Заморожен'),
            self::Banned => __('Заблокирован'),
            self::Unverified => __('Не верифицирован'),
        };
    }

    public function canTrade(): bool
    {
        return $this === self::Active;
    }
}
