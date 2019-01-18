<?php

namespace App\Http\Requests\Trail;


use App\Models\Trail;
use Dingo\Api\Http\FormRequest;
use Illuminate\Validation\Rule;

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
            'client.company' => 'nullable|unique:clients,company',
            'client.grade' => 'nullable|numeric',
            'contact.name' => 'nullable',
            'contact.phone' => ['nullable', 'digits:11', 'regex:/^1[34578]\d{9}$/'],
            'resource' => 'nullable',
            'resource_type' => 'nullable|numeric',
            'status' => ['nullable',Rule::in([Trail::PRIORITY_A,Trail::PRIORITY_B,Trail::PRIORITY_C,Trail::PRIORITY_S])],
            'cooperation_type' => 'nullable|numeric', // 合作类型
            'priority' => 'nullable|numeric',
            'recommendations' => 'nullable|array', //推荐艺人
            'recommendations.*.id' =>  'nullable|integer',
            'recommendations.*flag'    =>  ['nullable',Rule::in('star','blogger')],
            'expectations' => 'required|array', //目标艺人
            'expectations.*.id' =>  'required|integer',
            'expectations.*.flag'    =>  ['required',Rule::in('star','blogger')],
            'fee' => 'nullable|numeric',
            'lock' => 'nullable|boolean',
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
