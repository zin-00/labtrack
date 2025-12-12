<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class ComputerUnlockRequested implements ShouldBroadcast, ShouldQueue
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $computer;
    public $rfid_uid;


    public function __construct($computer, $rfid_uid)
    {
        $this->computer = $computer;
        $this->rfid_uid = $rfid_uid;
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
        return 'ComputerUnlockRequested';
    }

    /**
     * Get the data to broadcast.
     */
    public function broadcastWith(): array
    {
        return [
            'ip_address' => $this->computer->ip_address,
            'is_online' => $this->computer->is_online,
            'is_lock' => $this->computer->is_lock,
            'computer_number' => $this->computer->computer_number,
            'computer_id' => $this->computer->id,
            'timestamp' => now()->toISOString(),
            'rfid_uid' => $this->rfid_uid
        ];
    }

    /**
     * Determine if this event should broadcast.
     */
    public function broadcastWhen(): bool
    {
        return true;
    }
}
