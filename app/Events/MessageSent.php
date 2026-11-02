<?php

declare(strict_types=1);

namespace App\Events;

use App\Models\GlobalMessage;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class MessageSent implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public readonly GlobalMessage $globalMessage
    ) {}

    public function broadcastOn(): array
    {
        return [
            new Channel('public-chat'),
        ];
    }

    public function broadcastAs(): string
    {
        return 'message.sent';
    }

    public function broadcastWith(): array
    {
        return [
            'id' => $this->globalMessage->id,
            'user_id' => $this->globalMessage->user_id,
            'username' => $this->globalMessage->user->username ?? 'Юзер',
            'body' => $this->globalMessage->body,
            'time' => $this->globalMessage->created_at?->format('H:i') ?? now()->format('H:i'),
        ];
    }
}