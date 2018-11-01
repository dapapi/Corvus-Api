<?php

namespace App\Http\Transformers;

use App\Models\OperateLog;
use League\Fractal\TransformerAbstract;

class OperateLogTransformer extends TransformerAbstract
{
    protected $availableIncludes = ['user'];

    public function transform(OperateLog $operateLog)
    {
        return [
            'content' => $operateLog->content,
            'method' => $operateLog->method,
            'status' => $operateLog->status,
            'level' => $operateLog->level,
        ];
    }

    public function includeUser(OperateLog $operateLog)
    {
        $user = $operateLog->user;
        if (!$user)
            return null;
        return $this->item($user, new UserTransformer());
    }
}