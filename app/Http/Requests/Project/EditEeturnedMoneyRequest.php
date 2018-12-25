<?php

namespace App\Http\Requests\Project;

use Dingo\Api\Http\FormRequest;

class EditEeturnedMoneyRequest extends FormRequest
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
            'principal_id' => 'nullable',
            'issue_name' => 'nullable',
            'plan_returned_money' => 'nullable|numeric',
            'plan_returned_time' => 'nullable|date',
            'desc' => 'nullable',


        ];
    }
}
