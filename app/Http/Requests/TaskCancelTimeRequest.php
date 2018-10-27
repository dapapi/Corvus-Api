<?php

namespace App\Http\Requests;


use Dingo\Api\Http\FormRequest;
use Illuminate\Validation\Rule;

class TaskCancelTimeRequest extends FormRequest
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
            'type' => Rule::in(['start_at', 'end_at']),
        ];
    }
}
