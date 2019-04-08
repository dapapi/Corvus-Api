<?php

namespace App\Listeners;

use App\Events\BloggerMessageEvent;
use App\Models\Blogger;
use App\Models\DataDictionarie;
use App\Models\Message;
use App\Models\RoleResource;
use App\Models\RoleUser;
use App\Repositories\MessageRepository;
use App\TriggerPoint\BloggerTriggerPoint;
use DemeterChain\B;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;

class BloggerMessageEventListener
{
    private $messageRepository;
    private $blogger_arr;//任务model
    private $trigger_point;//触发点
    private $authorization;//token
    private $user;//发送消息用户
    private $data;//向用户发送的消息内容
    private $created_at;
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
    public function handle(BloggerMessageEvent $event)
    {
        $this->blogger_arr = $event->blogger_arr;
        $this->trigger_point = $event->trigger_point;
        $this->authorization = $event->authorization;
        $this->user = $event->user;
        $created_at = $event->meta['created'];//签约时间
        //获取所有博主
        $bloggers = Blogger::whereIn('id',$this->blogger_arr)->select('nickname')->get()->toArray();
        $blogger_names = implode(",",array_column($bloggers,'nickname'));
        $this->data = json_decode(sprintf($this->message_content,$blogger_names,$created_at),true);
        switch ($this->trigger_point){
            case BloggerTriggerPoint::SIGNING://签约
                $this->sendMessageWhenSigning();
                break;
            case BloggerTriggerPoint::RESCISSION://解约
                $this->sendMessageWhenRescission();
                break;
        }
    }

    /**
     * 博主签约通过时向全员发消息
     */
    public function sendMessageWhenSigning()
    {
        //获取全部博主
        $blogger_arr = array_column(Blogger::select("nickname")->whereIn('id',$this->blogger_arr)->get()->toArray(),"nickname");
        $blogger_names = implode(",",$blogger_arr);
        //获取有查看艺人详情的功能权限的角色
        $resource_list = DataDictionarie::where(function($query){
            $query->where('val','/stars/detail/{id}')
                ->where('code','get');
        })->orWhere(function ($query){
            $query->where('val','/stars/{id}')
                ->where('code','get');
        })->pluck('id');
        $role_list = RoleResource::whereIn('resource_id',$resource_list)->pluck('role_id');
        //获取对应角色的用户
        $user_list = RoleUser::whereIn('role_id',$role_list)->pluck('user_id')->toArray();
        $subheading = $title = $blogger_names."签约";
        $send_to = $user_list;//全员
        $this->sendMessage($title,$subheading,$send_to);
    }
    /**
     * 博主解约时向全员发消息
     */
    public function sendMessageWhenRescission()
    {
        //获取全部博主
        $blogger_arr = array_column(Blogger::select("nickname")->whereIn('id',$this->blogger_arr)->get()->toArray(),"nickname");
        $blogger_names = implode(",",$blogger_arr);
        //获取有查看艺人详情的功能权限的角色
        $resource_list = DataDictionarie::where(function($query){
            $query->where('val','/stars/detail/{id}')
                ->where('code','get');
        })->orWhere(function ($query){
            $query->where('val','/stars/{id}')
                ->where('code','get');
        })->pluck('id');
        $role_list = RoleResource::whereIn('resource_id',$resource_list)->pluck('role_id');
        //获取对应角色的用户
        $user_list = RoleUser::whereIn('role_id',$role_list)->pluck('user_id')->toArray();

        $subheading = $title = $blogger_names."解约";
        $send_to = $user_list;//全员
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
            Message::BLOGGER, null, $this->data, $send_to,$this->blogger_arr[0]);
    }
}
