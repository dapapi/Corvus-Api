<?php

namespace App\Http\Requests;

use App\AffixType;
use App\TaskPriorityStatus;
use Dingo\Api\Http\FormRequest;
use Illuminate\Validation\Rule;

class SupplierRequest extends FormRequest
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

//    protected function prepareForValidation()
//    {
//        $this->offsetSet('curr_date', date('Y-m-d H:i'));
//    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {

        return [
            'name' => 'required|max:255',
            'address' => 'required|max:255',
            'level' => 'required|max:255',
            'contact' => 'required|max:255',
            'phone' => 'required|max:255',

        ];
    }
}
