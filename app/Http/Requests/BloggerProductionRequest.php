<?php

namespace App\Http\Requests;

use Dingo\Api\Http\FormRequest;


class BloggerProductionRequest extends FormRequest
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
            'nickname' => 'required|max:255', // 昵称
            'videoname'=> 'required|max:255', // 视屏名称
            'release_time' => 'date',//发布时间
            'read_proportion' => 'max:255', // 装换率
            'link' => 'max:500',//链接
            'advertising' => 'boolean',//是否有广告
        ];
    }
}
