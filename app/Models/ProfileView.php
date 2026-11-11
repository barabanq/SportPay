<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProfileView extends Model
{
    protected $fillable = [
        'profile_user_id',
        'viewer_id',
        'source',
        'ip_hash',
    ];

    public function profileUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'profile_user_id');
    }

    public function viewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'viewer_id');
    }

    public static function sourceLabel(string $source): string
    {
        return match ($source) {
            'seller_modal' => 'Быстрый просмотр с витрины (Попап)',
            'lot_page'     => 'Переход из карточки лота',
            'order_page'   => 'Переход со страницы сделки',
            'catalog'      => 'Фильтр по продавцу в каталоге',
            default        => 'Прямой вход в профиль',
        };
    }
}