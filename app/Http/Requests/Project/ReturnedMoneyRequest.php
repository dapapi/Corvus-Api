<?php

namespace App\Http\Requests\Project;

use Illuminate\Foundation\Http\FormRequest;

class ReturnedMoneyRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        return [
            'contract_id' => 'nullable',
            'project_id' => 'nullable|numeric',
            'principal_id' => 'nullable|numeric',
            'issue_ name' => 'nullable|max:22',
            'plan_returned_money' => 'nullable|nullable',
            'plan_returned_time' => 'nullable|date',
            'desc' => 'nullable'

        ];
    }
}
