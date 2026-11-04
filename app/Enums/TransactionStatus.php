<?php

declare(strict_types=1);

namespace App\Enums;

enum TransactionStatus: string
{
    case Pending = 'pending';
    case Success = 'success';
    case Rejected = 'rejected';

    public function label(): string
    {
        return match ($this) {
            self::Pending => 'В обработке',
            self::Success => 'Успешно',
            self::Rejected => 'Отклонено',
        };
    }
}
