<?php

namespace App\Http\Requests;

use App\AffixType;
use App\Models\Attendance;
use Dingo\Api\Http\FormRequest;
use Illuminate\Validation\Rule;

class AttendanceRequest extends FormRequest
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
            'type' =>[
                'required',
                Rule::in([
                    Attendance::LEAVE,//请假
                    Attendance::OVERTIME,//加班
                    Attendance::BUSINESS_TRAVEL,//出差
                    Attendance::FIELD_OPERATION,//外勤
                ])
            ] ,
            'start_at'  => 'required|date',
            'end_at'    =>  'required|date',
            'number'    =>  'required',
            'cause' =>  'required|max:255',
//            'affixes'   =>  'required|max:255',
            'approval_flow' =>  'required|Integer',
            'notification_person'   =>  'required|max:255',
            'leave_type'    =>  Rule::in([
                Attendance::CASUAL_LEAVE,//事假
                Attendance::SICK_LEAVE,//病假
                Attendance::LEAVE_IN_LIEU,//调休假
                Attendance::ANNUAL_LEAVE,//年假
                Attendance::MARRIAGE_LEAVE,//婚假
                Attendance::MATERNITY_LEAVE,//产假
                Attendance::PATERNITY_LEAVE,//陪产假
                Attendance::FUNERAL_LEAVE,//丧假
                Attendance::OTHER_LEAVE,//其他
            ]),
            'place' => 'max:255',
            'status'    =>  Rule::in([
                Attendance::AGREED,//已同意
                Attendance::APPROVAL_PENFING,//待审批
                Attendance::REFUSED,//已拒绝
                Attendance::INVALID,//已作废
            ]),
            'affix' => 'array',
            'affix.*.title' => 'required|max:255',
            'affix.*.size' => 'required|numeric|min:0',
            'affix.*.url' => 'required|max:500',
            'affix.*.type' => ['required', Rule::in([AffixType::DEFAULT, AffixType::STAT_BULLETIN, AffixType::MONOLOGUE_VIDEO,AffixType::STAR_PLAN,AffixType::INTRODUCE_ONESELF,AffixType::OTHER])],

        ];
    }
    public function messages()
    {
        return [
            'type.required' =>  '考勤类型必须填',
            'type.in'   =>  '考勤类型不正确',
            'start_at.required' =>  '开始时间必须填写',
            'start_at.date' =>  '开始时间必须是日期类型',
            'end_at.required' =>  '结束时间必须填写',
            'end_at.date' =>  '结束时间必须是日期类型',
            'number.required'    => '天数必须填写',
            'cause.required'    =>  '事由必须填写',
            'cause.max' =>  '事由长度不能大于255个字',
            'leave_type.in'   =>  '请假类型不正确',
            'place.max' =>  '地点成都不能超过255个字',
            'affix.array'   =>  '附件上传参数错误',
            'affix.*.title.required' =>  '附件名称错误',
            'affix.*.title.max' =>  '附件名长度过长',
            'affix.*.size.required' =>  '附件大小不能为空',
            'affix.*.size.numeric'  =>  '附件大小必须是整数',
            'affix.*.size.min'  =>  '附件大小最小为0',
            'affix.*.url.required'  =>  '附件地址必传',
            'affix.*.url.max'   =>  '附件地址长度不能超过500',
            'affix.*.type.required'  =>  '附件类型必传',
            'affix.*.type.in'   =>  '附件类型不正确'

        ];
    }
}
