<?php
namespace App\Http\Requests;

use Dingo\Api\Http\FormRequest;
use Illuminate\Validation\Rule;
use App\Models\Role;


class RoleRequest extends FormRequest
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
            'name'  =>  'required|max:50',
            'group_id'  =>  'required|numeric',
//            'description'  =>  'required|max:50',
//            'sort_number'  =>  'required|max:50',
        ];
    }
}
