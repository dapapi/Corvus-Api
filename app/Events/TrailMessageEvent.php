<?php

namespace App\Events;

use App\Models\Trail;
use App\User;
use Illuminate\Broadcasting\Channel;
use Illuminate\Queue\SerializesModels;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;

class TrailMessageEvent
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $model;
    public $trigger_point;
    public $authorization;
    public $user;
    public $meta;

    /**
     * 消息事件构造器
     * TrailMessageEvent constructor.
     * @param Trail $model 线索模型
     * @param $trigger_point 线索触发点(常量)
     * @param $authorization 当前登录用户的token
     * @param User $user    发送消息的的用户，及当前登录用户
     * @param array $meta   额外参数
     */
    public function __construct(Trail $model,$trigger_point,$authorization,User $user,$meta = [])
    {
        $this->model = $model;
        $this->trigger_point = $trigger_point;
        $this->authorization = $authorization;
        $this->user = $user;
        $this->meta = $meta;
    }

    /**
     * Get the channels the event should broadcast on.
     *
     * @return \Illuminate\Broadcasting\Channel|array
     */
    public function broadcastOn()
    {
        return new PrivateChannel('channel-name');
    }
}
