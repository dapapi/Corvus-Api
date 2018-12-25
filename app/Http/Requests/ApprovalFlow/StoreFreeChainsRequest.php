<?php

namespace App\Http\Requests\ApprovalFlow;

use Dingo\Api\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreFreeChainsRequest extends FormRequest
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
            'chains' => 'required|array',
            'chains.id' => 'required'
        ];
    }
}
