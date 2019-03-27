<?php

namespace App\Http\Transformers;

use App\Models\ApprovalForm\Control;
use App\Models\ApprovalForm\DetailValue;
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
            'related_field' => $control->relate,
            'form_control_pid' => $control->pid ? 1 : 0,
            'sort_number' => $control->sort_number,
            'required' => $control->required,
            'control_title' => $control->title,
            'control_placeholder' => $control->placeholder,
            'control_source' => $control->source,
            'disabled' => $control->disabled,
        ];
        if ($control->format)
            $arr['control_data_select_format'] = $control->format;

        if ($control->indefinite_show)
            $arr['indefinite_show'] = $control->indefinite_show;

        if (in_array($control->control_id, [82, 84, 85]))
            $arr['control_enums'] = $control->enum;

        if ($control->control_id == 391) {
            $arr['control_enums'] = $control->enum;
        }

        if (in_array($control->control_id, [81])) {
            $arr['control_title_sub'] = $control->sub_title;
            $arr['control_placeholder_sub'] = $control->sub_placeholder;
        }

        if ($this->num && !is_null($control->value($this->num)))
            $arr['control_value'] = $control->value($this->num)->form_control_value;
        else {
            $arr['control_value'] = null;

            $detailControl = Control::where('form_id', $control->form_id)->where('control_id', 88)->first();

            if ($detailControl) {
                $detailArr = [];
                foreach (DetailValue::where('form_instance_number', $this->num)->cursor() as $item) {
                    $detailArr[$item->sort_number][] = [
                        'key' => $item->key,
                        'values' => [
                            'data' => [
                                'value' => $item->value
                            ]
                        ]
                    ];
                }
                $arr['control_value'] = $detailArr;
            }
        }


        return $arr;
    }
}