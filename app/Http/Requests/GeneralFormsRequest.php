<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class GeneralFormsRequest extends FormRequest
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
            'form_group_id' =>  'array',
            'except_form_group_id'  =>  'array'
        ];
    }

    public function messages()
    {
        return [
            "form_group_id.array"   =>  '参数错误',
            'except_form_group_id.array'    =>  '参数错误'
        ];
    }
}
