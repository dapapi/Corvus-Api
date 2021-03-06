<?php

namespace App\Http\Requests\Trail;


use Dingo\Api\Http\FormRequest;
use App\Models\Trail;
use Illuminate\Validation\Rule;

class AddTrailRequest extends FormRequest
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
            'client.brand' => 'required',   //品牌
            'principal_id' => 'required|numeric',
           // 'industry_id' => 'required|numeric',
            'client.id' => 'nullable|numeric',
            'client.company' => 'required_without:client.id|unique:clients,company',
            'client.grade' => 'numeric',
            'client.industry' => 'numeric',
            'client.customer' => 'numeric',
            'contact.id' => 'nullable|numeric',
            'contact.name' => 'required_without:contact.id',
            'contact.phone' => ['nullable:digits:11', 'regex:/^1[34578]\d{9}$/'],
           // 'contact.phone' => 'nullable'|['required_without:contact.id', 'digits:11', 'regex:/^1[34578]\d{9}$/'],
            'resource' => 'nullable',
            'resource_type' => 'required|numeric',
            'type' => 'required|numeric',
            'cooperation_type' => 'nullable|numeric', // 合作类型
           // 'status' => 'nullable|numeric',
            'status' => ['nullable',Rule::in(Trail::STATUS_BEGIN,Trail::STATUS_ENTER,Trail::STATUS_PURPOSE)],
            'priority' => 'required|numeric',
            'recommendations' => 'nullable|array', //推荐艺人
            'recommendations.*.id' =>  'nullable|integer',
            'recommendations.*.flag'    =>  ['nullable',Rule::in('star','blogger')],
            'expectations' => 'required|array', //目标艺人
            'expectations.*.id' =>  'required|integer',
            'expectations.*.flag'    =>  ['required',Rule::in('star','blogger')],
            'fee' => 'required|numeric',
            'lock' => 'nullable|boolean',
            'desc' => 'nullable',
        ];
    }


    public function messages()
    {
        return [
            'title.required' => '线索名称必填',
           // 'brand.reuqired' => '品牌必填',
            'principal_id.required' => '负责人必填',
            'principal_id.numeric' => '负责人必须为数值',
            'client.id.numeric' => '关联客户id必须为数值',
            'client.company.unique' => '关联客户公司名称已存在',
            'client.grade.numeric' => '关联客户级别传数值',
            'client.brand.numeric' => '品牌必填',
            'client.industry.numeric' => '行业必填',
            'contact.id.numeric' => '关联联系人id必须为数值',
            'contact.phone.digits' => '关联联系人手机号需满足11位',
            'contact.phone.regex' => '关联联系人手机号格式不正确',
            'resource_type.numeric' => '线索来源类型填数值',
            'type.numeric' => '线索类型填数值',
            'cooperation_type.numeric' => '合作类型填数值',
            'status.numeric' => '线索状态填数值',
            'recommendations.array' => '推荐艺人数组',
            'recommendations.*.id.integer'  =>  '推荐艺人id必须为数字',
            'recommendations.*.flag'    =>  '推荐艺人参数错误!',
            'expectations.required' =>  '目标艺人为必填',
            'expectations.*.id.required' => '目标艺人id不能为空',
            'expectations.*.id.integer' =>  '目标艺人id必须为数字',
            'expectations.*.flag.required'   =>  '目标艺人参数错误',
            'expectations.array' => '目标艺人数组',
            'fee.numeric' => '合作预算填数值',
            'lock' => '是否锁价需布尔值',
        ];
    }
}
