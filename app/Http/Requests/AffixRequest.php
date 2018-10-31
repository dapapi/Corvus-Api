<?php

namespace App\Http\Requests;

use Dingo\Api\Http\FormRequest;

class AffixRequest extends FormRequest
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
            'title' => 'required|max:255',
            'size' => 'required|numeric|min:0',
            'url' => 'required|max:500',
        ];
    }
}
