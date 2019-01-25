<?php

namespace App\Listeners;

use App\Events\ClientMessageEvent;
use App\Models\Department;
use App\Models\Message;
use App\Repositories\DepartmentRepository;
use App\Repositories\MessageRepository;
use App\TriggerPoint\ClientTriggerPoint;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;

class ClientMessageEventListener
{
    private $messageRepository;
    private $departmentRepository;
    private $client;//任务model
    private $trigger_point;//触发点
    private $authorization;//token
    private $user;//发送消息用户
    private $data;//向用户发送的消息内容
    //消息发送内容
    private $message_content = '[{"title":"客户名称","value":"%s"},{"title":"保护截止日期","value":"%s"}]';
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
     * @param  ClientMessageEvent  $event
     * @return void
     */
    public function handle(ClientMessageEvent $event)
    {
        $this->client = $event->model;
        $this->trigger_point = $event->trigger_point;
        $this->authorization = $event->authorization;
        $this->user = $event->user;
        $this->data = json_decode(sprintf($this->message_content,$this->client->company),true);
        switch ($this->trigger_point){
            case ClientTriggerPoint::CREATE_NEW_GRADE_NORMAL://新增直客

                break;
            case ClientTriggerPoint::GRADE_NORMAL_ORDER_FORM://直客成单
                break;
        }
    }

    /**
     * 直客新增时向papi商务组全部同事
     */
    public function sendMessageWhenCreateNewGradeNormal()
    {
        $subheading = $title = $this->client->company."开启了直客保护";
        //papi商务组全体同事
        $send_to = $this->departmentRepository->getUsersByDepartmentId(Department::BUSINESS_DEPARTMENT);
        $this->sendMessage($title,$subheading,$send_to);
    }

    /**
     * 直客成单时向papi商务组全部同事
     */
    public function sendMessageWhenGradeNormalOrderForm()
    {
        $subheading = $title = $this->client->company."直客成单了";
        //papi商务组全体同事
        $send_to = $this->departmentRepository->getUsersByDepartmentId(Department::BUSINESS_DEPARTMENT);
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
            Message::CLIENT, null, $this->data, $send_to,$this->client->id);
    }
}
