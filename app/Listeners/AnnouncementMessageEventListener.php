<?php

namespace App\Listeners;

use App\Events\AnnouncementMessageEvent;
use App\Models\DepartmentUser;
use App\Models\Message;
use App\Repositories\MessageRepository;
use App\TriggerPoint\AnnouncementTriggerPoint;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;

class AnnouncementMessageEventListener
{

    private $messageRepository;//消息仓库
    private $instance;//公告model
    private $trigger_point;//触发点
    private $authorization;//token
    private $user;//发送消息用户
    private $data;//向用户发送的消息内容
    private $form_name;//审批单的名字
    private $other_id; //转交时他是转交人id
    private $origin;//发起人
    private $module;//消息模块
    private $creator_id;
    private $deparments;//接受消息的部门
    private $message_content = '[{"title":"公告","value":"%s"},{"title":"发布人","value":"%s"},{"title":"发布时间","value":"%s"}]';

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
     * @param  AnnouncementMessageEvent  $event
     * @return void
     */
    public function handle(AnnouncementMessageEvent $event)
    {
        $this->deparments = $event->meta;
        $this->module = Message::ANNOUNCENMENT;
        $this->user = $event->user;
        $this->instance = $event->model;
        $this->data = json_decode(sprintf($this->message_content,$this->instance->title,$this->user->name,$this->instance->create_at),true);
        switch ($event->trigger_point){
            case AnnouncementTriggerPoint::CREATE:
                $this->sendMessageWhenCreate();
                break;
            case AnnouncementTriggerPoint::UPDATE:
                $this->sendMessageWhenCreate();
                break;
            case AnnouncementTriggerPoint::DELETE:
                $this->sendMessageWhenCreate();
                break;
        }
    }
    public function sendMessageWhenCreate()
    {
        //获取所有要接受消息的用户
        $this->deparments = explode(",",$this->instance->scope);
        $send_to = DepartmentUser::whereIn('department_id', explode(",",$this->deparments))->select('user_id');
        $subheading = $title = $this->user->name."发布了新公告";
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
    }

}
