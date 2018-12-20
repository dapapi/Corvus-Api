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

        $array['name'] = $instance->name;
        $array['avatar'] = $instance->avatar;

        return $array;
    }
}