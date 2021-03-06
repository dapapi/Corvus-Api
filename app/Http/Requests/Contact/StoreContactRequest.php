<?php

namespace App\Http\Requests\Contact;


use Dingo\Api\Http\FormRequest;

class StoreContactRequest extends FormRequest
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
            'name' => 'required',
//            'phone' => ['nullable', 'unique:contacts', 'digits:11', 'regex:/^1[34578]\d{9}$/'],
            'phone' => ['nullable', 'digits:11', 'regex:/^1[34578]\d{9}$/'],//去掉电话唯一性校验
            'other_contact_ways' => 'nullable',
            'wechat' => 'nullable',
            'position' => 'required',
            'type' => 'required|numeric'
        ];
    }
}
