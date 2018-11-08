<?php

namespace App\Http\Requests\Trail;

use Illuminate\Foundation\Http\FormRequest;

class SearchTrailRequest extends FormRequest
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
            'type' => 'required',
            'id' => 'required|numeric',
        ];
    }
}
