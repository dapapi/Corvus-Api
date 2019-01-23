<?php

namespace App\Http\Transformers;

use App\Models\Message;
use League\Fractal\TransformerAbstract;

class MessageTransformer extends TransformerAbstract
{
    public function transform(Message $message)
    {
        return [
            'module'    =>  $message->module,
            'link'  =>  $message->link,
            'title' =>  $message->title,
            'state' =>  $message->state,
            'created_at'    =>  $message->created_at,
            'body'  =>  $message->data()->get(),
        ];
    }
}