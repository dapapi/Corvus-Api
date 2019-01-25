<?php

namespace App\Listeners;

use App\Events\CalendarMessageEvent;
use App\Repositories\MessageRepository;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;

class CalendarMessageEventListener
{

    private $messageRepository;//消息仓库
    private $task;//任务model
    private $trigger_point;//触发点
    private $authorization;//token
    private $user;//发送消息用户
    private $data;//向用户发送的消息内容
    //消息发送内容
    private $message_content = '[{"title":"任务名称","value":"%s"},{"title":"负责人","value":"%s"}]';
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
     * @param  CalendarMessageEvent  $event
     * @return void
     */
    public function handle(CalendarMessageEvent $event)
    {
        $this->task = $event->model;
        $this->trigger_point = $event->trigger_point;
        $this->authorization = $event->authorization;
        $this->user = $event->user;
    }
}
