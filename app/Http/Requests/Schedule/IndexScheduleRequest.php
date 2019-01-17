<?php

namespace App\Http\Requests\Schedule;

use Dingo\Api\Http\FormRequest;

class IndexScheduleRequest extends FormRequest
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
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date',
            'calendar_ids' => 'nullable|array',
            'material_ids' => 'nullable|array'
        ];
    }
    public function messages()
    {
        return [
          'start_date.required' =>  '请填写开始时间',
            'start_date.date'   =>  '开始时间类型不正确',
            'end_date.date' =>  '结束时间类型不正确',
            'end_date.required' =>  '请填写结束时时间',
            'calendar_ids'  =>  '日历参数错误',
            'material_ids'  =>  '会议室参数错误',
        ];
    }
}
