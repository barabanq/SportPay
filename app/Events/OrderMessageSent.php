<?php

declare(strict_types=1);

namespace App\Events;

use App\Models\Message;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class OrderMessageSent implements ShouldBroadcastNow
{
    use Dispatchable;
    use InteractsWithSockets;
    use SerializesModels;

    public function __construct(
        public readonly Message $message
    ) {
    }

    public function broadcastOn(): array
    {
        return [
            new Channel("orders.{$this->message->order_id}"),
        ];
    }

    public function broadcastAs(): string
    {
        return 'order.message.sent';
    }

    public function broadcastWith(): array
    {
        return [
            'id' => $this->message->id,
            'order_id' => $this->message->order_id,
            'user_id' => $this->message->user_id,
            'username' => $this->message->user->username ?? 'Система',
            'body' => $this->message->body,
            'is_system' => (bool) $this->message->is_system,
            'time' => $this->message->created_at?->format('H:i') ?? now()->format('H:i'),
        ];
    }
}
