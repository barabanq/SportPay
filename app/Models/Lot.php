<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\LotStatus;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Lot extends Model
{
    use HasFactory;

    protected $fillable = [
        'seller_id',
        'title',
        'product_type',
        'description',
        'auto_delivery_text',
        'price',
        'stock_quantity',
        'status',
    ];

    protected $hidden = [
        'auto_delivery_text',
    ];

    public function hasAutoDelivery(): bool
    {
        return filled($this->auto_delivery_text);
    }

    protected function casts(): array
    {
        return [
            'price' => 'decimal:2',
            'stock_quantity' => 'integer',
            'status' => LotStatus::class,
        ];
    }

    public function seller(): BelongsTo
    {
        return $this->belongsTo(User::class, 'seller_id');
    }

    public function orders(): HasMany
    {
        return $this->hasMany(Order::class, 'lot_id');
    }

    public function reviews(): HasMany
    {
        return $this->hasMany(Review::class, 'lot_id');
    }

    /**
     * Фильтрация активных лотов по категории и поисковой строке
     */
    public function scopeFilter(Builder $query, ?string $type, ?string $search): Builder
    {
        return $query
            ->where('lots.stock_quantity', '>', 0)
            ->where('lots.status', LotStatus::Active)
            ->when($type, fn (Builder $q) => $q->where('lots.product_type', $type))
            ->when($search, function (Builder $q) use ($search): void {
                $term = '%' . trim($search) . '%';
                $q->where(function (Builder $sub) use ($term): void {
                    $sub->where('lots.title', 'like', $term)
                        ->orWhere('lots.description', 'like', $term);
                });
            });
    }

    /**
     * Безопасная сортировка по цене, рейтингу продавца или новизне
     */
    public function scopeSortBy(Builder $query, ?string $sort): Builder
    {
        return match ($sort) {
            'price_asc' => $query->orderBy('lots.price', 'asc'),
            'price_desc' => $query->orderBy('lots.price', 'desc'),
            'rating_desc' => $query
                ->join('users', 'lots.seller_id', '=', 'users.id')
                ->select('lots.*')
                ->orderByDesc('users.rating')
                ->orderByDesc('lots.id'),
            default => $query->latest('lots.id'),
        };
    }
}