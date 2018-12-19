<?php

namespace App\Http\Transformers;

use App\Interfaces\ChainInterface;
use App\Models\Department;
use League\Fractal\TransformerAbstract;

class ChainTransformer extends TransformerAbstract
{
    protected $departmentId = null;

    public function __construct($departmentId)
    {
        $this->departmentId = $departmentId;
    }

    public function transform(ChainInterface $chain)
    {
        $instance = $chain->next;

        $array = [
            'sort_number' => $chain->sort_number
        ];

        // todo 部门主管

        if ($instance) {
            $array['next_id'] = hashid_encode($instance->id);
            $array['value'] = $instance->name;
        } else {
            $array['next_id'] = 0;
            $array['value'] = null;
        }

        return $array;
    }
}