<?php

namespace App\Http\Requests;

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
            'type' => Rule::in([
                Attendance::LEAVE,//请假
                Attendance::OVERTIME,//加班
                Attendance::BUSINESS_TRAVEL,//出差
                Attendance::FIELD_OPERATION,//外勤
            ]),
            'start_at'  => 'required|date',
            'end_at'    =>  'required|date',
            'number'    =>  'required|Integer',
            'cause' =>  'required|max:255',
            'affixes'   =>  'required|max:255',
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

        ];
    }
}
