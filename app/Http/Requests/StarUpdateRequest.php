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
            'intention' => Rule::in([0,1]),
            'intention_desc' => 'max:500',
            'sign_contract_other' => Rule::in([0,1]),
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
}
