<?php

namespace App\Http\Requests\Client;


use Dingo\Api\Http\FormRequest;

class EditClientRequest extends FormRequest
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
            'company' => 'nullable|unique:clients,company',
            'grade' => 'nullable|numeric',
            'type' => 'nullable|numeric',
            'province' => 'nullable',
            'city' => 'nullable',
            'district' => 'nullable',
            'address' => 'nullable',
            'principal_id' => 'nullable',
            'size' => 'nullable',
            'keyman' => 'nullable',
            'desc' => 'nullable',
        ];
    }
}
