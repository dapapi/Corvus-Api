<?php

namespace App\Listeners;

use App\Events\ApprovalMessageEvent;
use App\Models\ApprovalFlow\Execute;
use App\Models\ApprovalForm\ApprovalForm;
use App\Models\ApprovalForm\Business;
use App\Models\ApprovalForm\Instance;
use App\Models\ApprovalForm\InstanceValue;
use App\Models\ApprovalForm\Participant;
use App\Models\Contact;
use App\Models\Contract;
use App\Models\Department;
use App\Models\DepartmentPrincipal;
use App\Models\DepartmentUser;
use App\Models\Message;
use App\Models\Project;
use App\Models\Role;
use App\Models\RoleUser;
use App\Repositories\MessageRepository;
use App\Repositories\UmengRepository;
use App\TriggerPoint\ApprovalTriggerPoint;
use App\User;
use Carbon\Carbon;
use Complex\Exception;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Log;
use Monolog\Handler\IFTTTHandler;

class ApprovalMessageEventListener
{
    private $messageRepository;//消息仓库
    private $instance;//任务model
    private $trigger_point;//触发点
    private $authorization;//token
    private $user;//发送消息用户
    private $data;//向用户发送的消息内容
    private $form_name;//审批单的名字
    private $other_id; //转交时他是转交人id
    private $origin;//发起人
    private $module;//消息模块
    private $creator_id;
    private $created_at;

    private $umeng_text;
    private $umengRepository;
    //消息发送内容
    private $message_content = '[{"title":"发起人","value":"%s"},{"title":"提交人","value":"%s"},{"title":"提交时间","value":"%s"}]';
    /**
     * Create the event listener.
     *
     * @return void
     */
    public function __construct(MessageRepository $messageRepository,UmengRepository $umengRepository)
    {
        $this->messageRepository = $messageRepository;
        $this->umengRepository = $umengRepository;
    }

    /**
     * Handle the event.
     *
     * @param  ApprovalMessageEvent  $event
     * @return void
     */
    public function handle(ApprovalMessageEvent $event)
    {
        $this->instance = $event->model;
        $this->trigger_point = $event->trigger_point;
        $this->authorization = $event->authorization;
        $this->user = $event->user;
        $this->other_id = $event->other_id;
        $create_at = null; //创建时间
        //获取发起人姓名，消息发送模块
        if ($this->instance->business_type == "projects"){
            $this->module = Message::PROJECT;
            $project = Project::where('project_number',$this->instance->form_instance_number)->first();
            if ($project){
                $this->creator_id = $project->creator_id;
                $create_at = $project->created_at;
            }
        }
        if ($this->instance->business_type == "contracts"){
            $this->module = Message::CONTRACT;
            $contract = Contract::where("form_instance_number",$this->instance->form_instance_number)->first();
            if ($contract){
                $this->creator_id = $contract->creator_id;
                $create_at = $contract->created_at;
            }
        }
        if ($this->instance->apply_id){
            $this->module = Message::APPROVAL;
            $this->creator_id = $this->instance->apply_id;
            $create_at = $this->instance->created_at;
        }
        if ($this->creator_id){
            $this->origin = User::find($this->creator_id);
        }
        $origin_name = $this->origin == null ? null : $this->origin->name;//发起人，提交人
        //获取审批的名字
        $form = ApprovalForm::where("form_id",$this->instance->form_id)->first();
        $this->form_name = $form == null ? null : $form->name;
        $this->created_at = $create_at;
        $this->data = json_decode(sprintf($this->message_content,$origin_name,$origin_name,$create_at),true);

        switch ($this->trigger_point){
            case ApprovalTriggerPoint::AGREE://审批同意
                $this->sendMessageWhenAgree();
                break;
            case ApprovalTriggerPoint::REFUSE://审批拒绝
                $this->sendMessageWhenRefuse();
                break;
            case ApprovalTriggerPoint::TRANSFER://转交
                $this->sendMessageWhenTransfer();
                break;
            case ApprovalTriggerPoint::WAIT_ME://待我审批
                $this->sendMessageWhenWaitMe();
                break;
            case ApprovalTriggerPoint::NOTIFY://知会我的
                $this->sendMessageWhenNotify();
                break;
            case ApprovalTriggerPoint::REMIND://提醒
                $this->sendMessageWhenProjectRemind();
                break;
            case ApprovalTriggerPoint::PROJECT_CONTRACT_AGREE://项目合同审批通过
                break;
        }
    }

