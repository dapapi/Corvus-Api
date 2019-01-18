<?php

namespace App\Http\Requests\Calendar;


use Dingo\Api\Http\FormRequest;
use Illuminate\Validation\Rule;

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
            'title' => 'required',
            'star.flag'  =>  ['nullable',Rule::in(['star','blogger'])],
            'color' => 'required',
            'privacy' => 'required|numeric',
            'star.id' => 'nullable|numeric',
            'participant_ids' => 'nullable|array',
        ];
    }
}
