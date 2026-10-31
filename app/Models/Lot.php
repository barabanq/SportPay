<?php

namespace App\Models;

use App\Enums\LotStatus;
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
        'price',
        'stock_quantity',
        'status',
    ];

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
}
