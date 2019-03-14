<?php

namespace App\Listeners;

use App\Console\Commands\TriggerPoint;
use App\Events\TrailMessageEvent;
use App\Models\Department;
use App\Models\Message;
use App\Repositories\DepartmentRepository;
use App\Repositories\MessageRepository;
use App\TriggerPoint\TrailTrigreePoint;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\Log;

class TrailMessageEventListener
{

    private $messageRepository;//消息仓库
    private $departmentRepository;//部门仓库
    private $trail;//任务model
    private $trigger_point;//触发点
    private $authorization;//token
    private $user;//发送消息用户
    private $data;//向用户发送的消息内容
    private $meta;
    //消息发送内容
//    private $message_content = '[{"title":"线索名称","value":"%s"},{"title":"最后跟进时间","value":"%s"}]';

    /**
     * Create the event listener.
     *
     * @return void
     */
    public function __construct(MessageRepository $messageRepository,DepartmentRepository $departmentRepository)
    {
        $this->messageRepository = $messageRepository;
        $this->departmentRepository = $departmentRepository;
    }

    /**
     * Handle the event.
     *
     * @param  TrailMessageEvent  $event
     * @return void
     */
    public function handle(TrailMessageEvent $event)
    {
        $this->trail = $event->model;
        $this->trigger_point = $event->trigger_point;
        $this->authorization = $event->authorization;
        $this->user = $event->user;
        $this->meta = $event->meta;
        switch ($this->trigger_point){
            case TrailTrigreePoint::REMIND_TRAIL_TO_SEAS://当线索即将进入公海池是发消息
                $this->sendMessageWhenTrailWhileToSeas();
                break;
            case TrailTrigreePoint::LOCK_PRICE://当销售线索锁价时发送消息，目前只有papi商务组能锁价
                $this->sendMessageWhenLockPrice();
                break;
        }
    }
    //当线索即将进入公海池是发消息
    public function sendMessageWhenTrailWhileToSeas()
    {
        $subheading = $title = "您负责的{$this->trail->title}线索即将进入公海池";
        $message_content = '[{"title":"线索名称","value":"%s"},{"title":"最后跟进时间","value":"%s"}]';
        $this->data = json_decode(sprintf(message_content,$this->trail->title,$this->trail->last_updated_at),true);
        $send_to[] = $this->trail->principal_id;
        Log::info("线索【{$this->trail->title}】进入公海池向".implode(",",$send_to)."发送消息");
        $this->sendMessage($title,$subheading,$send_to);
    }

    /**
     * 当销售线索锁价时发送消息
     * @author lile
     * @date 2019-03-14 10:15
     */
    public function sendMessageWhenLockPrice()
    {
        $subheading = $title = $this->trail->title." 锁价金额为".$this->trail->fee.'元';
        $message_content = '[{"title":"线索名称","value":"%s"},{"title":"预计订单费用","value":"%s"}]';
        $this->data = json_decode(sprintf($message_content,$this->trail->title,$this->trail->fee),true);
        $send_to = $this->departmentRepository->getUsersByDepartmentId(Department::BUSINESS_DEPARTMENT);
        $this->sendMessage($title,$subheading,$send_to);
    }
    //最终发送消息方法调用
    public function sendMessage($title,$subheading,$send_to)
    {
        //消息接受人去重
        $send_to = array_unique($send_to);
        $send_to = array_filter($send_to);//过滤函数没有写回调默认去除值为false的项目
        $this->messageRepository->addMessage($this->user, $this->authorization, $title, $subheading,
            Message::TRAILS, null, $this->data, $send_to,$this->trail->id);
    }
}
