<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\UserStatus;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\Storage;

class User extends Authenticatable
{
    use HasFactory, Notifiable;

    protected $fillable = [
        'username',
        'email',
        'password',
        'phone',
        'avatar_path',
        'wallet_balance',
        'rating',
        'status',
        'last_seen_at',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'password' => 'hashed',
            'wallet_balance' => 'decimal:2',
            'rating' => 'decimal:2',
            'status' => UserStatus::class,
            'last_seen_at' => 'datetime',
        ];
    }

    public function lots(): HasMany
    {
        return $this->hasMany(Lot::class, 'seller_id');
    }

    public function receivedReviews(): HasMany
    {
        return $this->hasMany(Review::class, 'seller_id');
    }

    public function globalMessages(): HasMany
    {
        return $this->hasMany(GlobalMessage::class, 'user_id');
    }

    public function isArbiter(): bool
    {
        return $this->email === 'arbiter@sportpay.local' || str_starts_with($this->username, 'Arbiter');
    }

    public function isOnline(): bool
    {
        return $this->last_seen_at !== null && $this->last_seen_at->gt(now()->subMinutes(5));
    }

    public function scopeOnline(Builder $query): Builder
    {
        return $query->where('users.last_seen_at', '>=', now()->subMinutes(5));
    }

    public function avatarUrl(): ?string
    {
        if (!filled($this->avatar_path)) {
            return null;
        }

        return Storage::disk('public')->url($this->avatar_path);
    }
}
