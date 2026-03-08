<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class AttendanceLogged
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public int $user_id;
    public string $entry_type;
    public int $scanned_at;

    /**
     * Create a new event instance.
     */
    public function __construct(int $userId, string $entryType, int $scannedAt)
    {
        $this->user_id = $userId;
        $this->entry_type = $entryType;
        $this->scanned_at = $scannedAt;
    }
}
