<?php

namespace App\Http\Requests\Trail;


use Dingo\Api\Http\FormRequest;

class EditTrailRequest extends FormRequest
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
            'title' => 'nullable',
            'brand' => 'nullable',
            'principal_id' => 'nullable|numeric',
            'industry_id' => 'nullable|numeric',
            'client.company' => 'nullable',
            'client.grade' => 'nullable|numeric',
            'contact.name' => 'nullable',
            'contact.phone' => ['nullable', 'digits:11', 'regex:/^1[34578]\d{9}$/'],
            'artist_id' => 'nullable|numeric',
            'recommendations' => 'nullable|array',
            'expectation' => 'nullable|array',
            'fee' => 'nullable|numeric',
            'lock' => 'nullable|boolean',
            'desc' => 'nullable',
            'refuse' => 'nullable|boolean',
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
