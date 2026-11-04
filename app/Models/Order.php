<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\OrderStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $lot_id
 * @property int $buyer_id
 * @property int $seller_id
 * @property int $quantity
 * @property string|float $unit_price
 * @property string|float $total_price
 * @property OrderStatus $status
 * @property int|null $unread_count
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read Lot|null $lot
 * @property-read User|null $buyer
 * @property-read User|null $seller
 * @property-read Message|null $latestMessage
 */
class Order extends Model
{
    use HasFactory;

    protected $fillable = [
        'lot_id',
        'buyer_id',
        'seller_id',
        'quantity',
        'unit_price',
        'total_price',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'quantity' => 'integer',
            'unit_price' => 'decimal:2',
            'total_price' => 'decimal:2',
            'status' => OrderStatus::class,
        ];
    }

    public function lot(): BelongsTo
    {
        return $this->belongsTo(Lot::class, 'lot_id');
    }

    public function buyer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'buyer_id');
    }

    public function seller(): BelongsTo
    {
        return $this->belongsTo(User::class, 'seller_id');
    }

    public function messages(): HasMany
    {
        return $this->hasMany(Message::class, 'order_id');
    }

    public function latestMessage(): HasOne
    {
        return $this->hasOne(Message::class, 'order_id')->latestOfMany();
    }

    public function review(): HasOne
    {
        return $this->hasOne(Review::class, 'order_id');
    }

    public function counterpartyFor(User $user): User
    {
        return $this->buyer_id === $user->id ? $this->seller : $this->buyer;
    }
}
