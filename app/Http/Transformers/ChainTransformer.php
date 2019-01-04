<?php

namespace App\Http\Transformers;

use App\Interfaces\ChainInterface;
use App\Models\Department;
use League\Fractal\TransformerAbstract;

class ChainTransformer extends TransformerAbstract
{
    protected $departmentId = null;

    public function transform(ChainInterface $chain)
    {
        $instance = $chain->next;
        if (is_null($instance)) {
            $array['name'] = '该用户不在系统';
        } else {
            $array['name'] = $instance->name;
            $array['icon_url'] = $instance->icon_url;
        }

        return $array;
    }
}