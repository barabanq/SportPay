<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $user_id
 * @property int|null $order_id
 * @property string $category
 * @property string $subject
 * @property string $message
 * @property string $status
 * @property string|null $staff_reply
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read User|null $user
 * @property-read Order|null $order
 */
class SupportTicket extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'order_id',
        'category',
        'subject',
        'message',
        'status',
        'staff_reply',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class, 'order_id');
    }

    public function statusLabel(): string
    {
        return match ($this->status) {
            'answered' => 'Есть ответ поддержки',
            'closed' => 'Закрыт',
            default => 'Открыт (В работе)',
        };
    }

    public function statusBadgeClasses(): string
    {
        return match ($this->status) {
            'answered' => 'bg-emerald-500/20 text-emerald-300 border border-emerald-500/30',
            'closed' => 'bg-slate-800 text-slate-400',
            default => 'bg-amber-500/20 text-amber-300 border border-amber-500/30',
        };
    }
}
