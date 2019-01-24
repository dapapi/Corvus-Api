<?php

namespace App\Providers;

use App\Models\Trail;
use Illuminate\Support\Facades\Event;
use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;

class EventServiceProvider extends ServiceProvider
{
    /**
     * The event listener mappings for the application.
     *
     * @var array
     */
    protected $listen = [
        'App\Events\OperateLogEvent' => [
            'App\Listeners\OperateLogEventListener',
        ],
        'App\Events\MessageEvent'   =>  [
            'App\Listeners\MessageEventListener',
        ],
        'App\Events\dataChangeEvent'    =>  [
            'App\Listeners\dataChangeListener',
        ]
    ];

    /**
     * Register any events for your application.
     *
     * @return void
     */
    public function boot()
    {
        parent::boot();

    }
}
