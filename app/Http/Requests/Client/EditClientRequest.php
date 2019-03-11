<?php

namespace App\Http\Requests\Client;


use App\Models\Client;
use Dingo\Api\Http\FormRequest;
use Illuminate\Support\Facades\DB;

class EditClientRequest extends FormRequest
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
        $company = request()->get('company',null);
        $id = Client::where('company',$company)->value('id');
        return [
            'company' => 'nullable|unique:clients,company,'.$id,
            'grade' => 'nullable|numeric',
            'type' => 'nullable|numeric',
            'province' => 'nullable',
            'city' => 'nullable',
            'district' => 'nullable',
            'address' => 'nullable',
            'principal_id' => 'nullable',
            'size' => 'nullable',
            'keyman' => 'nullable',
            'desc' => 'nullable',
        ];
    }
}
