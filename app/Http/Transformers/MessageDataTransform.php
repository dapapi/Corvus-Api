<?php

namespace App\Http\Transformers;

use App\Models\MessageData;
use League\Fractal\TransformerAbstract;

class MessageDataTransform extends TransformerAbstract
{
    public function transform(MessageData $messageState)
    {
        return [
            'title' =>  $messageState->title,
            'value' =>  $messageState->value,
        ];
    }

}