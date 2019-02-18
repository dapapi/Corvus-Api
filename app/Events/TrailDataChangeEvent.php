<?php

namespace App\Events;

use App\Models\Trail;
use Illuminate\Broadcasting\Channel;
use Illuminate\Queue\SerializesModels;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;

class TrailDataChangeEvent
{
    use Dispatchable, InteractsWithSockets, SerializesModels;
    public $oldModel;
    public $newModel;
    /**
     * Create a new event instance.
     *
     * @return void
     */
    public function __construct(Trail $oldModel,Trail $newModel)
    {
        $this->oldModel = $oldModel;
        $this->newModel = $newModel;
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
