<?php

namespace App\Http\Requests\Excel;

use Dingo\Api\Http\FormRequest;

class ExcelImportRequest extends FormRequest
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
            'file' => 'required|mimetypes:application/vnd.openxmlformats-officedocument.spreadsheetml.sheet|mimes:xlsx|max:10000'
        ];
    }
}
