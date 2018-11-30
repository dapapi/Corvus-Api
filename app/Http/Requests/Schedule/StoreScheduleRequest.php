<?php

namespace App\Http\Requests\Schedule;


use Dingo\Api\Http\FormRequest;

class StoreScheduleRequest extends FormRequest
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
            'calendar_id' => 'required',
            'is_allday' => 'required|boolean',
            'start_at' => 'required|date|after_or_equal:today',
            'end_at' => 'required|date|after:start_at',
            'privacy' => 'required|boolean',
            'project_ids' => 'nullable|array',
            'task_ids' => 'nullable|array',
            'participant_ids' => 'nullable|array',
            'material_id' => 'nullable',
            'repeat' => 'nullable',
            'position' => 'nullable',
            'desc' => 'nullable',

            'affix' => 'nullable|array',
            'affix.*.title' => 'required_with:affix|max:255',
            'affix.*.size' => 'required_with:affix|numeric|min:0',
            'affix.*.url' => 'required_with:affix|max:500',
        ];
    }
}
