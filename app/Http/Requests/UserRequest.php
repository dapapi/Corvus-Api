<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UserRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        return false;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */



    public function rules()
    {
        return [
            'en_name' => 'required|max:255',
            'gender' => 'required',
            'id_number' => 'required',
            'phone' => 'required',
            'political' => 'required',
            'marriage' => 'required',
            'cadastral_address' => 'required',
            'national' => 'required',
            'current_address' => 'required',
            'gender' => 'required',
            'id_number' => 'required',
            'birth_time' => 'date',
            'entry_time' => 'date',
            'blood_type' => 'required',
            'status' => 'required',
        ];


    }
}
