<?php

namespace App\Http\Requests;


use App\TaskStatus;
use Dingo\Api\Http\FormRequest;
use Illuminate\Validation\Rule;

class TaskStatusRequest extends FormRequest
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
            'status' => Rule::in([TaskStatus::NORMAL, TaskStatus::COMPLETE, TaskStatus::TERMINATION]),
        ];
    }
}
