<?php

namespace App\Events\configuration;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class ConfigEvent implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $action;
    public $data;

    public function __construct($action, $data)
    {
        $this->action = $action;
        $this->data = $data;
    }

    public function broadcastOn()
    {
        return new Channel('config-event');
    }

    public function broadcastAs()
    {
        return 'config-event';
    }

    public function broadcastWith()
    {
        return [
            'action' => $this->action,
            'data' => $this->data,
        ];
    }
}
