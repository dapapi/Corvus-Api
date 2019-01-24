<?php

namespace App\Listeners;

use App\Events\MessageEvent;
use App\Models\Task;
use App\TriggerPoint;
use App\User;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;

class MessageEventListener
{
    /**
     * Create the event listener.
     *
     * @return void
     */
    public function __construct()
    {
        //
    }
    private $send_message_to_principal = '{"title":"任务名称","value":"%s","title":"负责人","value":"%s"}';

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
        if ($model instanceof Task){
            switch ($trigger_point){
                case TriggerPoint::CRATE_TASK:
                    //获取负责人
                    $principal = User::find($model->principal_id);
                    $principal_name = $principal == null? null:$principal->name;
                    $message = sprintf($this->send_message_to_principal,$model->title,$principal_name);
                    $this->createMessage($model->title,$message,[$model->principal_id]);


            }
        }

    }

    public function createMessage($title,$message,$send_to)
    {
        //发送消息
        DB::beginTransaction();
        try {

            $user = Auth::guard('api')->user();

            $title = $user->name . $title;  //通知消息的标题
            $subheading = $user->name . $message;
            $module = Message::TASK;

            $data = json_decode($message,true);

            $recives[] = $task->creator_id;//创建人
            $recives[] = $task->principal_id;//负责人
            $authorization = $request->header()['authorization'][0];
            (new MessageRepository())->addMessage($user, $authorization, $title, $subheading, $module, $link, $data, $recives,$task->id);
            DB::commit();
        }catch (Exception $e){
            DB::rollBack();
            Log::error($e);
        }
    }

}
