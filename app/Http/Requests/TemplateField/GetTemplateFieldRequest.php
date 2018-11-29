<?php

namespace App\Http\Requests\TemplateField;


use Dingo\Api\Http\FormRequest;

class GetTemplateFieldRequest extends FormRequest
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
            'type' => 'required|numeric',
            'status' => 'required|numeric'
        ];
    }

    public function messages()
    {
        return [
            'type.required' => '123',
        ];
    }
}
