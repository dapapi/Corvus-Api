<?php

namespace App\Http\Requests;

use App\AffixType;
use Dingo\Api\Http\FormRequest;
use Illuminate\Validation\Rule;

class AffixQueryRequest extends FormRequest
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
            'type' => Rule::in([AffixType::DEFAULT, AffixType::STAT_BULLETIN, AffixType::MONOLOGUE_VIDEO]),
        ];
    }
}
