<?php

namespace App\Listeners;

use App\Events\OperateLogEvent;
use App\Models\OperateLog;
use App\Models\Project;
use App\Models\Task;
use App\ModuleableType;
use App\OperateLogMethod;
use Illuminate\Support\Facades\Auth;

class OperateLogEventListener
{
    /**
     * Create the event listener.
     *
     * @return void
     */
    public function __construct()
    {
        //
    }

    protected $update_field = '修改【%s】从【%s】到【%s】';
    protected $setting_field = '设置【%s】为【%s】';

    protected $follow_up = '跟进：%s';

    /**
     * Handle the event.
     *
     * @param  OperateLogEvent $event
     * @return void
     */
    public function handle(OperateLogEvent $event)
    {
        $user = Auth::guard('api')->user();
        if (!$user) {
            abort(401);
        }
        $operateList = $event->operateList;
        foreach ($operateList as $operate) {
            $id = $operate->obj->id;
            if ($operate->obj instanceof Task) {
                $type = ModuleableType::TASK;
            } else if ($operate->obj instanceof Project) {
                $type = ModuleableType::PROJECT;
            }
            //TODO

            $title = $operate->title;
            $start = $operate->start;
            $end = $operate->end;

            $content = null;
            switch ($operate->method) {
                case OperateLogMethod::CREATE://创建
                    break;
                case OperateLogMethod::UPDATE://修改
                    if (!$start && $end) {
                        $content = sprintf($this->setting_field, $title, $end);
                    } else if ($start && $end) {
                        $content = sprintf($this->update_field, $title, $start, $end);
                    }
                    break;
                case OperateLogMethod::DELETE://删除
                    break;
                case OperateLogMethod::FOLLOW_UP://跟进
                    break;
                case OperateLogMethod::LOOK://查看
                    break;
            }

            OperateLog::create([
                'user_id' => $user->id,
                'logable_id' => $id,
                'logable_type' => $type,
                'content' => $content,
                'method' => $operate->method,
                'level' => $operate->level,
                'status' => 1,
            ]);

        }
    }
}
