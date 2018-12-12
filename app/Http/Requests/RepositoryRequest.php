<?php

namespace App\Http\Requests;

use Dingo\Api\Http\FormRequest;

class RepositoryRequest extends FormRequest
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

            'title' => 'max:255', // 昵称
            'scope'=> 'nullable', // 视屏名称
//            'desc' => 'max:255', // 装换率
            'accessory' => 'max:500',//链接
            'stick' => 'nullable',//是否有广告

        ];
    }
}