    /**
     * 当审批同意时向审批发起人发消息
     */
    public function sendMessageWhenAgree()
    {
        $subheading = $title = "您的{$this->form_name}已同意";
        $send_to = [];
        try{
            $send_to[] = $this->getInstanceCreator();
        }catch (\Exception $e){
            Log::error($e);
        }
        $this->umeng_text = "提交时间:".$this->created_at;
        $this->sendMessage($title,$subheading,$send_to);
    }

    /**
     * 当审批拒绝时向审批发起人发消息
     */
    public function sendMessageWhenRefuse()
    {
        $subheading = $title = "您的{$this->form_name}已拒绝";
        $send_to = [];
        try{
            $send_to[] = $this->getInstanceCreator();
        }catch (\Exception $e){
            Log::error($e);
        }
        $this->umeng_text = "提交时间:".$this->created_at;
        $this->sendMessage($title,$subheading,$send_to);
    }
    /**
     * 当审批转交是发送消息
     */
    public function sendMessageWhenTransfer()
    {
        $origin_name = $this->origin == null ? null : $this->origin->name;
        $subheading = $title = $this->user->name."转交你审批{$origin_name}"."的".$this->form_name;
        $this->umeng_text = "审批类型:".$this->form_name;
        $send_to[] = $this->other_id;//被转交人
        $this->sendMessage($title,$subheading,$send_to);
    }
    //待审批
    public function sendMessageWhenWaitMe()
    {
//        //获取下一个审批人
//        $execute = Execute::where('form_instance_number',$this->instance->form_instance_number)->first();
//        $send_to = [];
//        if ($execute->current_handler_type == 245){//团队
//            $send_to[] = $execute->current_handler_id;
//        }elseif($execute->current_handler_type == 246){//创建人所在部门负责人
//            try{
//                //获取创建人
//                $creator_id = $this->getInstanceCreator();
//                $department_user = DepartmentUser::where("user_id",$creator_id)->first();
//                //获取部门负责人
//                $department_principal = DepartmentUser::where('department_id',$department_user->department_id)->where('type',1)->first();
//                $send_to[] = $department_principal == null ? $creator_id : $department_principal->user_id;
//            }catch (\Exception $e){
//                Log::error($e);
//            }
//        }elseif($execute->current_handler_type == 247){//角色
//            //获取角色
//            $users = RoleUser::where("role_id",$execute->current_handler_id)->select('user_id')->get()->toArray();
//            $send_to = array_column($users,'user_id');
//        }
        $send_to = $this->getNextApprovalUser();
//        dd($send_to);
        $creator = User::find($this->creator_id);
        $creator_name = $creator == null ? null : $creator->name;
        $subheading = $title = $creator_name."的".$this->form_name."待您审批";
        $this->umeng_text = "提交时间:".$this->created_at;
//        $send_to[] = $this->other_id;//向下一个审批人发消息
        $this->sendMessage($title,$subheading,$send_to);
    }
    //向知会人发消息
    public function sendMessageWhenNotify()
    {
        $origin_name = $this->origin == null ? null : $this->origin->name;
        $subheading = $title = $this->user->name."知会你".$origin_name."的".$this->form_name;
        $this->umeng_text = "提交时间:".$this->created_at;
        //todo 可能会根据角色发消息
        //获取知会人
        $send_to = array_column(Participant::select("notice_id")->where("form_instance_number",$this->instance->form_instance_number)->get()->toArray(),"notice_id");
        Log::info("向知会人发消息".implode(",",$send_to));
        $this->sendMessage($title,$subheading,$send_to);
        Log::info("发送完毕");
    }

