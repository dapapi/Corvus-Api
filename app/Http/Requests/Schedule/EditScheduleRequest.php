<?php

namespace App\Http\Requests\Schedule;

use Illuminate\Foundation\Http\FormRequest;

class EditScheduleRequest extends FormRequest
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
            'calendar_id' => 'nullable',
            'is_allday' => 'nullable|boolean',
            'start_at' => 'nullable|date|after_or_equal:today',
            'end_at' => 'nullable|date|after:start_at',
            'privacy' => 'nullable|boolean',
            'material_id' => 'nullable',
            'repeat' => 'nullable',
            'position' => 'nullable',
            'desc' => 'nullable',
            'affix' => 'nullable|array',
        ];
    }
}
