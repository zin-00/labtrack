<?php

namespace App\Events;

use App\Models\Computer;
use Illuminate\Broadcasting\Channel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\SerializesModels;

class ComputerEvent implements  ShouldBroadcast, ShouldQueue
{
    use SerializesModels;

    public $computer;
    public $action;

    public function __construct($computer, string $action)
    {
        $this->computer = $computer;
        $this->action = $action;
    }

    public function broadcastOn()
    {
        return new Channel('computers');
    }

    public function broadcastAs()
    {
        return 'ComputerEvent';
    }
    public function broadcastWith()
    {
        return [
            'computer' => $this->computer,
            'action' => $this->action,
        ];

    }

}
