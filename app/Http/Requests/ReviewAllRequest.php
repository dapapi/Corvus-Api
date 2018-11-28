<?php

namespace App\Http\Requests;



use Dingo\Api\Http\FormRequest;


class ReviewAllRequest extends FormRequest
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

          'template_id' => 'required|Integer',
    ];
    }
}
