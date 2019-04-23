<?php

namespace App\Listeners;

use App\Events\AnnouncementMessageEvent;

use App\Helper\Common;

use App\Models\DepartmentUser;
use App\Models\Message;
use App\Models\Project;
use App\Repositories\AnnouncementRepository;
use App\Repositories\MessageRepository;
use App\TriggerPoint\AnnouncementTriggerPoint;
use App\TriggerPoint\ApprovalTriggerPoint;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;

class AnnouncementMessageEventListener
{

    private $messageRepository;//消息仓库
    private $announcementRepository;
    private $deparment;
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

    private $umeng_description;
    /**
     * Create the event listener.
     *
     * @return void
     */

    public function __construct(MessageRepository $messageRepository,AnnouncementRepository $announcementRepository)
    {
        $this->messageRepository = $messageRepository;
        $this->announcementRepository = $announcementRepository;
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
        $this->data = json_decode(sprintf($this->message_content,$this->instance->title,$this->user->name,$this->instance->creatd_at),true);
        switch ($event->trigger_point){
            case AnnouncementTriggerPoint::CREATE:
                $this->sendMessageWhenCreate();
                break;
            case AnnouncementTriggerPoint::UPDATE:
                $this->sendMessageWhenUpdate();
                break;
            case AnnouncementTriggerPoint::DELETE:
                $this->sendMessageWhenDelete();
                break;
        }
    }
    public function sendMessageWhenCreate()
    {
        //获取所有要接受消息的用户
//        $this->deparments = explode(",",$this->instance->scope);
        $send_to = $this->announcementRepository->getAllUserThatCanSeeTheAnnouncement($this->instance);
//        $send_to = DepartmentUser::whereIn('department_id', $this->deparments)->select('user_id')->get();
//        $send_to = array_column($send_to,"user_id");
        $subheading = $title = $this->user->name."发布了新公告";
        $this->umeng_description = "发布公告";
        $this->sendMessage($title,$subheading,$send_to);

    }

    public function sendMessageWhenUpdate()
    {
        $send_to = $this->announcementRepository->getAllUserThatCanSeeTheAnnouncement($this->instance);
        $subheading = $title = $this->user->name."更新了新公告";
        $this->umeng_description = "修改公告";
        $this->sendMessage($title,$subheading,$send_to);
    }
    public function sendMessageWhenDelete()
    {
        $send_to = $this->announcementRepository->getAllUserThatCanSeeTheAnnouncement($this->instance);
        $subheading = $title = $this->user->name."删除了新公告";
        $this->umeng_description="删除公告";
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
            Message::ANNOUNCENMENT, null, $this->data, $send_to,$this->instance->id);
        $umeng_text = "公告名称:".$this->instance->title;
//        $this->umengRepository->sendMsgToMobile($send_to,"公告管理助手",$title,$umeng_text,Message::ANNOUNCENMENT,hashid_encode($this->instance->id));
        $job = new SendUmengMsgToMobile([
            'send_to' => Common::unsetArrayValue($send_to,$this->user->id),
            'title' => $title,
            'tricker' => "公告管理助手",
            'text' => $this->umeng_text,
            'description'   => $this->umeng_description,
            'module' => Message::ANNOUNCENMENT,
            'module_data_id' => hashid_encode($this->instance->id),
        ]);
        dispatch($job)->onQueue("umeng_message");
    }




}
