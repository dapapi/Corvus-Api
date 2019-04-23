<?php

namespace App\Http\Requests\Project;


use App\Models\Project;
use App\Models\TemplateField;
use Dingo\Api\Http\FormRequest;
use Illuminate\Validation\Rule;
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
            # todo 修改trail相关逻辑
            'trail_id' => 'required_unless:type,'. Project::TYPE_BASE,
            'fee' => 'required_unless:type,'. Project::TYPE_BASE,
            'resource_type' => 'required_unless:type,'. Project::TYPE_BASE,
            'resource' => 'required_unless:type,'. Project::TYPE_BASE,
            'cooperation_type' => 'required_if:type' . Project::TYPE_ENDORSEMENT,
            'expectations.*.id' => 'required_unless:type,' . Project::TYPE_BASE . '|integer',
            'expectations.*.name' => 'required_unless:type,' . Project::TYPE_BASE,
            'expectations.*.flag' => ['required_unless:type'. Project::TYPE_BASE,Rule::in(['star','blogger'])],
            'television_type' => 'required_if:type,'. Project::TYPE_MOVIE,
            'play_grade' => 'required_if:type,'. Project::TYPE_MOVIE,

            'participant_ids' => 'nullable|array',
            'start_at' => 'nullable|date',
            'end_at' => 'nullable|date|after:start_at',
            'desc' => 'nullable'
        ];
    }
}
