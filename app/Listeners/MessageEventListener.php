<?php

namespace App\Listeners;

use App\Events\MessageEvent;
use App\Models\Message;
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
        $authorization = $event->authorization;
        if ($model instanceof Task){
            switch ($trigger_point){
                case TriggerPoint::CRATE_TASK:
                    $module = Message::TASK;
                    //获取负责人
                    $principal = User::find($model->principal_id);
                    $principal_name = $principal == null? null:$principal->name;
                    $message = sprintf($this->send_message_to_principal,$model->title,$principal_name);
                    $data_id = $model->id;
                    $this->createMessage($model->title,$message,[$model->principal_id],$authorization,$module,$data_id);


            }
        }

    }

    /**
     * @param $title 消息标题
     * @param $message 消息体
     * @param $send_to 发送给  数组
     * @param $authorization token
     */
    public function createMessage($title,$message,$send_to,$authorization,$module,$data_id)
    {

        //发送消息
        DB::beginTransaction();
        try {

            $user = Auth::guard('api')->user();

            $title = $user->name . $title;  //通知消息的标题
            $subheading = $user->name . $message;


            $data = json_decode($message,true);
            (new MessageRepository())->addMessage($user, $authorization, $title, $subheading, $module, null, $data, $send_to,$data_id);
            DB::commit();
        }catch (Exception $e){
            DB::rollBack();
            Log::error($e);
        }
    }

}
