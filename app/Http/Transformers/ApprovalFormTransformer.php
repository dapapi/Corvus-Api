<?php

namespace App\Http\Transformers;

use App\Models\ApprovalForm\ApprovalForm;
use League\Fractal\TransformerAbstract;

class ApprovalFormTransformer extends TransformerAbstract
{
    protected $availableIncludes = ['applicant', 'approval_form_controls'];

    protected $num = null;

    public function __construct($num = null)
    {
        $this->num = $num;
    }

    public function transform(ApprovalForm $form)
    {
        dd($form);
        return [
            'form_id' => hashid_encode($form->form_id),
            'name' => $form->name,
            'modified' => [
                'id' => $form->modifiedDetail->id,
                'name' => $form->modifiedDetail->name,
            ],
            'description' => $form->description,
            'icon' => $form->icon,
            // todo 两个数据字典
            'change_type' => [
                'id' => $form->changeTypeDetail->id,
                'name' => $form->changeTypeDetail->name,
            ]
        ];
    }

    public function includeApprovalFormControls(ApprovalForm $form)
    {
        $controls = $form->controls()->orderBy('sort_number')->get();
        return $this->collection($controls, new FormControlTransformer());
    }
}