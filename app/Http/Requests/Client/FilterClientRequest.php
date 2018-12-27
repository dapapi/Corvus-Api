<?php

namespace App\Http\Requests\Client;


use Dingo\Api\Http\FormRequest;

class FilterClientRequest extends FormRequest
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
            'principal_ids' => 'nullable|array',
            'grade' => 'nullable|numeric',
        ];
    }
}
