<?php

namespace App\Listeners;

use App\Events\OperateLogEvent;
use App\Jobs\RecordOperateLog;
use App\Models\Announcement;
use App\Models\ApprovalForm\ApprovalForm;
use App\Models\ApprovalForm\Business;
use App\Models\ApprovalForm\Instance;
use App\Models\Attendance;
use App\Models\Blogger;
use App\Models\Client;
use App\Models\Contact;
use App\Models\Contract;
use App\Models\OperateLog;
use App\Models\Production;
use App\Models\Project;
use App\Models\ProjectImplode;
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
use App\ModuleUserType;
use App\User;
use App\ModuleableType;
use App\OperateLogLevel;
use App\OperateLogMethod;
use Carbon\Carbon;
use Illuminate\Queue\Jobs\Job;
use Illuminate\Support\Facades\Auth;

class OperateLogEventListener
{
    private $implodeModel;
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
    protected $add_work = '为该艺人新增了 %s 作品';
    protected $create_signing_contracts="%s 创建了签约合同";
    protected $create_rescission_contracts = "%s 创建了解约合同";
    protected $add_resource_task = "创建了关联资源为该%s的任务";
    protected $add_production = "为该博主新增了 %s 做品";

//    protected $add_trail_task = "创建了关联资源为该销售线索的任务";
//    protected $add_client_task = "创建了关联资源为该客户的任务";
    protected $add_client_contracts = "添加了该客户的联系人";
    protected $add_relate = "将此项目关联了 %s";
    protected $status_frozen = "进行了撤单,撤单原因为%s";
    protected $add_privacy = "对该%s进行了隐私设置";
    protected $create_contracts = "创建了合同";
    protected $approval_agree = "同意了该审批";
    protected $approval_refuse = "拒绝了该审批";
    protected $approval_transfer = "转交审批给%s";
    protected $approval_cancel = "撤销了该审批";
    protected $approval_discard = "作废了该审批";
    protected $allot = "将销售线索分配给了%s";
    protected $recevie = "领取了销售线索";
    protected $return_trail = "退回了销售线索,原因是:%s";
    protected $create_star_schedules = "创建了日程%s";

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
            if ($operate->obj instanceof Task) {
                $type = ModuleableType::TASK;
                $typeName = '任务';
            } else if ($operate->obj instanceof Project) {
                $type = ModuleableType::PROJECT;
                $this->implodeModel = ProjectImplode::find($operate->obj->id);
                $typeName = '项目';
            } else if ($operate->obj instanceof Star) {
                $type = ModuleableType::STAR;
                $this->implodeModel = $operate->obj;
                $typeName = '艺人';
            } else if ($operate->obj instanceof Blogger) {
                $type = ModuleableType::BLOGGER;
                $this->implodeModel = $operate->obj;
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
                $type = ModuleableType::PROJECTBILLSRESOURCE;
                $typeName = '分组';
            }else if($operate->obj instanceof Role) {
                $type = ModuleableType::ROLE;
                $typeName = '角色';
            }else if($operate->obj instanceof Production){
                $type = ModuleableType::PRODUCTION;
                $typeName = "做品库";
            }else if($operate->obj instanceof Contract){
                $type = ModuleableType::CONTRACT;
                $typeName = "合同";
            }else if($operate->obj instanceof Instance){
                $type = ModuleableType::INSTANCE;
                $typeName = "一般审批审批";
                $operate->obj->id = $operate->obj->form_instance_id;
            }else if ($operate->obj instanceof Business){
                if ($operate->obj->busubess_type == "projects"){
                    $typeName = "项目审批";
                }else{
                    $typeName = "合同审批";
                }
                $type = ModuleableType::BUSINESS;
            }
            //TODO
            $id = $operate->obj->id;
            $title = $operate->title;
            $start = $operate->start;
            $end = $operate->end;
            $field_name = $operate->field_name;
            $level = 0;
            $content = null;
            switch ($operate->method) {
                case OperateLogMethod::CREATE://创建
                    if ($this->implodeModel == null) {
                        $this->implodeModel->last_follow_up_user_id = $user->id;
                        $this->implodeModel->last_follow_up_user = $user->name;
                        $this->implodeModel->last_follow_up_at = Carbon::now()->toDateTimeString();
                        $this->implodeModel->last_updated_user_id = $user->id;
                        $this->implodeModel->last_updated_user = $user->name;
                        $this->implodeModel->last_updated_at = Carbon::now()->toDateTimeString();
                        $this->implodeModel->save();
                    }
                    $level = OperateLogLevel::LOW;
                    $content = $this->create . '' . $typeName;
                    break;
                case OperateLogMethod::UPDATE://修改
                    if ($this->implodeModel == null) {
                        $this->implodeModel->last_updated_user_id = $user->id;
                        $this->implodeModel->last_updated_user = $user->name;
                        $this->implodeModel->last_updated_at = Carbon::now()->toDateTimeString();
                        $this->implodeModel->save();
                    }
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
                    } else if ($start && !$end){
                        $content = sprintf($this->update_field, $title, $start,$end);
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
                    if ($this->implodeModel == null){
                        $this->implodeModel->last_follow_up_user_id = $user->id;
                        $this->implodeModel->last_follow_up_user = $user->name;
                        $this->implodeModel->last_follow_up_at = Carbon::now()->toDateTimeString();
                        $this->implodeModel->save();
                    }
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
                    $content = sprintf($this->add_work,$title);;
                    break;
                case OperateLogMethod::ADD_TASK_RESOURCE: //为艺人添加任务
                    $level = OperateLogLevel::LOW;
                    $content = sprintf($this->add_resource_task,$typeName);
                    break;
                case OperateLogMethod::CREATE_SIGNING_CONTRACTS: //创建签约合同
                    $level = OperateLogLevel::LOW;
                    $content = sprintf($this->create_signing_contracts,$title);
                    break;
                case OperateLogMethod::CREATE_RESCISSION_CONTRACTS: //创建解约合同
                    $level = OperateLogLevel::LOW;
                    $content = sprintf($this->create_rescission_contracts,$title);
                    break;
                case OperateLogMethod::ADD_PRODUCTION://为博主添加做品库
                    $level = OperateLogLevel::LOW;
                    $content = sprintf($this->add_production,$title);
                    break;
//                case OperateLogMethod::ADD_TRAIL_TASK://为销售线索创建任务
//                    $level = OperateLogLevel::LOW;
//                    $content = sprintf($this->add_trail_task);
//                    break;
//                case OperateLogMethod::ADD_CLIENT_TASK://为客户创建任务
//                    $level = OperateLogLevel::LOW;
//                    $content = sprintf($this->add_client_task);
//                    break;
//
                case OperateLogMethod::ADD_CLIENT_CONTRACTS://为客户创建联系人
                    $level = OperateLogLevel::LOW;
                    $content = sprintf($this->add_client_contracts);
                    break;
                case OperateLogMethod::ADD_RELATE://为项目创建关联项目或任务
                    $level = OperateLogLevel::LOW;
                    $content = sprintf($this->add_relate,$start);
                    break;
                case OperateLogMethod::STATUS_FROZEN://撤单
                    $level = OperateLogLevel::LOW;
                    $content = sprintf($this->status_frozen,$start);
                    break;
                case OperateLogMethod::ADD_PRIVACY://进行隐私设置
                    $level = OperateLogLevel::LOW;
                    $content = sprintf($this->add_privacy,$typeName);
                    break;
                case OperateLogMethod::CREATE_CONTRACTS://创建合同
                    $level = OperateLogLevel::LOW;
                    $content = $this->create_contracts;
                    break;
                case OperateLogMethod::APPROVAL_AGREE://审批同意
                    $level = OperateLogLevel::HIGH;
                    $content = $this->approval_agree;
                    break;
                case OperateLogMethod::APPROVAL_REFUSE://拒接审批
                    $level = OperateLogLevel::HIGH;
                    $content = $this->approval_refuse;
                    break;
                case OperateLogMethod::APPROVAL_TRANSFER://转交审批
                    $level = OperateLogLevel::HIGH;
                    $content = sprintf($this->approval_refuse,$title);
                    break;
                case OperateLogMethod::APPROVAL_CANCEL://撤销审批
                    $level = OperateLogLevel::HIGH;
                    $content = $this->approval_cancel;
                    break;
                case OperateLogMethod::APPROVAL_DISCARD://作废
                    $level = OperateLogLevel::HIGH;
                    $content = $this->approval_discard;
                    break;
                case OperateLogMethod::ALLOT://分配销售线索
                    $level = OperateLogLevel::HIGH;
                    $content = sprintf($this->allot,$title);
                    break;
                case OperateLogMethod::RECEIVE://领取销售线索
                    $level = OperateLogLevel::HIGH;
                    $content = $this->recevie;
                    break;
                case OperateLogMethod::REFUND_TRAIL://退回销售线索
                    $level = OperateLogLevel::HIGH;
                    $content = sprintf($this->return_trail,$title);
                    break;
                case OperateLogMethod::CREATE_STAR_SCHEDULE://创建艺人日程
                    $level = OperateLogLevel::HIGH;
                    $content = sprintf($this->create_star_schedules,$title);
                    break;
                case OperateLogMethod::TASK_TO_SECRET://任务转私密，转公开
                    $level = OperateLogLevel::HIGH;
                    $content = $title;
                    break;

            }
            dispatch(new RecordOperateLog([
                'user_id' => $user->id,
                'logable_id' => $id,
                'logable_type' => $type,
                'content' => $content,
                'method' => $operate->method,
                'level' => $level,
                'status' => 1,
                'field_name'    =>$field_name,
                'field_title' =>  $title
            ]))->delay(Carbon::now()->addMinutes(10))->onQueue("record:operatelog");
//            OperateLog::create([
//                'user_id' => $user->id,
//                'logable_id' => $id,
//                'logable_type' => $type,
//                'content' => $content,
//                'method' => $operate->method,
//                'level' => $level,
//                'status' => 1,
//                'field_name'    =>$field_name,
//                'field_title' =>  $title
//            ]);

        }
    }
}
