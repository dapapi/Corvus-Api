<?php

namespace App\Http\Requests\Project;


use App\Models\Project;
use App\Models\TemplateField;
use Dingo\Api\Http\FormRequest;
use Illuminate\Validation\Validator;

class StoreProjectRequest extends FormRequest
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
            'type' => 'required',
            'principal_id' => 'required|numeric',
            'priority' => 'required|numeric',
            'trail.id' => 'required_unless:type,'. Project::TYPE_BASE,
            'trail.fee' => 'nullable',
            'trail.lock' => 'nullable|boolean',
            'trail.resource_type' => 'nullable|numeric',
            'trail.resource' => 'nullable',
            'trail.recommendations' => 'nullable|array',
            'trail.expectations' => 'nullable|array',
            'fields' => 'required_unless:type,'. Project::TYPE_BASE .'|array',
            'start_at' => 'nullable|date',
            'end_at' => 'nullable|date|after:start_at',
            'desc' => 'nullable'
        ];
    }
}