    /**
     * 项目合同审批通过
     */
    public function sendMessageWhenProjectContractAgree()
    {
        if ($this->instance->business_type == "contracts"){
            $instance_value = InstanceValue::where("form_instance_number",$this->instance->form_instance_number)->where('form_control_id',36)->first();
            $department_name = $instance_value->form_control_value;
            //获取部门
            $department = Department::where('name',$department_name)->first();
            //查找部门下用户
            $department_users = DepartmentUser::where('depart_id',$department->id)->select("user_id")->get()->toArray();
            $send_to = array_column($department_users,"user_id");
            //获取项目
            $project = Project::join("contracts","contracts.project_id","projects.id")->where("form_instance_number",$this->instance->form_instance_number)->first();
            $subheading = $title = $project == null ? null :$project->title."项目成单了";
            $this->sendMessage($title,$subheading,$send_to);
        }

    }
    public function sendMessageWhenProjectRemind(){
        $send_to = $this->getNextApprovalUser();
        $creator = User::find($this->creator_id);
        $creator_name = $creator == null ? null : $creator->name;
        $title = $subheading = "$creator_name 提醒你审批 $creator_name 的 $this->form_name";
        $this->umeng_text = "提交时间:".$this->created_at;
        $this->sendMessage($title,$subheading,$send_to);
    }

    /**
     * @param $title
     * @param $subheading
     * @param $send_to
     */

    //最终发送消息方法调用
    public function sendMessage($title,$subheading,$send_to)
    {
        //消息接受人去重
        $send_to = array_unique($send_to);
        $send_to = array_filter($send_to);//过滤函数没有写回调默认去除值为false的项目
        $module_data_id = 0;
        if ($this->module == Message::CONTRACT || $this->module == Message::APPROVAL){
            $module_data_id = $this->instance->form_instance_number;
        }else{
            $project = Project::where('project_number',$this->instance->form_instance_number)->first();
            if ($project){
                $module_data_id = $project->id;
            }
        }
        if ($this->trigger_point == ApprovalTriggerPoint::NOTIFY){
            Log::info("消息函数向".implode(",",$send_to)."发消息");
        }
        $this->messageRepository->addMessage($this->user, $this->authorization, $title, $subheading,
            $this->module, null, $this->data, $send_to,$module_data_id);
        $this->umengRepository->sendMsgToMobile($send_to,"审批管理助手",$title,$this->umeng_text,$this->module,$module_data_id);

    }

    private function getInstanceCreator()
    {
        $creator_id = null;
        //获取发起人姓名
        if ($this->instance->business_type == "projects"){
            $project = Project::where('project_number',$this->instance->form_instance_number)->first();
            if ($project){
                return $project->creator_id;
            }
        }
        if ($this->instance->business_type == "contracts"){
            $contract = Contract::where("form_instance_number",$this->instance->form_instance_number)->first();
            if ($contract){
                return $contract->creator_id;
            }
        }
        if ($this->instance->apply_id){
            return $this->instance->apply_id;
        }
        throw new \Exception("查找不到创建人");
    }

    //获取下一个审批人
    public function getNextApprovalUser(){
        //获取下一个审批人
        $execute = Execute::where('form_instance_number',$this->instance->form_instance_number)->first();
        $send_to = [];
        if ($execute->current_handler_type == 245){//团队
            $send_to[] = $execute->current_handler_id;
        }elseif($execute->current_handler_type == 246){//创建人所在部门负责人
            try{
                //获取创建人
                $creator_id = $this->getInstanceCreator();
                //获取创建人所在部门
                $department_user = DepartmentUser::where("user_id",$creator_id)->first();
                //获取创建人的主管
                $department_principal = DepartmentPrincipal::where('department_id',$department_user->department_id)->first();
                //当前部门id
                $department_id = $department_user->department_id;
                //获取查找几级主管
                $principal_level = $execute->principal_level;
                //判断创建人是否是所在部门的主管,是部门主管查询部门的上级部门主管，不是查询创建人的主管
                if ($department_principal->user_id != $creator_id){
                     $principal_level = $principal_level -1;//查询上级部门减少一级
                }
                //获取要接收消息的部门主管
                for ($i=0;$i<$principal_level;$i++){
                        $department_id = Department::where('id',$department_id)->value('department_pid');
                }
                //获取主管
                $send_department_principal = DepartmentPrincipal::where('department_id',$department_id)->first();
                $send_to[] = $send_department_principal == null ? $creator_id : $send_department_principal->user_id;
            }catch (\Exception $e){
                Log::error($e);
            }
        }elseif($execute->current_handler_type == 247){//角色
            //获取角色
            $users = RoleUser::where("role_id",$execute->current_handler_id)->select('user_id')->get()->toArray();
            $send_to = array_column($users,'user_id');
        }
        return $send_to;
    }

    //获取部门的上级部门
    protected function getParentDepartment($department_id)
    {
        $department = Department::where('$department_id',$department_id)->first();
        return $department->pid;
    }

}
