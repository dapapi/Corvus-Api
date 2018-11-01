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
    protected $create = '创建';
    protected $look = '查看';
    protected $add = '添加';
    protected $delete = '删除';
    protected $recover = '恢复';
    protected $upload_affix = '上传附件';
    protected $download_affix = '下载附件';

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
                $typeName = '任务';
            } else if ($operate->obj instanceof Project) {
                $type = ModuleableType::PROJECT;
                $typeName = '项目';
            }
            //TODO

            $title = $operate->title;
            $start = $operate->start;
            $end = $operate->end;

            $content = null;
            switch ($operate->method) {
                case OperateLogMethod::CREATE://创建
                    $content = $this->create . '' . $typeName;
                    break;
                case OperateLogMethod::UPDATE://修改
                case OperateLogMethod::PRIVACY_UPDATE://隐私修改
                    if (!$start && $end) {
                        $content = sprintf($this->setting_field, $title, $end);
                    } else if ($start && $end) {
                        $content = sprintf($this->update_field, $title, $start, $end);
                    }
                    break;
                case OperateLogMethod::DELETE://删除
                    $content = $this->delete . '' . $typeName;
                    break;
                case OperateLogMethod::FOLLOW_UP://跟进
                    $content = sprintf($this->follow_up, $start);
                    break;
                case OperateLogMethod::LOOK://查看
                    $content = $this->look . '' . $typeName;
                    break;
                case OperateLogMethod::PUBLIC://公开
                    //TODO
                    break;
                case OperateLogMethod::PRIVACY://私密
                    //TODO
                    break;
                case OperateLogMethod::TERMINATION://终止
                    //TODO
                    break;
                case OperateLogMethod::COMPLETE://完成
                    //TODO
                    break;
                case OperateLogMethod::ACTIVATE://激活
                    //TODO
                    break;
                case OperateLogMethod::ADD://添加
                    //TODO
                    break;
                case OperateLogMethod::RECOVER://恢复
                    //TODO
                    break;
                case OperateLogMethod::UPLOAD_AFFIX://上传附件
                    $content = $this->upload_affix;
                    break;
                case OperateLogMethod::DOWNLOAD_AFFIX://下载附件
                    $content = $this->download_affix;
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
