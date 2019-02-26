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
        //发消息
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
        'App\Events\TrailMessageEvent'   =>  [//监听博主签约解约
            'App\Listeners\TrailMessageEventListener',
        ],
        'App\Events\AnnouncementMessageEvent'   =>  [//监听博主签约解约
            'App\Listeners\AnnouncementMessageEventListener',
        ],

        //日志
        'App\Events\TaskDataChangeEvent'    =>  [//任务修改，增加操作日志
            'App\Listeners\TaskDataChangeListener',
        ],
        'App\Events\TrailDataChangeEvent'    =>  [//监听线索修改，增加操作日志
            'App\Listeners\TrailDataChangeListener',
        ],
        'App\Events\ClientDataChangeEvent'    =>  [//监听客户修改，增加操作日志
            'App\Listeners\ClientDataChangeListener',
        ],
        'App\Events\ProjectDataChangeEvent'    =>  [//监听客户修改，增加操作日志
            'App\Listeners\ProjejctDataChangeListener',
        ],
        'App\Events\StarDataChangeEvent'    =>  [//监听艺人修改，增加操作日志
            'App\Listeners\StarDataChangeListener',
        ],
        'App\Events\BloggerDataChangeEvent'    =>  [//监听博主修改，增加操作日志
            'App\Listeners\BloggerDataChangeListener',
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
