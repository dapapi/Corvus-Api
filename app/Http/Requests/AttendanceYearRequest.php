<?php

namespace App\Http\Requests;

use Dingo\Api\Http\FormRequest;

class AttendanceYearRequest extends FormRequest
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
            'year'  =>  'required|Integer'
        ];
    }
    public function messages()
    {
        return [
            'year.required' =>  '请选择查询考勤年份',
            'year.Integer'  =>  '查询年份必须为整数'
        ];
    }
}
