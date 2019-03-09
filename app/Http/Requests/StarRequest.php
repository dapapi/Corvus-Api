<?php

namespace App\Http\Requests;


use App\AffixType;
use App\CommunicationStatus;
use App\Gender;
use App\SignContractStatus;
use App\StarSource;
use Dingo\Api\Http\FormRequest;
use Illuminate\Validation\Rule;

class StarRequest extends FormRequest
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
            'name' => 'required|max:255',
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
            'intention_desc' => 'max:255',
            'sign_contract_other' => Rule::in([1,2]),
            'sign_contract_other_name' => 'max:255',
            'sign_contract_at' => 'date',
            'sign_contract_status' => Rule::in([
                SignContractStatus::SIGN_CONTRACTING,
                SignContractStatus::ALREADY_SIGN_CONTRACT,
                SignContractStatus::ALREADY_TERMINATE_AGREEMENT,
            ]),
            'terminate_agreement_at' => 'date',

            'affix' => 'array',
            'affix.*.title' => 'required|max:255',
            'affix.*.size' => 'required|numeric|min:0',
            'affix.*.url' => 'required|max:500',
            'affix.*.type' => ['required', Rule::in([AffixType::DEFAULT, AffixType::STAT_BULLETIN, AffixType::MONOLOGUE_VIDEO,AffixType::STAR_PLAN,AffixType::INTRODUCE_ONESELF,AffixType::OTHER])],
        ];
    }
//    public function messages()
//    {
//        return [];
//    }
}
