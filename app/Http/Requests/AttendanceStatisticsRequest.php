<?php

namespace App\Http\Requests;

use App\Models\Attendance;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class AttendanceStatisticsRequest extends FormRequest
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
            'start_time'    =>  'date',
            'end_time'  =>  'date',
            'department'    =>  'Integer',
            'type'  =>  Rule::in(
                            [
                                Attendance::LEAVE,//请假
                                Attendance::OVERTIME,//加班
                                Attendance::BUSINESS_TRAVEL,//出差
                                Attendance::FIELD_OPERATION,//外勤
                            ]
                        )
        ];
    }
    public function messages()
    {
        return [
            'start_time.date'   =>  '开始时间必须为时间类型',
            'end_time.date' =>  '结束时间必须为时间类型',
            'department.Integer'    =>  '部门选择错误',
            'type.in'   =>  '考勤类型不正确'
        ];
    }
}
