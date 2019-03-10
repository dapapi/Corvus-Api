<?php

namespace App\Http\Transformers;

use App\Models\Message;
use Carbon\Carbon;
use League\Fractal\TransformerAbstract;

class MessageTransformer extends TransformerAbstract
{
    public function transform(Message $message)
    {
        return [
            'module'    =>  $message->module,
            'title' =>  $message->title,
            'state' =>  $message->state,
            'module_data_id'    =>$message->module_data_id,
            'body'  =>  $message->data()->select('title','value')->get(),
            'created_at'    =>  Carbon::createFromTimeString($message->created_at)->toDateTimeString()
        ];
    }
}