<?php

namespace App\Listeners;

use App\Events\OperateLogEvent;
use App\Models\Announcement;
use App\Models\Attendance;
use App\Models\Blogger;
use App\Models\Client;
use App\Models\Contact;
use App\Models\OperateLog;
use App\Models\Project;
use App\Models\Star;
use App\Models\Report;
use App\Models\Calendar;
use App\Models\Task;
use App\Models\Schedule;
use App\Models\PersonalJob;
use App\Models\PersonalSalary;
use App\Models\Department;
use App\Models\Issues;
use App\Models\Trail;
use App\Models\Repository;
use App\Models\Work;
use App\Models\GroupRoles;
use App\Models\ProjectBillsResource;
use App\Models\Role;
use App\User;
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

    protected $update_field = '修改 %s 从【%s】到【%s】';
    protected $setting_field = '设置 %s 为【%s】';
    protected $cancel_field = '取消 %s %s';
    protected $follow_up = '跟进：%s';
    protected $create = '创建';
    protected $look = '查看';
    protected $add = '添加';
    protected $add_person = '添加 %s %s';
    protected $del_person = '删除 %s %s';
    protected $delete = '删除';
    protected $recover = '恢复';
    protected $upload_affix = '上传附件';
    protected $download_affix = '下载附件';
    protected $termination = '终止';
    protected $complete = '完成';
    protected $activate = '激活';
    protected $public = '公开';
    protected $privacy = '私密';
    protected $relevance_resource = '关联';
    protected $un_relevance_resource = '解除关联';
    protected $principal = '负责人';
    protected $cancel = '取消';
    protected $renewal = '更新';
    protected $transfer = '调岗';
    protected $refuse = '拒绝了';
    protected $add_work = '添加 %s 作品';
    protected $add_star_task = "创建了关联资源 %s 任务";

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
            } else if ($operate->obj instanceof Star) {
                $type = ModuleableType::STAR;
                $typeName = '艺人';
            } else if ($operate->obj instanceof Blogger) {
                $type = ModuleableType::BLOGGER;
                $typeName = '博主';
            }else if ($operate->obj instanceof User) {
                $type = ModuleableType::USER;
                $typeName = '用户';
            }else if ($operate->obj instanceof PersonalJob) {
                $type = ModuleableType::PERSONA_JOB;
                $typeName = '岗位';
            }else if ($operate->obj instanceof PersonalSalary) {
                $type = ModuleableType::PERSONA_SALARY;
                $typeName = '薪资';
            }else if($operate->obj instanceof Work){
                $type = ModuleableType::WORK;
                $typeName = '作品库';
            }else if($operate->obj instanceof Attendance){
                $type = ModuleableType::ATTENDANCE;
                $typeName = '考勤';
            }else if($operate->obj instanceof Department){
                $type = ModuleableType::DEPARTMENT;
                $typeName = '部门';
            }else if($operate->obj instanceof Trail){
                $type = ModuleableType::TRAIL;
                $typeName = '销售线索';
            }else if($operate->obj instanceof Client){
                $type = ModuleableType::CLIENT;
                $typeName = '客户';
            }else if($operate->obj instanceof Contact){
                $type = ModuleableType::CONTACT;
                $typeName = '联系人';
            }else if($operate->obj instanceof Announcement){
                $type = ModuleableType::ANNOUNCEMENT;
                $typeName = '公告';
            }else if($operate->obj instanceof Issues){
                $type = ModuleableType::ISSUES;
                $typeName = '问题';
            }else if($operate->obj instanceof Report){
                $type = ModuleableType::REPORT;
                $typeName = '简报';
            }else if($operate->obj instanceof Repository){
                $type = ModuleableType::REPOSITORY;
                $typeName = '知识库';
            }else if($operate->obj instanceof Schedule){
                $type = ModuleableType::SCHEDULE;
                $typeName = '日程';
            }else if($operate->obj instanceof Calendar){
                $type = ModuleableType::CALENDAR;
                $typeName = '日历';
            }else if($operate->obj instanceof GroupRoles){
                $type = ModuleableType::GTOUPROLES;
                $typeName = '分组';
            }else if($operate->obj instanceof ProjectBillsResource){
                $type = ModuleableType::GTOUPROLES;
                $typeName = '分组';
            }else if($operate->obj instanceof Role) {
                $type = ModuleableType::ROLE;
                $typeName = '角色';
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
                case OperateLogMethod::RELEVANCE_RESOURCE://关联资源
                    $level = OperateLogLevel::LOW;
                    $content = $this->relevance_resource . $title . ' ' . $start;
                    break;
                case OperateLogMethod::UN_RELEVANCE_RESOURCE://解除关联资源
                    $level = OperateLogLevel::MIDDLE;
                    $content = $this->un_relevance_resource . $title . ' ' . $start;
                    break;
                case OperateLogMethod::DEL_PRINCIPAL://删除负责人
                    $level = OperateLogLevel::MIDDLE;
                    $content = $this->delete . $typeName . $this->principal . $start;
                    break;
                case OperateLogMethod::CANCEL://取消
                    $level = OperateLogLevel::MIDDLE;
                    $content = sprintf($this->cancel_field, $title, $start);
                    break;
                case OperateLogMethod::RENEWAL://更新TRANSFER
                    $level = OperateLogLevel::MIDDLE;
                    $content = $this->renewal . $title;
                    break;
                case OperateLogMethod::TRANSFER://调岗
                    $level = OperateLogLevel::MIDDLE;
                    $content = $this->transfer . $title;
                    break;
                case OperateLogMethod::REFUSE://拒绝线索
                    $level = OperateLogLevel::HIGH;
                    $content = $this->refuse. $typeName . '，' . $start;
                    break;
                case OperateLogMethod::ADD_WORK://为艺人添加作品
                    $level = OperateLogLevel::LOW;
                    $content = sprintf($this->add_work, $title);;
                    break;
                case OperateLogMethod::ADD_STAR_TASK:
                    $level = OperateLogLevel::LOW;
                    $content = sprintf($this->add_star_task,$title);
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
