<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class TestConnection implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $message;
    public $time;

    public function __construct($message)
    {
        $this->message = $message;
        $this->time = now()->toTimeString();
    }

    public function broadcastOn()
    {
        return new Channel('attendance');
    }

    public function broadcastAs()
    {
        return 'TestConnection';
    }
}
