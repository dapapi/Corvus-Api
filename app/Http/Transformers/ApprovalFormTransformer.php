<?php

namespace App\Http\Transformers;

use App\Models\ApprovalForm\ApprovalForm;
use League\Fractal\TransformerAbstract;

class ApprovalFormTransformer extends TransformerAbstract
{
    protected $availableIncludes = ['approval_form_controls'];

    protected $num = null;

    public function __construct($num = null)
    {
        $this->num = $num;
    }

    public function transform(ApprovalForm $form)
    {
        $arr = [
            'form_id' => hashid_encode($form->form_id),
            'name' => $form->name,
            'modified' => [
                'id' => $form->modifiedDetail->id,
                'name' => $form->modifiedDetail->name,
            ],
            'description' => $form->description,
            'icon' => $form->icon,
            'change_type' => [
                'id' => $form->changeTypeDetail->id,
                'name' => $form->changeTypeDetail->name,
            ],
        ];

        if ($form->condition_control)
            $arr['condition'] = $form->condition_control;

        if ($this->num)
            $arr['form_instance_number'] = $this->num;

        return $arr;
    }

    public function includeApprovalFormControls(ApprovalForm $form)
    {
        $controls = $form->controls()->orderBy('sort_number')->get();
        return $this->collection($controls, new FormControlTransformer($this->num));
    }

}