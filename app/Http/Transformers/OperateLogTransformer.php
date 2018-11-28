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
            'created_at' => $operateLog->created_at->toDatetimeString(),
            'user' => $operateLog->user_id,
        ];

        if ($operateLog->user_id) {
            $array['username'] = $operateLog->user->name;
        }
        return $array;
    }

    public function includeDepartment(User $user)
    {
        $department = $user->department()->first();
        if (!$department) {
            return null;
        }
        return $this->item($department, new DepartmentTransformer());
    }

    public function includeUser(OperateLog $operateLog)
    {
        $user = $operateLog->user;
        if (!$user)
            return null;
        return $this->item($user, new UserTransformer());
    }
}