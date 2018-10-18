<?php

namespace App\Http\Transformers;

use App\Models\Action;
use League\Fractal\TransformerAbstract;

class ActionTransformer extends TransformerAbstract
{
    public function transform(Action $action)
    {
        $array = [
            'id' => hashid_encode($action->id),
            'name' => $action->name,
            'icon' => $action->code,
            'module_id' => hashid_encode($action->module_id),
            'type' => $action->type,
            'desc' => $action->desc,
        ];

        return $array;
    }
}