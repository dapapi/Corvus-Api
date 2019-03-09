<?php

namespace App\Http\Requests\Contract;

use Dingo\Api\Http\FormRequest;

class ContractArchiveRequest extends FormRequest
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
            'comment' => 'nullable',
            'files' => 'array|required',
        ];
    }
}