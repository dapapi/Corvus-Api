<?php

namespace App\Http\Requests\Schedule;


use Dingo\Api\Http\FormRequest;

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
         //   'start_at' => 'nullable|date|after_or_equal:today',
            'start_at' => 'nullable|date',
            'end_at' => 'nullable|date|after:start_at',
            'privacy' => 'nullable|boolean',
            'material_id' => 'nullable',
            'participant_ids' => 'nullable|array',
            'participant_del_ids' => 'nullable|array',

            'project_ids' => 'nullable|array',
            'project_del_ids' => 'nullable|array',
            'task_ids' => 'nullable|array',
            'task_del_ids' => 'nullable|array',

            'repeat' => 'nullable',
            'position' => 'nullable',
            'desc' => 'nullable',

            'affix' => 'nullable|array',
            'affix.*.title' => 'required_with:affix|max:255',
            'affix.*.size' => 'required|numeric|min:0',
            'affix.*.url' => 'required|max:500',

            'affix_del' => 'nullable|array',
            'affix_del.*' => 'nullable|numeric'
        ];
    }
}
