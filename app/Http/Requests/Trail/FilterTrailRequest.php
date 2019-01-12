<?php

namespace App\Http\Requests\Trail;


use App\Models\Trail;
use Dingo\Api\Http\FormRequest;
use Illuminate\Validation\Rule;

class FilterTrailRequest extends FormRequest
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
            'keyword' => 'nullable',
            'status' => 'nullable',
            'principal_ids' => 'nullable',
            'type'  => Rule::in([Trail::TYPE_BASE,Trail::TYPE_PAPI,Trail::TYPE_ENDORSEMENT,Trail::TYPE_VARIETY,Trail::TYPE_MOVIE])
        ];
    }
}
