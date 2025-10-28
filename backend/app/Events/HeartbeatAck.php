<?php

namespace App\Events;

use App\Models\Computer;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class HeartbeatAck implements ShouldBroadcast, ShouldQueue
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $computer;

    /**
     * Create a new event instance.
     */
    public function __construct(Computer $computer)
    {
        $this->computer = $computer;
    }

    /**
     * Get the channels the event should broadcast on.
     */
    public function broadcastOn(): array
    {
        return [
            new Channel('computer'),
            new Channel('computer-status.' . $this->computer->ip_address),
        ];
    }

    /**
     * The event's broadcast name.
     */
    public function broadcastAs(): string
    {
        return 'HeartbeatAck';
    }

    /**
     * Get the data to broadcast.
     */
    public function broadcastWith(): array
    {
        return [
            'ip_address' => $this->computer->ip_address,
            'last_heartbeat' => $this->computer->last_heartbeat,
            'is_online' => $this->computer->is_online,
        ];
    }
}
