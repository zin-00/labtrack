<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class ComputerStatusUpdated implements ShouldBroadcast, ShouldQueue
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $computer;
    public function __construct($computer)
    {
        $this->computer = $computer;
    }

    public function broadcastAs()
    {
        return 'ComputerStatusUpdated';
    }

    /**
     * Get the channels the event should broadcast on.
     *
     * @return array<int, \Illuminate\Broadcasting\Channel>
     */
        public function broadcastOn()
        {
            return [
                new Channel('computer-status'),
                new Channel('computer-status.' . $this->computer->ip_address),
            ];
        }

        public function broadcastWith()
        {
            return [
                'computer' => [
                                'id' => $this->computer->id,
                                'computer_number' => $this->computer->computer_number,
                                'ip_address' => $this->computer->ip_address,
                                'mac_address' => $this->computer->mac_address,
                                'laboratory_id' => $this->computer->laboratory_id,
                                'status' => $this->computer->status,
                                'is_online' => $this->computer->is_online,
                                'is_lock' => $this->computer->is_lock,
                            ]

                    ];
        }
}
