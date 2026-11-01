<?php

declare(strict_types=1);

namespace App\Events;

use App\Models\GlobalMessage;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class MessageSent implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public GlobalMessage $globalMessage
    ) {}

    /**
     * Канал, на который идет вещание (публичный канал 'public-chat')
     */
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
}