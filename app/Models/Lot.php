<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\LotStatus;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Storage;

/**
 * @property int $id
 * @property int $seller_id
 * @property string $title
 * @property string $product_type
 * @property string|null $description
 * @property string|null $image_path
 * @property string|null $auto_delivery_text
 * @property string|float $price
 * @property int $stock_quantity
 * @property LotStatus $status
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read User|null $seller
 */
class Lot extends Model
{
    use HasFactory;

    protected $fillable = [
        'seller_id',
        'title',
        'product_type',
        'description',
        'image_path',
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

    public function imageUrl(): ?string
    {
        if (! filled($this->image_path)) {
            return null;
        }

        return Storage::disk('public')->url($this->image_path);
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

    public function scopeFilter(
        Builder $query,
        ?string $type = null,
        ?string $search = null,
        ?float $minPrice = null,
        ?float $maxPrice = null,
        ?string $seller = null,
        bool $onlineOnly = false
    ): Builder {
        return $query
            ->where('lots.stock_quantity', '>', 0)
            ->where('lots.status', LotStatus::Active)
            ->when($type, fn (Builder $q) => $q->where('lots.product_type', $type))
            ->when($search, function (Builder $q) use ($search): void {
                $term = '%'.trim($search).'%';
                $q->where(function (Builder $sub) use ($term): void {
                    $sub->where('lots.title', 'like', $term)
                        ->orWhere('lots.description', 'like', $term);
                });
            })
            ->when($minPrice !== null && $minPrice > 0, fn (Builder $q) => $q->where('lots.price', '>=', $minPrice))
            ->when($maxPrice !== null && $maxPrice > 0, fn (Builder $q) => $q->where('lots.price', '<=', $maxPrice))
            ->when($seller, function (Builder $q) use ($seller): void {
                $cleanSeller = trim($seller);
                $q->whereHas('seller', function (Builder $sq) use ($cleanSeller): void {
                    if (is_numeric($cleanSeller)) {
                        $sq->where('users.id', (int) $cleanSeller);
                    } else {
                        $sq->where('users.username', 'like', '%'.$cleanSeller.'%');
                    }
                });
            })
            ->when($onlineOnly, function (Builder $q): void {
                $q->whereHas('seller', fn (Builder $sq) => $sq->where('users.last_seen_at', '>=', now()->subMinutes(5)));
            });
    }

    public function scopeSortBy(Builder $query, ?string $sort): Builder
    {
        $threshold = now()->subMinutes(5)->toDateTimeString();

        return match ($sort) {
            'price_asc' => $query->orderBy('lots.price', 'asc'),
            'price_desc' => $query->orderBy('lots.price', 'desc'),
            'rating_desc' => $query
                ->join('users', 'lots.seller_id', '=', 'users.id')
                ->select('lots.*')
                ->orderByDesc('users.rating')
                ->orderByDesc('lots.id'),
            'online_first' => $query
                ->join('users', 'lots.seller_id', '=', 'users.id')
                ->select('lots.*')
                ->orderByRaw('CASE WHEN users.last_seen_at >= ? THEN 1 ELSE 0 END DESC', [$threshold])
                ->orderByDesc('users.last_seen_at')
                ->orderByDesc('lots.id'),
            default => $query->latest('lots.id'),
        };
    }
}
