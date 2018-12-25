<?php

namespace App\Http\Requests\Approval;

use Dingo\Api\Http\FormRequest;
use Illuminate\Validation\Rule;

class GetFormIdsRequest extends FormRequest
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
            'type' => [
                'required',
                // 暂为0,1
                // 0 合同，1通用审批
                Rule::in([0,1]),
            ]
        ];
    }
}
