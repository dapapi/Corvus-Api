<?php
/**
 * Created by PhpStorm.
 * User: apple
 * Date: 2018/12/10
 * Time: 7:17 PM
 */

namespace App\Helper;
use WebSocket\Client;

class SendMessage{
    private $websocket_client;
    public function __construct($uri=null)
    {
        $uri = $uri == null ? config("app.websocket_uri") : $uri;
        $this->websocket_client = new Client($uri);
    }

    public function login($user_id,$username)
    {
        $user = new User();
        $user->userId = $user_id;
        $user->userName = $username;
        $this->websocket_client->send(json_encode($user));
    }
    public function sendMessage($title,$link,$data,$recives)
    {
        $message = new Message();
        $message->title = $title;
        $message->to = implode(",",$recives);
        $message->link = $link;
        $message->message = $data;
        $message->action = "sendmessage";
        $this->websocket_client->send(json_encode($message));
    }
    public function recive(){
        $this->websocket_client->receive();
    }
}