<?php

namespace App\Http\Requests\Calendar;


use Dingo\Api\Http\FormRequest;
use Illuminate\Validation\Rule;

class EditCalendarRequest extends FormRequest
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
            'color' => 'required',
            'privacy' => 'nullable|numeric',
            'star.id' => 'nullable|numeric',
            'star.flag' =>  ['nullable',Rule::in(['star','blogger'])],
            'participant_ids' => 'nullable|array',
            'participant_del_ids' => 'nullable|array',
        ];
    }
    public function messages()
    {
        return [
          'star.flag.required'   =>  '关联艺人参数错误',
            'title.required' =>  '日历标题不能为空',
            'color.required'    =>  '日历颜色不能为空',
            'privacy.numberic'  =>  '是否公开参数错误',
            'star.id.number'  =>  '艺人错误',
            'participant_ids.array' =>  '参与人参数错误',
            'participant_del_ids.array' =>  '参与人参数错误'
        ];
    }
}
