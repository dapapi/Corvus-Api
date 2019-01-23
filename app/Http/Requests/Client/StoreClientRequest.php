<?php

namespace App\Http\Requests\Client;


use Dingo\Api\Http\FormRequest;

class StoreClientRequest extends FormRequest
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
            'company' => 'required|unique:clients',
            'grade' => 'required|numeric',
            'type' => 'required|numeric',
            'client_rating' => 'required|numeric',
            'province' => 'nullable',
            'city' => 'nullable',
            'district' => 'nullable',
            'address' => 'nullable',
            'principal_id' => 'required',
            'size' => 'nullable',
            'contact.name' => 'required',
            'contact.phone' => ['required', 'digits:11', 'regex:/^1[34578]\d{9}$/'],
            'contact.position' => 'required',
            'contact.type' => 'required|numeric',
            'desc' => 'nullable',
        ];
    }

}
