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
//        'App\Events\MessageEvent'   =>  [
//            'App\Listeners\MessageEventListener',
//        ],
        'App\Events\TaskMessageEvent'   =>  [//监听任务事件
            'App\Listeners\TaskMessageEventListener',
        ],
        'App\Events\ApprovalMessageEvent'   =>  [//监听审批事件
            'App\Listeners\ApprovalMessageEventListener',
        ],
        'App\Events\CalendarMessageEvent'   =>  [//监听日历事件
            'App\Listeners\CalendarMessageEventListener',
        ],
        'App\Events\StarMessageEvent'   =>  [//监听艺人签约解约
            'App\Listeners\StarMessageEventListener',
        ],
        'App\Events\BloggerMessageEvent'   =>  [//监听博主签约解约
            'App\Listeners\BloggerMessageEventListener',
        ],
        'App\Events\ClientMessageEvent'   =>  [//监听博主签约解约
            'App\Listeners\ClientMessageEventListener',
        ],
        'App\Events\dataChangeEvent'    =>  [
            'App\Listeners\dataChangeListener',
        ],

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
