<?php

namespace App\Listeners;

use App\Events\MessageEvent;
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

    /**
     * Handle the event.
     *
     * @param  MessageEvent  $event
     * @return void
     */
    public function handle(MessageEvent $event)
    {
        $model = $event->model;
        dd($model);

    }

//    public function createMessage()
//    {
//        //发送消息
//        DB::beginTransaction();
//        try {
//
//            $user = Auth::guard('api')->user();
//            $message = "";
//            switch ($status){
//                case TaskStatus::NORMAL:
//                    $message="任务状态转为正常";
//                    break;
//                case TaskStatus::COMPLETE:
//                    $message="任务完成";
//                    break;
//                case TaskStatus::TERMINATION:
//                    $message="任务终止";
//                    break;
//            }
//            $title = $user->name . $message;  //通知消息的标题
//            $subheading = $user->name . $message;
//            $module = Message::TASK;
//            $link = URL::action("TaskController@show", ["task" => $task->id]);
//            $data = [];
//            $data[] = [
//                "title" => '任务名称', //通知消息中的消息内容标题
//                'value' => $task->title,  //通知消息内容对应的值
//            ];
//            $principal = User::findOrFail($task->principal_id);
//            $data[] = [
//                'title' => '负责人',
//                'value' => $principal->name
//            ];
//
//            $recives = array_column($task->participants()->get()->toArray(),'id');
//            $recives[] = $task->creator_id;//创建人
//            $recives[] = $task->principal_id;//负责人
//            $authorization = $request->header()['authorization'][0];
//            (new MessageRepository())->addMessage($user, $authorization, $title, $subheading, $module, $link, $data, $recives,$task->id);
//            DB::commit();
//        }catch (Exception $e){
//            DB::rollBack();
//            Log::error($e);
//        }
//    }

}
