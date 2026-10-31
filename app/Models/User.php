<?php

namespace App\Models;

use App\Enums\UserStatus;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'username',
        'email',
        'password',
        'phone',
        'wallet_balance',
        'rating',
        'status',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'password' => 'hashed',
            'wallet_balance' => 'decimal:2',
            'rating' => 'decimal:2',
            'status' => UserStatus::class,
        ];
    }

    public function lots(): HasMany
    {
        return $this->hasMany(Lot::class, 'seller_id');
    }

    public function purchases(): HasMany
    {
        return $this->hasMany(Order::class, 'buyer_id');
    }

    public function sales(): HasMany
    {
        return $this->hasMany(Order::class, 'seller_id');
    }

    public function walletTransactions(): HasMany
    {
        return $this->hasMany(WalletTransaction::class, 'user_id');
    }

    public function authoredReviews(): HasMany
    {
        return $this->hasMany(Review::class, 'author_id');
    }

    public function receivedReviews(): HasMany
    {
        return $this->hasMany(Review::class, 'seller_id');
    }
}
