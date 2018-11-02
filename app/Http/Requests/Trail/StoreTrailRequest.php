<?php

namespace App\Http\Requests\Trail;

use Illuminate\Foundation\Http\FormRequest;

class StoreTrailRequest extends FormRequest
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
            'title' => 'required',
            'brand' => 'required',
            'principal_id' => 'required',
            'client_id' => 'required',
            'contact_id' => 'required',
            'artist_id' => 'required',
            'desc' => 'nullable',
        ];
    }


    public function messages()
    {
        return [
            'title' => '线索名称',
            'brand' => '品牌',
            'principal_id' => '负责人',
            'client_id' => '关联客户',
            'contact_id' => '关联联系人',
            'artist_id' => '关联艺人',
            'desc' => '描述',
        ];
    }
}
