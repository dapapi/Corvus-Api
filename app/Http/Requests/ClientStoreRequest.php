<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ClientStoreRequest extends FormRequest
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
            'brand' => 'required',
            'company' => 'required',
            'grade' => 'required',
            'region_id' => 'nullable',
            'address' => 'nullable',
            'principal_id' => 'required',
            'industry_id' => 'required|numeric',
            'size' => 'nullable',
            'contact.name' => 'required',
            'contact.phone' => ['nullable', 'digits:11', 'regex:/^1[34578]\d{9}$/'],
            'contact.position' => 'required',
            'keyman' => 'nullable',
            'desc' => 'nullable',
        ];
    }

    public function messages() {
        return [
            'brand' => '品牌',
            'company' => '公司',
            'grade' => '客户类型',
            'region_id' => '地区id',
            'address' => '地址',
            'principal_id' => '负责人',
            'industry_id' => '行业id',
            'size' => '规模',
            'contact.name' => '联系人姓名',
            'contact.phone' => '联系人电话',
            'contact.position' => '联系人职位',
            'keyman' => '关键人',
            'desc' => '描述',
        ];
    }
}
