<?php

namespace App\Http\Requests\Trail;

use Illuminate\Foundation\Http\FormRequest;

class EditTrailRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        return false;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        return [
            'title' => 'nullable',
            'brand' => 'nullable',
            'principal_id' => 'nullable|numeric',
            'client.id' => 'nullable|numeric',
            'client.company' => 'required_without:client.id',
            'client.grade' => 'required_without:client.id|numeric',
            'contact.id' => 'nullable|numeric',
            'contact.name' => 'required_without:contact.id',
            'contact.phone' => 'required_without:contact.id',
            'artist_id' => 'nullable|numeric',
            'recommendations' => 'nullable|array',
            'fee' => 'nullable|numeric',
            'desc' => 'nullable',
        ];
    }

    public function messages()
    {
        return [
            'client.company.required_without' => '客户公司',
            'client.grade.required_without' => '客户级别',
            'contact.id.numeric' => '联系人id',
            'contact.name.required_without' => '联系人姓名',
            'contact.phone.required_without' => '联系人电话',
        ];
    }
}
