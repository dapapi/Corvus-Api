<?php

namespace App\Listeners;

use App\Events\OperateLogEvent;
use App\Models\OperateLog;
use App\Models\Project;
use App\Models\Task;
use App\ModuleableType;
use App\OperateLogLevel;
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
    protected $add_person = '添加【%s】%s';
    protected $del_person = '删除【%s】%s';
    protected $delete = '删除';
    protected $recover = '恢复';
    protected $upload_affix = '上传附件';
    protected $download_affix = '下载附件';
    protected $termination = '终止';
    protected $complete = '完成';
    protected $activate = '激活';
    protected $public = '公开';
    protected $privacy = '私密';

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
            $level = 0;

            $content = null;
            switch ($operate->method) {
                case OperateLogMethod::CREATE://创建
                    $level = OperateLogLevel::LOW;
                    $content = $this->create . '' . $typeName;
                    break;
                case OperateLogMethod::UPDATE://修改
                    if ($level == 0)
                        $level = OperateLogLevel::MIDDLE;
                case OperateLogMethod::UPDATE_PRIVACY://修改隐私
                    if ($level == 0)
                        $level = OperateLogLevel::HIGH;
                case OperateLogMethod::UPDATE_SIGNIFICANCE://修改重要
                    if ($level == 0)
                        $level = OperateLogLevel::HIGH;
                    //
                    if (!$start && $end) {
                        $content = sprintf($this->setting_field, $title, $end);
                    } else if ($start && $end) {
                        $content = sprintf($this->update_field, $title, $start, $end);
                    }
                    break;
                case OperateLogMethod::DELETE://删除
                    $level = OperateLogLevel::HIGH;
                    $content = $this->delete . '' . $typeName;
                    break;
                case OperateLogMethod::DELETE_OTHER://删除其他
                    $level = OperateLogLevel::HIGH;
                    $content = $this->delete . '' . $title;
                    break;
                case OperateLogMethod::FOLLOW_UP://跟进
                    $level = OperateLogLevel::LOW;
                    $content = sprintf($this->follow_up, $start);
                    break;
                case OperateLogMethod::LOOK://查看
                    $level = OperateLogLevel::LOW;
                    $content = $this->look . '' . $typeName;
                    break;
                case OperateLogMethod::PUBLIC://公开
                    $level = OperateLogLevel::HIGH;
                    $content = $typeName . '转为' . $this->public;
                    break;
                case OperateLogMethod::PRIVACY://私密
                    $level = OperateLogLevel::HIGH;
                    $content = $typeName . '转为' . $this->privacy;
                    //TODO
                    break;
                case OperateLogMethod::TERMINATION://终止
                    $level = OperateLogLevel::MIDDLE;
                    $content = $this->termination . $typeName;
                    break;
                case OperateLogMethod::COMPLETE://完成
                    $level = OperateLogLevel::MIDDLE;
                    $content = $this->complete . $typeName;
                    break;
                case OperateLogMethod::ACTIVATE://激活
                    $level = OperateLogLevel::MIDDLE;
                    $content = $this->activate . $typeName;
                    break;
                case OperateLogMethod::ADD://添加
                    $level = OperateLogLevel::LOW;
                    //TODO
                    break;
                case OperateLogMethod::RECOVER://恢复
                    $level = OperateLogLevel::HIGH;
                    $content = $this->recover . '' . $typeName;
                    break;
                case OperateLogMethod::RECOVER_OTHER://恢复其他
                    $level = OperateLogLevel::HIGH;
                    $content = $this->recover . '' . $title;
                    break;
                case OperateLogMethod::UPLOAD_AFFIX://上传附件
                    $level = OperateLogLevel::LOW;
                    $content = $this->upload_affix;
                    break;
                case OperateLogMethod::DOWNLOAD_AFFIX://下载附件
                    $level = OperateLogLevel::LOW;
                    $content = $this->download_affix;
                    break;
                case OperateLogMethod::ADD_PERSON://添加人
                    $level = OperateLogLevel::LOW;
                    $content = sprintf($this->add_person, $title, $start);
                    break;
                case OperateLogMethod::DEL_PERSON://删除人
                    $level = OperateLogLevel::MIDDLE;
                    $content = sprintf($this->del_person, $title, $start);
                    break;
            }

            OperateLog::create([
                'user_id' => $user->id,
                'logable_id' => $id,
                'logable_type' => $type,
                'content' => $content,
                'method' => $operate->method,
                'level' => $level,
                'status' => 1,
            ]);

        }
    }
}
