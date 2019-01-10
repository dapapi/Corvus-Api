<?php

namespace App\Http\Transformers;

use App\Interfaces\ApprovalParticipantInterFace;
use League\Fractal\TransformerAbstract;

class ApprovalParticipantTransformer extends TransformerAbstract
{
    public function transform(ApprovalParticipantInterFace $participant)
    {
        $notic = $participant->notice;
        return [
            'id' => hashid_encode($notic->id),
            'name' => $notic->name,
            'type' => $participant->notice_type,
            "icon_url" => $notic->icon_url
        ];
    }
}