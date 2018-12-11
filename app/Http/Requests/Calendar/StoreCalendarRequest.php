<?php

namespace App\Http\Requests\Calendar;


use Dingo\Api\Http\FormRequest;

class StoreCalendarRequest extends FormRequest
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
            'title' => 'required|unique:calendars',
            'color' => 'required',
            'privacy' => 'required|numeric',
            'star' => 'nullable|numeric',
            'participant_ids' => 'nullable|array',
        ];
    }
}
