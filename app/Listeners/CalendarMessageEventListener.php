<?php

namespace App\Listeners;

use App\Events\CalendarMessageEvent;
use App\Models\Calendar;
use App\Models\Message;
use App\Repositories\MessageRepository;
use App\Repositories\UmengRepository;
use App\TriggerPoint\CalendarTriggerPoint;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;

class CalendarMessageEventListener
{

    private $messageRepository;//消息仓库
    private $schedule;//日程model
    private $trigger_point;//触发点
    private $authorization;//token
    private $user;//发送消息用户
    private $data;//向用户发送的消息内容
    private $meta;
    private $umengRepository;
    //消息发送内容
    private $message_content = '[{"title":"日程标题","value":"%s"},{"title":"开始时间","value":"%s"},{"title":"结束时间","value":"%s"}]';
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
     * @param  CalendarMessageEvent  $event
     * @return void
     */
    public function handle(CalendarMessageEvent $event)
    {
        $this->schedule = $event->model;
        $this->trigger_point = $event->trigger_point;
        $this->authorization = $event->authorization;
        $this->user = $event->user;
        $this->meta = $event->meta;
        $this->data = json_decode(sprintf($this->message_content,$this->schedule->title,$this->schedule->start_at,$this->schedule->end_at),true);
        switch ($this->trigger_point){
            case CalendarTriggerPoint::CREATE_SCHEDULE://创建日程
                $this->sendMessageWhenCreateSchedule();
                break;
            case CalendarTriggerPoint::REMIND_SCHEDULE://日程提醒
                $this->sendMessageWhenRemindSchdule();
                break;
            case CalendarTriggerPoint::UPDATE_SCHEDULE://修改日程
                $this->sendMessageWhenUpdateSchedule();
                break;
        }
    }

    /**
     * 当创建日程时向参与人发消息
     */
    public function sendMessageWhenCreateSchedule()
    {
        $subheading = $title = $this->user->name."邀请你参与了日程";
        $send_to = array_column($this->schedule->participants()->select("user_id")->get()->toArray(),"user_id");

        $this->sendMessage($title,$subheading,$send_to);
    }

    /**
     * 日程提醒向参与者和创建者发消息
     */
    public function sendMessageWhenRemindSchdule()
    {
        $subheading = $title = "日程提醒";
        $send_to = array_column($this->schedule->participants()->select("user_id")->get()->toArray(),"user_id");
        $send_to[] = $this->schedule->creator_id;
        $this->sendMessage($title,$subheading,$send_to);
    }
    /**
     * 当修改日程时向参与人发消息
     */
    public function sendMessageWhenUpdateSchedule()
    {
        $send_to = array_column($this->schedule->participants()->select("user_id")->get()->toArray(),"user_id");
        //判断是否更改了会议室，时间，位置
        $old_schedule_arr = $this->meta['old_schedule']->toArray();
        $schedule_arr = $this->schedule->toArray();
        foreach ($old_schedule_arr as $key => $value){
            if ($key == "start_at" || $key == "end_at" || $key == "material_id" || $key == "position"){
                if ($key == "start_at" || $key == "end_at"){
                    $value = date('Y-m-d H:i:s',strtotime($value));
                    $schedule_arr[$key] = date('Y-m-d H:i:s',strtotime($schedule_arr[$key]));
                }
                if ($value != $schedule_arr[$key]){
                    $update_filed = "";
                    if ($key == "start_at")
                        $update_filed = "开始时间";
                    if ($key == "end_at")
                        $update_filed = "结束时间";
                    if ($key == "material_id")
                        $update_filed = "会议室";
                    if ($key == "position")
                        $update_filed = "位置";
                    $subheading = $title = $this->user->name."修改了($update_filed)";
                    $this->sendMessage($title,$subheading,$send_to);
                }
            }
        }
    }

    //最终发送消息方法调用
    public function sendMessage($title,$subheading,$send_to)
    {
        //消息接受人去重
        $send_to = array_unique($send_to);
        $send_to = array_filter($send_to);//过滤函数没有写回调默认去除值为false的项目
        $this->messageRepository->addMessage($this->user, $this->authorization, $title, $subheading,
            Message::CALENDAR, null, $this->data, $send_to,$this->schedule->id);
        $umeng_text = "日程名称:".$this->schedule->title;
        $this->umengRepository->sendMsgToMobile($send_to,"日程管理助手",$title,$umeng_text,Message::CALENDAR,hashid_encode($this->schedule->id));

    }
}
