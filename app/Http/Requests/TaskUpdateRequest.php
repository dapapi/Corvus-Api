<?php

namespace App\Http\Requests;


use App\TaskPriorityStatus;
use Dingo\Api\Http\FormRequest;
use Illuminate\Validation\Rule;

class TaskUpdateRequest extends FormRequest
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

    protected function prepareForValidation()
    {
        $this->offsetSet('curr_date', date('Y-m-d H:i'));
    }


    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
//        'type',
//        'task_pid',

        return [
            'title' => 'max:255',
            'principal_id' => 'numeric',
            'privacy' => 'boolean',
            'priority' => Rule::in([TaskPriorityStatus::NOTHING, TaskPriorityStatus::HIGH, TaskPriorityStatus::MIDDLE, TaskPriorityStatus::LOW]),
            'start_at' => 'date',
            'end_at' => 'date|after_or_equal:start_at',
        ];
    }
}
