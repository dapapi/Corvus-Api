<?php

namespace App\Http\Requests\Trail;


use Dingo\Api\Http\FormRequest;

class StoreTrailRequest extends FormRequest
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
        // todo 更新 优先级字段
        return [
            'title' => 'required',
            'brand' => 'required',
            'principal_id' => 'required|numeric',
            'industry_id' => 'required|numeric',
            'client.id' => 'nullable|numeric',
            'client.company' => 'required_without:client.id',
            'client.grade' => 'required_without:client.id|numeric',
            'contact.id' => 'nullable|numeric',
            'contact.name' => 'required_without:contact.id',
            'contact.phone' => ['required_without:contact.id', 'digits:11', 'regex:/^1[34578]\d{9}$/'],
            'resource' => 'nullable',
            'resource_type' => 'required|numeric',
            'recommendations' => 'nullable|array',
            'expectations' => 'nullable|array',
            'fee' => 'required|numeric',
            'lock' => 'nullable|boolean',
            'desc' => 'nullable',
        ];
    }


    public function messages()
    {
        return [
            'title' => '线索名称',
            'brand' => '品牌',
            'principal_id' => '负责人',
            'client_id' => '关联客户',
            'contact_id' => '关联联系人',
            'artist_id' => '关联艺人',
            'recommendations' => '推荐艺人',
            'desc' => '描述',
        ];
    }
}
