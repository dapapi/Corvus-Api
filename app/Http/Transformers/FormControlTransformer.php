<?php

namespace App\Http\Transformers;

use App\Models\ApprovalForm\Control;
use League\Fractal\TransformerAbstract;

class FormControlTransformer extends TransformerAbstract
{
    protected $num = null;
    public function __construct($num = null)
    {
        $this->num = $num;
    }

    public function transform(Control $control)
    {
        $arr = [
            'id' => hashid_encode($control->form_control_id),
            'control' => [
                'data_dictionary_id' => $control->dictionary->id,
                'data_dictionary_name' => $control->dictionary->name
            ],
            'form_control_pid' => $control->pid ? hashid_encode($control->pid) : 0,
            'sort_number' => $control->sort_number,
            'required' => $control->required,
            'control_title' => $control->title,
            'control_placeholder' => $control->placeholder,
            'control_value' => $control->value($this->num),
        ];
        if ($control->format)
            $arr['control_data_select_format'] = $control->format;

        if (in_array($control->control_id, [82,84,85]))
            $arr['control_enums'] = $control->enum;

        return $arr;
    }
}