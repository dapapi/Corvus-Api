<?php

namespace App\Listeners;

use App\Events\StarMessageEvent;
use App\Jobs\SendUmengMsgToMobile;
use App\Models\DataDictionarie;
use App\Models\Message;
use App\Models\RoleResource;
use App\Models\RoleUser;
use App\Models\MessageState;
use App\Models\Star;
use App\Repositories\MessageRepository;
use App\Repositories\UmengRepository;
use App\TriggerPoint\StarTriggerPoint;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;

class StarMessageEventListener
{
    private $messageRepository;
    protected $umengRepository;
    private $star_arr;//任务model
    private $trigger_point;//触发点
    private $authorization;//token
    private $user;//发送消息用户
    private $data;//向用户发送的消息内容
    private $star_names;//签约解约艺人名称
    private $umeng_text;
    private $created_at;//签约解约时间
    private $umeng_description;
    //消息发送内容
    private $message_content = '[{"title":"艺人名称","value":"%s"},{"title":"签约时间","value":"%s"}]';
    /**
     * Create the event listener.
     *
     * @return void
     */
    public function __construct(MessageRepository $messageRepository,UmengRepository $umengRepository)
    {
        $this->messageRepository = $messageRepository;
        $this->umengRepository = $umengRepository;
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
        $this->star_names = $star_names;
        $this->created_at = $created_at;
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
     * 艺人签约通过时向有查看艺人详情功能权限的人发消息
     */
    public function sendMessageWhenSigning()
    {
        //获取全部艺人
        $star_name_arr = array_column(Star::select("name")->whereIn('id',$this->star_arr)->get()->toArray(),"name");
        $star_names = implode(",",$star_name_arr);
        //获取有查看艺人详情的功能权限的角色
        $resource_list = DataDictionarie::where(function($query){
            $query->where('val','/stars/detail/{id}')
                ->where('code','get');
        })->orWhere(function ($query){
            $query->where('val','/stars/{id}')
                ->where('code','get');
        })->pluck('id');
        $role_list = RoleResource::whereIn('resouce_id',$resource_list)->pluck('role_id');
        //获取对应角色的用户
        $user_list = RoleUser::whereIn('role_id',$role_list)->pluck('user_id')->toArray();
        $subheading = $title = $star_names."签约";
        $this->umeng_description = $title;
        $this->umeng_text = "签约时间:".$this->created_at;
        $send_to = $user_list;//全员
        $this->sendMessage($title,$subheading,$send_to);
    }

    /**
     * 艺人解约时向全员发消息
     */
    public function sendMessageWhenRescission()
    {
        //获取全部艺人
        $star_name_arr = array_column(Star::select("name")->whereIn('id',$this->star_arr)->get()->toArray(),"name");
        $star_names = implode(",",$star_name_arr);
        //获取有查看艺人详情的功能权限的角色
        $resource_list = DataDictionarie::where(function($query){
            $query->where('val','/stars/detail/{id}')
                ->where('code','get');
        })->orWhere(function ($query){
            $query->where('val','/stars/{id}')
                ->where('code','get');
        })->pluck('id');
        $role_list = RoleResource::whereIn('resouce_id',$resource_list)->pluck('role_id');
        //获取对应角色的用户
        $user_list = RoleUser::whereIn('role_id',$role_list)->pluck('user_id')->toArray();
        $subheading = $title = $star_names."解约";
        $this->umeng_text = "解约时间:".$this->created_at;
        $this->umeng_description = $title;
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
            Message::STAR, null, $this->data, $send_to,$this->star_arr[0]);
        $umeng_title = "艺人名称:".$this->star_names;

//        $this->umengRepository->sendMsgToMobile($send_to,"艺人管理助手",$umeng_title,$this->umeng_text,Message::STAR,hashid_encode($this->star_arr[0]));
        $job = new SendUmengMsgToMobile([
            'send_to' => $send_to,
            'title' => $umeng_title,
            'tricker' => "艺人管理助手",
            'text' => $this->umeng_text,
            'description'   => $this->umeng_description,
            'module' => Message::STAR,
            'module_data_id' => hashid_encode($this->star_arr[0]),
        ]);
        dispatch($job)->onQueue("umeng_message");
    }
}
