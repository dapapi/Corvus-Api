<?php

namespace App\Http\Requests;


use Dingo\Api\Http\FormRequest;

class ModuleUserRequest extends FormRequest
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
            'person_ids' => 'array',
            'del_person_ids' => 'array'
        ];
    }
}
