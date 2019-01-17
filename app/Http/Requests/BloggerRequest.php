<?php

namespace App\Http\Requests;

use App\BloggerLevel;
use App\BloggerType;
use App\CommunicationStatus;
use App\Gender;
use App\SignContractStatus;
use Dingo\Api\Http\FormRequest;
use Illuminate\Validation\Rule;

class BloggerRequest extends FormRequest
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
       //     'nickname' => 'required|unique:bloggers|max:255',
            'nickname' => ['required',
                            'unique:bloggers',
                            'max:255'
                            ],
            'platform_id'=> 'nullable', // 平台
            'type_id' => 'required|numeric',
            'communication_status' => Rule::in([
                CommunicationStatus::ALREADY_SIGN_CONTRACT,
                CommunicationStatus::HANDLER_COMMUNICATION,
                CommunicationStatus::TALENT_COMMUNICATION,
                CommunicationStatus::UNDETERMINED,
                CommunicationStatus::WEED_OUT,
                CommunicationStatus::CONTRACT,
                CommunicationStatus::NO_ANSWER,
            ]),
            'intention' => 'boolean',
            'intention_desc' => 'max:500',
            'sign_contract_at' => 'date',
            'level' => Rule::in([
                BloggerLevel::S,
                BloggerLevel::A,
                BloggerLevel::B,
                BloggerLevel::C
            ]),
            'hatch_star_at' => 'date',//孵化期开始时间
            'hatch_end_at' => 'date',//孵化期结束时间
            'producer_id' => 'nullable',//制作人
            'sign_contract_status' => Rule::in([
                SignContractStatus::SIGN_CONTRACTING,
                SignContractStatus::ALREADY_SIGN_CONTRACT,
                SignContractStatus::ALREADY_TERMINATE_AGREEMENT,
            ]),
            'icon' => 'max:255', // 图片
            'desc' => 'nullable',//描述/备注
            'avatar' => 'max:500',
            'gender' => Rule::in([Gender::MAN, Gender::WOMAN]),
            'cooperation_demand' => 'max:500',//合作需求
            'terminate_agreement_at' => 'date',//解约日期
            'sign_contract_other' => 'boolean',//是否签约其他公司
            'sign_contract_other_name' => 'max:255',//签约公司名称
        ];
    }
    public function messages(){
        return [
           'nickname.unique' => '已存在'
        ];
    }
}
