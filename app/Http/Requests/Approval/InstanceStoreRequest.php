<?php

namespace App\Http\Requests\Approval;

use Dingo\Api\Http\FormRequest;

class InstanceStoreRequest extends FormRequest
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
            'values' => 'required|array',
            'values.*.key' => 'required|numeric',
            'values.*.value' => 'required',
            'values.*.type' => 'nullable',
            'chains' => 'nullable|array',
            'notice' => 'nullable|array',
        ];
    }
}
