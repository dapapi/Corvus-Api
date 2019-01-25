<?php

namespace App\Listeners;

use App\Events\ApprovalMessageEvent;
use App\Models\ApprovalForm\ApprovalForm;
use App\Models\ApprovalForm\Business;
use App\Models\ApprovalForm\Instance;
use App\Models\ApprovalForm\Participant;
use App\Models\Message;
use App\Repositories\MessageRepository;
use App\TrigerPoint\ApprovalTriggerPoint;
use App\User;
use Carbon\Carbon;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\App;

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
    //消息发送内容
    private $message_content = '[{"title":"发起人","value":"%s"},{"title":"提交人","value":"%s"},{"title":"提交时间","value":%s}]';
    /**
     * Create the event listener.
     *
     * @return void
     */
    public function __construct(MessageRepository $messageRepository)
    {
        $this->messageRepository = $messageRepository;
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
        //获取发起人姓名
        $this->origin = User::find($this->instance->created_by);
        $origin_name = $this->origin == null ? null : $this->origin->name;//发起人，提交人
        //获取审批的名字
        $form = ApprovalForm::find($this->instance->form_id);
        $this->form_name = $form == null ? null : $form->name;
        $this->data = sprintf($this->message_content,$origin_name,$origin_name,$this->instance->created_at);
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
                break;
        }
    }

    /**
     * 当审批同意时向审批发起人发消息
     */
    public function sendMessageWhenAgree()
    {
        $subheading = $title = "您的{$this->form_name}已同意";
        $send_to[] = $this->instance->created_by;//发起人
        $this->sendMessage($title,$subheading,$send_to);
    }

    /**
     * 当审批同意时向审批发起人发消息
     */
    public function sendMessageWhenRefuse()
    {
        $subheading = $title = "您的{$this->form_name}已拒绝";
        $send_to[] = $this->instance->created_by;//发起人
        $this->sendMessage($title,$subheading,$send_to);
    }
    /**
     * 当审批转交是发送消息
     */
    public function sendMessageWhenTransfer()
    {
        //转交人
        $other_user = User::find($this->other_id);
        $other_user_name = $other_user == null ? null : $other_user->name;
        $origin_name = $this->origin == null ? null : $this->origin->name;
        $subheading = $title = $other_user_name."转交你审批{$origin_name}"."的".$this->form_name;
        $send_to[] = $this->other_id;//被转交人
        $this->sendMessage($title,$subheading,$send_to);
    }
    //待审批
    public function sendMessageWhenWaitMe()
    {
        $subheading = $title = $this->user->name."的".$this->form_name."待您审批";
        $send_to[] = $this->other_id;//向下一个审批人发消息
        $this->sendMessage($title,$subheading,$send_to);
    }
    //向知会人发消息
    public function sendMessageWhenNotify()
    {
        $origin_name = $this->origin == null ? null : $this->origin->name;
        $subheading = $title = $this->user->name."知会你".$origin_name."的".$this->form_name;
        //todo 可能会根据角色发消息
        //获取知会人
        $send_to = array_column(Participant::select("notice_id")->find($this->instance->form_instance_number)->get()->toArray(),"notice_id");
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
        $this->messageRepository->addMessage($this->user, $this->authorization, $title, $subheading,
            Message::APPROVAL, null, $this->data, $send_to,$this->task->id);
    }

}
