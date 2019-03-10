<?php

namespace App\Http\Requests\ApprovalFlow;

use Dingo\Api\Http\FormRequest;
use Illuminate\Validation\Rule;

class GetChainsRequest extends FormRequest
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
            'form_id' => 'required',
            'change_type' => [
                'nullable',
                Rule::in([
                    222,
                    223,
                    224,
                ])
            ],
            'value' => 'required_if:change_type,224'
        ];
    }
}
