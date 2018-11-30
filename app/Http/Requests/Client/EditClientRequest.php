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
            'region_id' => 'nullable',
            'address' => 'nullable',
            'principal_id' => 'nullable',
            'size' => 'nullable',
            'keyman' => 'nullable',
            'desc' => 'nullable',
        ];
    }

    public function messages() {
        return [
            'company.unique' => '公司名称已存在',
            'grade.numeric' => '客户类型应传数字',
            'region_id' => '地区id',
            'address' => '地址',
            'principal_id' => '负责人',
            'industry_id' => '行业id',
            'size' => '规模',
            'keyman' => '关键人',
            'desc' => '描述',
        ];
    }
}
