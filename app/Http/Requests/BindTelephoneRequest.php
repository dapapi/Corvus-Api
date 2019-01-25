<?php

namespace App\Http\Requests;

use Dingo\Api\Http\FormRequest;

class BindTelephoneRequest extends FormRequest {
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
            'token' => ['required'],
            'sms_code' => ['required', 'sms_code'],
            'password' => 'required|min:6|max:18',
//            'password_confirmation' => 'required|min:6|max:18 ',
        ];
    }

    public function messages() {
        return [
            'telephone.required' => '手机号不能为空',
            'telephone.regex' => '手机号格式错误',
            'device.required' => '设备号不能为空',
            'token.required' => 'Token不能为空',
            'sms_code.required' => '短信验证码不能为空',
            'sms_code.sms_code' => '短信验证码错误',
            'password.required' => '密码不能为空',
            'password.confirmed' => '两次密码不一致',
            'password.between' => '密码必须在 :min 到 :max 个字符之间',
//            'password_confirmation.required' => '确认密码不能为空',
//            'password_confirmation.between' => '确认密码必须在 :min 到 :max 个字符之间',
        ];
    }
}
