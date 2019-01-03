<?php

namespace App\Http\Transformers;

use App\Models\ApprovalForm\Control;
use League\Fractal\TransformerAbstract;

class ControlTransformer extends TransformerAbstract
{
    protected $num = null;
    public function __construct($num = null)
    {
        $this->num = $num;
    }

    protected $defaultIncludes= ['values'];

    public function transform(Control $control)
    {
        $array = [
            'key' => $control->title,
        ];

        return $array;
    }

    public function includeValues(Control $control)
    {
        $value = $control->value($this->num);
        if (!$value)
            return null;

        return $this->item($value, new ApprovalInstanceValueTransformer());
    }
}