<?php

namespace App\Http\Requests\Project;

use Dingo\Api\Http\FormRequest;

class EditProjectRequest extends FormRequest
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
            'type' => 'nullable',
            'principal_id' => 'nullable|numeric',
            'priority' => 'nullable|numeric',
            'fee' => 'nullable',
            'resource_type' => 'nullable|numeric',
            'resource' => 'nullable',
            'expectations' => 'nullable|array',
            'fields' => 'nullable|array',
            'start_at' => 'nullable|date',
            'end_at' => 'nullable|date',
            'desc' => 'nullable',

            'participant_ids' => 'nullable|array',
            'participant_del_ids' => 'nullable|array',

        ];
    }
}
