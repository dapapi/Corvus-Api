<?php

namespace App\Http\Requests\Task;

use Dingo\Api\Http\FormRequest;

class AddRelateTaskRequest extends FormRequest
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
            'tasks' => 'nullable|array',
            'projects' => 'nullable|array',
        ];
    }
}
