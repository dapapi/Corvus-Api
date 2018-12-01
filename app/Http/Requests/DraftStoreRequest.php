<?php

namespace App\Http\Requests;

use Dingo\Api\Http\FormRequest;


class DraftStoreRequest extends FormRequest
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
            'answer' => 'required|max:255',
            'issues_id' =>'max:255',
            'reviewer_id' =>'max:255',


        ];
    }
}
