<?php

namespace App\Events;

use App\Models\Star;
use Illuminate\Broadcasting\Channel;
use Illuminate\Queue\SerializesModels;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;

class StarMessageEvent
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $star_arr;
    public $trigger_point;
    public $authorization;
    public $user;
    /**
     * Create a new event instance.
     *
     * @return void
     */
    public function __construct($star_arr,$trigger_point,$authorization,User $user)
    {
        $this->star_arr = $star_arr;
        $this->trigger_point = $trigger_point;
        $this->authorization = $authorization;
        $this->user = $user;
    }

    /**
     * Get the channels the event should broadcast on.
     *
     * @return \Illuminate\Broadcasting\Channel|array
     */
    public function broadcastOn()
    {
        return new PrivateChannel('channel-name');
    }
}
