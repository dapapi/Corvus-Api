<?php

namespace App\Listeners;

use App\Events\StarMessageEvent;
use App\Models\Message;
use App\Models\Star;
use App\Repositories\MessageRepository;
use App\TriggerPoint\StarTriggerPoint;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;

class StarMessageEventListener
{
    private $messageRepository;
    private $star_arr;//任务model
    private $trigger_point;//触发点
    private $authorization;//token
    private $user;//发送消息用户
    private $data;//向用户发送的消息内容
    //消息发送内容
    private $message_content = '[{"title":"艺人名称","value":"%s"},{"title":"签约时间","value":"%s"}]';
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
     * @param  StarMessageEvent  $event
     * @return void
     */
    public function handle(StarMessageEvent $event)
    {
        $this->star_arr = $event->star_arr;
        $this->trigger_point = $event->trigger_point;
        $this->authorization = $event->authorization;
        $this->user = $event->user;
        $created_at = $event->meta['created'];
        $stars = Star::whereIn('id',$this->star_arr)->select('name')->get()->toArray();
        $star_names = implode(",",array_column($stars,'name'));
        $this->data = json_decode(sprintf($this->message_content,$star_names,$created_at),true);
        switch ($this->trigger_point){
            case StarTriggerPoint::SIGNING://签约
                $this->sendMessageWhenSigning();
                break;
            case StarTriggerPoint::RESCISSION://解约
                $this->sendMessageWhenRescission();
                break;
        }
    }

    /**
     * 艺人签约通过时向全员发消息
     */
    public function sendMessageWhenSigning()
    {
        //获取全部艺人
        $star_name_arr = array_column(Star::select("name")->get()->toArray(),"name");
        $star_names = implode(",",$star_name_arr);
        $subheading = $title = $star_names."签约";
        $send_to = null;//全员
        $this->sendMessage($title,$subheading,$send_to);
    }

    /**
     * 艺人解约时向全员发消息
     */
    public function sendMessageWhenRescission()
    {
        //获取全部艺人
        $star_name_arr = array_column(Star::select("name")->get()->toArray(),"name");
        $star_names = implode(",",$star_name_arr);
        $subheading = $title = $star_names->name."解约";
        $send_to = null;//全员
        $this->sendMessage($title,$subheading,$send_to);
    }


    //最终发送消息方法调用
    public function sendMessage($title,$subheading,$send_to)
    {
        //消息接受人去重
        if ($send_to !== null){
            $send_to = array_unique($send_to);
            $send_to = array_filter($send_to);//过滤函数没有写回调默认去除值为false的项目
        }

        $this->messageRepository->addMessage($this->user, $this->authorization, $title, $subheading,
            Message::STAR, null, $this->data, $send_to,$this->task->id);
    }
}
