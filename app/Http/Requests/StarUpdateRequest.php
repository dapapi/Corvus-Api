<?php

namespace App\Http\Requests;


use App\CommunicationStatus;
use App\Gender;
use App\SignContractStatus;
use App\StarSource;
use Dingo\Api\Http\FormRequest;
use Illuminate\Validation\Rule;

class StarUpdateRequest extends FormRequest
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
            'name' => 'max:255',//去掉了必填
            'gender' => Rule::in([Gender::MAN, Gender::WOMAN]),
            'avatar' => 'max:500',
            'birthday' => 'date',
            'phone' => ['digits:11', 'regex:/^1[34578]\d{9}$/'],
            'desc' => 'nullable',
            'wechat' => 'max:30',
            'email' => 'email',
            'source' => Rule::in([
                StarSource::ON_LINE,
                StarSource::OFFLINE,
                StarSource::TRILL,
                StarSource::WEIBO,
                StarSource::CHENHE,
                StarSource::BEIDIAN,
                StarSource::YANGGUANG,
                StarSource::ZHONGXI,
                StarSource::PAPITUBE,
                StarSource::AREA_EXTRA,
            ]),
            'communication_status' => Rule::in([
                CommunicationStatus::ALREADY_SIGN_CONTRACT,
                CommunicationStatus::HANDLER_COMMUNICATION,
                CommunicationStatus::TALENT_COMMUNICATION,
                CommunicationStatus::UNDETERMINED,
                CommunicationStatus::WEED_OUT,
                CommunicationStatus::CONTRACT,
                CommunicationStatus::NO_ANSWER,
            ]),
            'intention' => Rule::in([1,2]),
            'intention_desc' => 'max:500',
            'sign_contract_other' => Rule::in([1,2]),
            'sign_contract_other_name' => 'max:255',
            'sign_contract_at' => 'date',
            'sign_contract_status' => Rule::in([
                SignContractStatus::SIGN_CONTRACTING,
                SignContractStatus::ALREADY_SIGN_CONTRACT,
                SignContractStatus::ALREADY_TERMINATE_AGREEMENT,
            ]),
            'terminate_agreement_at' => 'date',
        ];
    }

    public function messages()
    {
        return [
            'name.required' => '姓名必填',
            'name.max'  =>  '姓名长度不能超过255个字',
            'gender.in'   =>  '性别不正确',
            'birthday.date' =>  '生日必须是日期类型',
            'phone.digits'  =>  '电话必须是11位数字',
            'phone.regex'   =>  '电话格式不正确',
            'wechat.max'    =>  '微信长度不能超过30位',
            'email' =>  '邮箱格式不正确',
            'source'    =>  '艺人来源不正确',
            'communication_status.in'   =>  '沟通状态不正确',
            'intention.in'  =>  '与我公司签约意向输入不正确',
            'intention_desc'    =>  '不与我公司签约原因长度不能超过255个字',
            'sign_contract_other.in'    =>  '是否与其他公司签约输入不正确',
            'sign_contract_other_name.max'  =>  '签约公司名称不能超过255个字',
            'sign_contract_at.date' =>  '签约日期必须是日期类型',
            'sign_contract_status.in'   =>  '签约状态不正确',
            'terminate_agreement_at.date'   =>  '解约时间必须是时间类型',
            'affix.array'   =>  '附件信息不正确',
            'affix.*.title.required'    =>  '附件标题不能为空',
            'affix.*.title.max' =>  '附件标题不能超过255个字',
            'affix.*.size.required' =>  '附件大小必传',
            'affix.*.size.numeric'  =>  '附件大小必须是数字',
            'affix.*.url.required'  =>  '附件地址不能为空',
            'affix.*.url.max'   =>  '附件地址太长了',
            'affix.*.type.required' =>  '附件类型必传',
            'affix.*.type.in'   =>  '附件类型不正确',
        ];
    }
}
