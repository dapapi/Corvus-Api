<?php

namespace App\Repositories;

use App\Models\Message;
use App\Models\MessageData;
use Illuminate\Support\Facades\DB;

class MessageRepository
{
    public function addMessage($title,$module,$data){
        DB::beginTransaction();

        $message = new Message();
        $message->title = $title;
        $message->module = $module;
        $message->save();
        $message_date = new MessageData();
        $message_date->addAll($data);
    }
}
