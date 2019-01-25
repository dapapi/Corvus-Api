<?php

namespace App\Listeners;

use App\Events\MessageEvent;
use App\Models\Message;
use App\Models\Star;
use App\Models\Task;
use App\Repositories\MessageRepository;
use App\TriggerPoint;
use App\User;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class MessageEventListener
{
    private $messageRepository;
    private $send_message_to_principal = '[{"title":"任务名称","value":"%s"},{"title":"负责人","value":"%s"}]';
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
     * @param  MessageEvent  $event
     * @return void
     */
    public function handle(MessageEvent $event)
    {
        $model = $event->model;
        $trigger_point = $event->trigger_point;
        $authorization = $event->authorization;
        $user = Auth::guard('api')->user();
        if ($model instanceof Task) {
            $this->sendTaskMessage($model, $trigger_point, $user, $authorization);
        }
    }

    /**
     * @param $title 消息标题
     * @param $message 消息体
     * @param $send_to 发送给  数组
     * @param $authorization token
     */
    public function sendTaskMessage($model,$trigger_point,$user,$authorization)
    {
        $module = Message::TASK;
        $send_to = null;
        $title = null;
        $principal = User::find($model->principal_id);
        $principal_name = $principal == null? null:$principal->name;
        $message = sprintf($this->send_message_to_principal,$model->title,$principal_name);
        $data = json_decode($message,true);
        switch ($trigger_point){
            case TriggerPoint::CRATE_TASK:
                if ($model->task_pid){
                    //给负责人发消息
                    //查找父任务
                    $pTask = Task::find($model->task_pid);
                    $p_task_name = $pTask == null ? null : $pTask->name;
                    $title = $user->name."给你分配了子任务(父任务:{$p_task_name})";
                    $send_to = [$model->principal_id];
                    $subheading =  $title;
                    $this->messageRepository->addMessage($user, $authorization, $title, $subheading,
                        $module, null, $data, $send_to,$model->id);
                    //向参与人发消息
                    //获取主任务的创建人，参与人，负责人
                    $send_to = null;
                    $send_to[] = $pTask->creator_id;
                    $send_to[] = $pTask->principal_id;
                    $send_to[] = array_merge($send_to,array_column($pTask->participants()->select('user_id')->toArray(),'user_id'));
                    //子任务的参与人，负责人
                    $send_to[] = $model->principal_id;
                    $send_to = array_merge($send_to,array_column($model->participants()->select('user_id')->toArray(),'user_id'));
                    $this->messageRepository->addMessage($user, $authorization, $title, $subheading,
                        $module, null, $data, $send_to,$model->id);


                }else{
                    //向负责人发消息
                    $title = $user->name."给你分配了任务";
                    $send_to = [$model->principal_id];
                    $subheading =  $title;
                    $this->messageRepository->addMessage($user, $authorization, $title, $subheading,
                        $module, null, $data, $send_to,$model->id);
                    //向参与人发消息
                    $send_to = null;
                    $send_to = array_column($model->participants()->select('user_id')->toArray(),'user_id');
                    $this->messageRepository->addMessage($user, $authorization, $title, $subheading,
                        $module, null, $data, $send_to,$model->id);
                }
                break;
            case TriggerPoint::COMPLETE_TSAK:
                $send_to[] = $model->principal_id;
                $send_to[] = $model->creator_id;
                $participants = array_column($model->participants()->select('user_id')->toArray(),'user_id');
                $send_to = array_merge($send_to,$participants);
                break;
        }
    }

}
