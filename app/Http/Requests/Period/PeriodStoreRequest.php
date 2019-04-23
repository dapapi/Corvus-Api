<?php

namespace App\Http\Requests\Period;

use Dingo\Api\Http\FormRequest;

class PeriodStoreRequest extends FormRequest
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
            'name' => 'required|unique',
            'start_at' => 'required|before:end_at',
            'end_at' => 'required|after:start_at',
        ];
    }
}
