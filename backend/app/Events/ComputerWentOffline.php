<?php

namespace App\Events;

use App\Models\Computer;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class ComputerWentOffline implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $computer;
    public $reason;
    public $timestamp;

    /**
     * Create a new event instance.
     */
    public function __construct(Computer $computer, string $reason = 'missed_heartbeats')
    {
        $this->computer = $computer;
        $this->reason = $reason;
        $this->timestamp = now();
    }

    /**
     * Get the channels the event should broadcast on.
     */
    public function broadcastOn(): array
    {
        return [
            new Channel('computer'),
            new Channel('computer-status.' . $this->computer->ip_address),
            new Channel('lab.' . $this->computer->lab_id),
        ];
    }

    /**
     * The event's broadcast name.
     */
    public function broadcastAs(): string
    {
        return 'ComputerWentOffline';
    }

    /**
     * Get the data to broadcast.
     */
    public function broadcastWith(): array
    {
        return [
            'id' => $this->computer->id,
            'ip_address' => $this->computer->ip_address,
            'mac_address' => $this->computer->mac_address,
            'pc_number' => $this->computer->pc_number,
            'computer_name' => $this->computer->computer_name,
            'is_online' => false,
            'is_lock' => $this->computer->is_lock,
            'last_seen' => $this->computer->last_seen?->toISOString(),
            'reason' => $this->reason,
            'lab_id' => $this->computer->lab_id,
            'lab_name' => $this->computer->lab->name ?? null, // If you have lab relationship
            'timestamp' => $this->timestamp->toISOString(),
            'message' => $this->getMessage()
        ];
    }

    /**
     * Get the human-readable message.
     */
    protected function getMessage(): string
    {
        $messages = [
            'missed_heartbeats' => "Computer {$this->computer->pc_number} ({$this->computer->ip_address}) went offline due to missed heartbeats",
            'manual' => "Computer {$this->computer->pc_number} was manually taken offline",
            'shutdown' => "Computer {$this->computer->pc_number} was shut down",
            'maintenance' => "Computer {$this->computer->pc_number} taken offline for maintenance",
            'unknown' => "Computer {$this->computer->pc_number} went offline unexpectedly"
        ];

        return $messages[$this->reason] ?? $messages['unknown'];
    }
}
