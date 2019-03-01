<?php

namespace App\Http\Requests\Filter;

use Dingo\Api\Http\FormRequest;

class FilterRequest extends FormRequest
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
            'keyword' => 'nullable',
            'conditions' => 'array',
//            'conditions.*.field' => 'required|exists:filter_fields,code',
            'conditions.*.operator' => 'required',
            'conditions.*.value' => 'required',
            'conditions.*.type' => 'required',
//            'conditions.*.id'   =>  'required',
        ];
    }
}
