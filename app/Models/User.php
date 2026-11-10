<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\UserStatus;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Storage;

/**
 * @property int $id
 * @property string $username
 * @property string $email
 * @property string $password
 * @property string|null $phone
 * @property string|null $avatar_path
 * @property string|float $wallet_balance
 * @property string|float $rating
 * @property UserStatus $status
 * @property Carbon|null $last_seen_at
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
class User extends Authenticatable
{
    use HasFactory;
    use Notifiable;

    protected $fillable = [
        'tg_notify_messages',
        'tg_subscribed_categories',
        'tg_radar_lat',
        'tg_radar_lng',
        'tg_radar_radius_km',
        'telegram_chat_id',
        'telegram_username',
        'telegram_link_token',
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
            'tg_notify_messages' => 'boolean',
            'tg_subscribed_categories' => 'array',
            'tg_radar_lat' => 'float',
            'tg_radar_lng' => 'float',
            'tg_radar_radius_km' => 'integer',
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
        return $this->last_seen_at instanceof Carbon && $this->last_seen_at->gt(now()->subMinutes(5));
    }

    public function scopeOnline(Builder $query): Builder
    {
        return $query->where('users.last_seen_at', '>=', now()->subMinutes(5));
    }

    public function avatarUrl(): ?string
    {
        if (! filled($this->avatar_path)) {
            return null;
        }

        return Storage::disk('public')->url($this->avatar_path);
    }
    public function markMessagesAsRead(): void
    {
        $myOrderIds = \App\Models\Order::where('buyer_id', $this->id)
            ->orWhere('seller_id', $this->id)
            ->pluck('id');

        $maxId = $myOrderIds->isNotEmpty()
            ? (int) \App\Models\Message::whereIn('order_id', $myOrderIds)->max('id')
            : 0;

        \Illuminate\Support\Facades\Cache::forever('sp_last_read_msg_' . $this->id, $maxId);
        session(['sp_last_read_msg_' . $this->id => $maxId]);
    }

    public function unreadMessagesCount(): int
    {
        if (request()->routeIs('chat.*')) {
            $this->markMessagesAsRead();
            return 0;
        }

        $myOrderIds = \App\Models\Order::where('buyer_id', $this->id)
            ->orWhere('seller_id', $this->id)
            ->pluck('id');

        if ($myOrderIds->isEmpty()) {
            return 0;
        }

        $lastReadId = max(
            (int) \Illuminate\Support\Facades\Cache::get('sp_last_read_msg_' . $this->id, 0),
            (int) session('sp_last_read_msg_' . $this->id, 0)
        );

        return \App\Models\Message::whereIn('order_id', $myOrderIds)
            ->where('user_id', '!=', $this->id)
            ->where('id', '>', $lastReadId)
            ->count();
    }
}
