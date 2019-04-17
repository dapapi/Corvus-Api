<?php

namespace App\Http\Requests\Aim;

use Dingo\Api\Http\FormRequest;

class AimEditRequest extends FormRequest
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
            'percentage' => 'between:0,100',
        ];
    }

    public function messages()
    {
        return [
            'percentage.between' => '进度范围在0～100%',
        ];
    }
}
