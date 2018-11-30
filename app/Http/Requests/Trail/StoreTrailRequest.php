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
            'client.company' => 'required_without:client.id|unique:clients,company',
            'client.grade' => 'required_without:client.id|numeric',
            'contact.id' => 'nullable|numeric',
            'contact.name' => 'required_without:contact.id',
            'contact.phone' => ['required_without:contact.id', 'digits:11', 'regex:/^1[34578]\d{9}$/'],
            'resource' => 'nullable',
            'resource_type' => 'required|numeric',
            'type' => 'required|numeric',
            'cooperation_type' => 'nullable|numeric', // 合作类型
            'status' => 'nullable|numeric',
            'priority' => 'required|numeric',
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
            'title.required' => '线索名称必填',
            'brand.reuqired' => '品牌必填',
            'principal_id.required' => '负责人必填',
            'principal_id.numeric' => '负责人必须为数值',
            'client.id.numeric' => '关联客户id必须为数值',
            'client.company.unique' => '关联客户公司名称已存在',
            'client.grade.numeric' => '关联客户级别传数值',
            'contact.id.numeric' => '关联联系人id必须为数值',
            'contact.phone.digits' => '关联联系人手机号需满足11位',
            'contact.phone.regex' => '关联联系人手机号格式不正确',
            'resource_type.numeric' => '线索来源类型填数值',
            'type.numeric' => '线索类型填数值',
            'cooperation_type.numeric' => '合作类型填数值',
            'status.numeric' => '线索状态填数值',
            'recommendations.array' => '推荐艺人数组',
            'expectations.array' => '目标艺人数组',
            'fee.numeric' => '预计费用填数值',
            'lock' => '是否锁价需布尔值',
        ];
    }
}
