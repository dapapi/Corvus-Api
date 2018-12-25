<?php

namespace App\Http\Requests;

use App\ModuleableType;
use     Dingo\Api\Http\FormRequest;
use Illuminate\Validation\Rule;

class ScheduleRequest extends FormRequest
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
            'date'  =>  'required|date',
            'starable_type' =>  'required',
            'starable_type' =>  Rule::in([
                ModuleableType::BLOGGER,//博主
                ModuleableType::STAR//艺人
            ]),
            'starable_id'   =>  'required|Integer'
        ];
    }
}
