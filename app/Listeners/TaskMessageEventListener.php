<?php

namespace App\Listeners;

use App\Events\TaskMessageEvent;
use App\Models\Task;
use App\Repositories\MessageRepository;
use App\TriggerPoint\TaskTriggerPoint;
use App\User;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;

class TaskMessageEventListener
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
     * @param  MessageEvent  $event
     * @return void
     */
    public function handle(TaskMessageEvent $event)
    {
        $this->task = $event->model;
        $this->trigger_point = $event->trigger_point;
        $this->authorization = $event->authorization;
        $this->user = $event->user;
        //获取负责人名称
        $principal = User::find($this->task->principal_id);
        $principal_name = $principal == null ? null : $principal->name;
        $this->data = json_decode(sprintf($this->message_content,$this->task->title,$principal_name),true);
        //根据触发点判断是创建任务还是完成任务
        switch ($this->trigger_point){
            case TaskTriggerPoint::CRATE_TASK: //创建任务
                $this->sendMessageWhenCreate();
                break;
            case TaskTriggerPoint::COMPLETE_TSAK: //完成任务
                $this->sendMessageWhenComplete();
                break;
        }
    }

    /**
     * 创建任务时发送消息
     */
    public function sendMessageWhenCreate()
    {
        //判断任务是顶级任务还是子任务
        if ($this->task->task_pid){//子任务
            //向负责人发消息
            $this->createSonTaskSendMessageToPrincipal();
            //向子任务参与人，父任务创建人，参与人，负责人发消息
            $this->createSonTaskSendMessageToWhithOutPrincipal();
        }else{//父任务
            //创建父任务时向负责人发消息
            $this->createTopTaskSendMessageToPrincipal();
            //向参与人发消息
            $this->createTopTaskSendMessageToParticipants();

        }
    }
    public function sendMessageWhenComplete()
    {
        //判断任务是顶级任务还是子任务
        if ($this->task->task_pid) {//子任务
            $this->sendMessageWhenTopTaskComplete();
        }else{
            $this->sendMessageWhenSonTaskComplete();
        }
    }

    /**
     * 完成父任务时发消息
     */
    public function sendMessageWhenTopTaskComplete()
    {
        $subheading = $title = $this->user->name."完成了任务";
        $send_to[] = $this->task->creator_id;//创建人
        $send_to[] = $this->task->principal_id;//负责人
        //参与人
        $participants = array_column($this->task->participants()->select('user_id')->toArray(),'user_id');
        $send_to = array_merge($participants,$send_to);
        $this->sendMessage($title,$subheading,$send_to);
    }
    /**
     * 完成子任务时发消息
     */
    public function sendMessageWhenSonTaskComplete()
    {
        //获取父任务
        $pTask = Task::find($this->task->task_pid);
        $pTaskTitle = $pTask == null ? null : $pTask->title;//父任务名称
        $subheading = $title = $this->user->name."完成了子任务(父任务{$pTaskTitle})";
        //子任务参与人
        $send_to[] = array_column($this->task->participants()->select('user_id')->toArray(),'user_id');
        //父任务创建人
        $send_to[] = $pTask == null ? null : $pTask->creator_id;
        //父任务负责人
        $send_to[] = $pTask == null ? null : $pTask->principal_id;
        //父任务参与人
        $pTaskParticipants = $pTask == null ? [] : array_column($pTask->participants()->select('user_id')->toArray(),'user_id');
        $send_to = array_merge($send_to,$pTaskParticipants);//合并参与人数组
        $this->sendMessage($title,$subheading,$send_to);

    }

    //创建顶级任务，向负责人发消息
    public function createTopTaskSendMessageToPrincipal()
    {
        $subheading = $title = $this->user->name."给你分配了任务";
        $send_to[] = $this->task->principal_id;
        $this->sendMessage($title,$subheading,$send_to);
    }
    //创建顶级任务时向参与人发消息
    public function createTopTaskSendMessageToParticipants()
    {
        $subheading = $title = $this->user->name."邀请你参加任务";
        //任务参与人
        $send_to = array_column($this->task->participants()->select('user_id')->toArray(),'user_id');
        $this->sendMessage($title,$subheading,$send_to);
    }
    //创建子任务时向负责人发消息
    public function createSonTaskSendMessageToPrincipal()
    {
        //获取父任务名称
        $pTask = Task::find($this->task->task_pid);
        $pTaskTitle = $pTask == null ? null : $pTask->title;
        $subheading = $title = $this->user->name."给你分配了任务(父任务{$pTaskTitle})";
        $send_to[] = $this->task->principal_id;
        $this->sendMessage($title,$subheading,$send_to);
    }
    //创建子任务时向主任务创建人，负责人，参与人，子任务参与人发消息
    public function createSonTaskSendMessageToWhithOutPrincipal()
    {
        //获取父任务
        $pTask = Task::find($this->task->task_pid);
        $pTaskTitle = $pTask == null ? null : $pTask->title;//父任务名称
        $subheading = $title = $this->user->name."创建了子任务(父任务{$pTaskTitle})";
        //子任务参与人
        $send_to[] = array_column($this->task->participants()->select('user_id')->toArray(),'user_id');
        //父任务创建人
        $send_to[] = $pTask == null ? null : $pTask->creator_id;
        //父任务负责人
        $send_to[] = $pTask == null ? null : $pTask->principal_id;
        //父任务参与人
        $pTaskParticipants = $pTask == null ? [] : array_column($pTask->participants()->select('user_id')->toArray(),'user_id');
        $send_to = array_merge($send_to,$pTaskParticipants);//合并参与人数组
        $this->sendMessage($title,$subheading,$send_to);
    }
    //最终发送消息方法调用
    public function sendMessage($title,$subheading,$send_to)
    {
        //消息接受人去重
        $send_to = array_unique($send_to);
        $send_to = array_filter($send_to);//过滤函数没有写回调默认去除值为false的项目
        $this->messageRepository->addMessage($this->user, $this->authorization, $title, $subheading,
            $this->task, null, $this->data, $send_to,$this->task->id);
    }
}
