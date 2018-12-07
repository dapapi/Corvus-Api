<?php

namespace App\Http\Requests;


use Dingo\Api\Http\FormRequest;

class MergeUserRequest extends FormRequest {
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize() {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules() {
        return [
            'telephone' => ['required', 'regex:/^1[34578]\d{9}$/'],
            'device' => ['required'],
//            'token' => ['required'],
            'bind_token' => ['required'],
            'sms_code' => ['required', 'sms_code:telephone,device,token']
        ];
    }


    public function messages() {
        return [
            'telephone.required' => '手机号不能为空',
            'telephone.regex' => '手机号格式错误',
            'device.required' => '设备号不能为空',
//            'token.required' => 'Token不能为空',
            'sms_code.required' => '短信验证码不能为空',
            'sms_code.sms_code' => '短信验证码错误',
            'bind_token.required' => 'BindToken不能为空'
        ];
    }
}
