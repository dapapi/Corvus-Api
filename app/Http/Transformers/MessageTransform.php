<?php

namespace App\Http\Transformers;

use App\Models\Message;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use League\Fractal\TransformerAbstract;

class MessageTransform extends TransformerAbstract
{
    protected $availableIncludes = ['recive'];
    public function transform(Message $message)
    {
        return [
            'id'    =>  hashid_encode($message->id),
            'module'    =>  $message->module,
            'title' =>  $message->title,
            'link'  =>  $message->link,
            'created_at'   =>  Carbon::parse($message->created_at)->format('Y-m-d'),
            'dayofweek' =>  Carbon::parse($message->created_at)->dayOfWeek
        ];
    }
    public function includeRecive(Message $message)
    {
        $recive = $message->recive;
        return $this->collection($recive,new MessageStateTransform());
    }
}