<?php

namespace App\Http\Requests\Calendar;

use Illuminate\Foundation\Http\FormRequest;

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
            'title' => 'nullable',
            'color' => 'nullable',
            'privacy' => 'nullable|numeric',
            'star' => 'nullable|numeric',
            'participant_ids' => 'nullable|array',
            'participant_del_ids' => 'nullable|array',
        ];
    }
}
