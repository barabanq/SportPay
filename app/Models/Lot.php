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
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class Lot extends Model
{
    use HasFactory;

    protected $fillable = [
        'seller_id',
        'title',
        'product_type',
        'description',
        'location_name',
        'latitude',
        'longitude',
        'image_path',
        'auto_delivery_text',
        'price',
        'stock_quantity',
        'status',
    ];

    protected $hidden = [
        'auto_delivery_text',
        'coordinates',
    ];

    protected static function booted(): void
    {
        static::saved(function (Lot $lot): void {
            if (DB::getDriverName() !== 'pgsql') {
                return;
            }

            if ($lot->latitude !== null && $lot->longitude !== null) {
                DB::statement(
                    'UPDATE lots SET coordinates = ST_SetSRID(ST_MakePoint(?, ?), 4326)::geography WHERE id = ?',
                    [(float) $lot->longitude, (float) $lot->latitude, $lot->id]
                );
            } else {
                DB::statement('UPDATE lots SET coordinates = NULL WHERE id = ?', [$lot->id]);
            }
        });
    }

    public function hasAutoDelivery(): bool
    {
        return filled($this->auto_delivery_text);
    }

    public function hasLocation(): bool
    {
        return $this->latitude !== null && $this->longitude !== null;
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
            'latitude' => 'decimal:7',
            'longitude' => 'decimal:7',
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
        bool $onlineOnly = false,
        ?float $userLat = null,
        ?float $userLng = null,
        ?float $radiusKm = null
    ): Builder {
        $likeOperator = DB::getDriverName() === 'pgsql' ? 'ilike' : 'like';

        $query->select('lots.*')
            ->where('lots.stock_quantity', '>', 0)
            ->where('lots.status', LotStatus::Active)
            ->when($type, fn (Builder $q) => $q->where('lots.product_type', $type))
            ->when($search, function (Builder $q) use ($search, $likeOperator): void {
                $term = '%'.trim($search).'%';
                $q->where(function (Builder $sub) use ($term, $likeOperator): void {
                    $sub->where('lots.title', $likeOperator, $term)
                        ->orWhere('lots.description', $likeOperator, $term)
                        ->orWhere('lots.location_name', $likeOperator, $term);
                });
            })
            ->when($minPrice !== null && $minPrice > 0, fn (Builder $q) => $q->where('lots.price', '>=', $minPrice))
            ->when($maxPrice !== null && $maxPrice > 0, fn (Builder $q) => $q->where('lots.price', '<=', $maxPrice))
            ->when($seller, function (Builder $q) use ($seller, $likeOperator): void {
                $cleanSeller = trim($seller);
                $q->whereHas('seller', function (Builder $sq) use ($cleanSeller, $likeOperator): void {
                    if (is_numeric($cleanSeller)) {
                        $sq->where('users.id', (int) $cleanSeller);
                    } else {
                        $sq->where('users.username', $likeOperator, '%'.$cleanSeller.'%');
                    }
                });
            })
            ->when($onlineOnly, function (Builder $q): void {
                $q->whereHas('seller', fn (Builder $sq) => $sq->where('users.last_seen_at', '>=', now()->subMinutes(5)));
            });

        if (DB::getDriverName() === 'pgsql' && $userLat !== null && $userLng !== null) {
            $query->selectRaw(
                'ROUND((ST_Distance(lots.coordinates, ST_SetSRID(ST_MakePoint(?, ?), 4326)::geography) / 1000.0)::numeric, 1) as distance_km',
                [$userLng, $userLat]
            );

            if ($radiusKm !== null && $radiusKm > 0) {
                $query->whereNotNull('lots.coordinates')
                    ->whereRaw(
                        'ST_DWithin(lots.coordinates, ST_SetSRID(ST_MakePoint(?, ?), 4326)::geography, ?)',
                        [$userLng, $userLat, $radiusKm * 1000.0]
                    );
            }
        }

        return $query;
    }

    public function scopeSortBy(Builder $query, ?string $sort, ?float $userLat = null, ?float $userLng = null): Builder
    {
        $threshold = now()->subMinutes(5)->toDateTimeString();

        if ($sort === 'nearest' && DB::getDriverName() === 'pgsql' && $userLat !== null && $userLng !== null) {
            return $query
                ->orderByRaw('lots.coordinates IS NULL ASC')
                ->orderByRaw(
                    'ST_Distance(lots.coordinates, ST_SetSRID(ST_MakePoint(?, ?), 4326)::geography) ASC',
                    [$userLng, $userLat]
                )
                ->orderByDesc('lots.id');
        }

        return match ($sort) {
            'price_asc' => $query->orderBy('lots.price', 'asc'),
            'price_desc' => $query->orderBy('lots.price', 'desc'),
            'rating_desc' => $query
                ->join('users', 'lots.seller_id', '=', 'users.id')
                ->orderByDesc('users.rating')
                ->orderByDesc('lots.id'),
            'online_first' => $query
                ->join('users', 'lots.seller_id', '=', 'users.id')
                ->orderByRaw('CASE WHEN users.last_seen_at >= ? THEN 1 ELSE 0 END DESC', [$threshold])
                ->orderByDesc('users.last_seen_at')
                ->orderByDesc('lots.id'),
            default => $query->latest('lots.id'),
        };
    }
}