<?php

namespace App\Http\Requests\Aim;

use Dingo\Api\Http\FormRequest;

class AimStoreRequest extends FormRequest
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
            'title' => 'required',
            'range' => 'required',
            'department_id' => 'nullable',
            'period_id' => 'required',
            'type' => 'required',
            'amount_type' => 'nullable',
            'amount' => 'nullable',
            'position' => 'required',
            'talent_level' => 'nullable',
            'aim_level' => 'required',
            'principal_id' => 'required',
            'desc' => 'nullable',
            'parents_ids' => 'array'
        ];
    }

    public function messages()
    {
        return [
            'title.required' => '目标名称 必须填写',
            'range.required' => '目标范围 必须填写',
            'period_id.required' => '目标周期 必须填写',
            'type.required' => '目标类型 必须填写',
            'position.required' => '维度 必须填写',
            'aim_level.required' => '目标级别 必须填写',
            'principal_id.required' => ' 必须填写',
        ];
    }
}
