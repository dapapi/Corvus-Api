<?php

namespace App\Events;

use App\Models\Client;
use Illuminate\Broadcasting\Channel;
use Illuminate\Queue\SerializesModels;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;

class ClientDataChangeEvent
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $oldModel;
    public $newModel;
    /**
     * Create a new event instance.
     *
     * @return void
     */
    public function __construct(Client $oldModel,Client $newModel)
    {
        $this->newModel = $newModel;
        $this->oldModel = $oldModel;
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
