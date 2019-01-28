<?php

namespace App\Listeners;

use App\Events\TrailMessageEvent;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;

class TrailMessageEventListener
{
    /**
     * Create the event listener.
     *
     * @return void
     */
    public function __construct()
    {

    }

    /**
     * Handle the event.
     *
     * @param  TrailMessageEvent  $event
     * @return void
     */
    public function handle(TrailMessageEvent $event)
    {
        $this->task = $event->model;
        $this->trigger_point = $event->trigger_point;
        $this->authorization = $event->authorization;
        $this->user = $event->user;
    }
}
