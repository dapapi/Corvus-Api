<?php

namespace App\Events;

use App\Models\Blogger;
use Illuminate\Broadcasting\Channel;
use Illuminate\Queue\SerializesModels;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;

class BloggerMessageEvent
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $blogger_arrel;
    public $trigger_point;
    public $authorization;
    public $user;
    /**
     * Create a new event instance.
     *
     * @return void
     */
    public function __construct($blogger_arr,$trigger_point,$authorization,User $user)
    {
        $this->blogger_arr = $blogger_arr;
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
