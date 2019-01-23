<?php

namespace App\Http\Transformers;

use App\Models\OperateLog;
use League\Fractal\TransformerAbstract;

class OperateLogTransformer extends TransformerAbstract
{
    protected $availableIncludes = ['user'];
    public function transform(OperateLog $operateLog)
    {
        $array = [
            'content' => $operateLog->content,
            'method' => $operateLog->method,
            'status' => $operateLog->status,
            'level' => $operateLog->level,
            'created_at' => $operateLog->created_at->formatLocalized('%Y-%m-%d %H:%I'),//时间去掉秒,,
            'user' => hashid_encode($operateLog->user_id),
        ];

        if ($operateLog->user_id) {
            $array['username'] = $operateLog->user->name;
            $array['icon_url'] = $operateLog->user->icon_url;
        }
        return $array;
    }

    public function includeUser(OperateLog $operateLog)
    {
        $user = $operateLog->user;
        if (!$user)
            return null;
        return $this->item($user, new UserTransformer());
    }
}