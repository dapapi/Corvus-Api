<?php

namespace App\Http\Transformers;

use App\Models\MessageState;
use League\Fractal\TransformerAbstract;

class MessageStateTransform extends TransformerAbstract
{
    protected $availableIncludes = ['data'];
    public function transform(MessageState $messageState)
    {
        return [
            'user_id'   =>  $messageState->user_id,
            'state' =>  $messageState->state,
        ];
    }
    public function includeData(MessageState $messageState)
    {
        $date = $messageState->data;
        return $this->collection($date,new MessageDataTransform());
    }
}