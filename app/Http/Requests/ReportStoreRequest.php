<?php

namespace App\Http\Requests;


use App\AffixType;
use App\CommunicationStatus;
use App\Gender;
use App\SignContractStatus;
use App\StarSource;
use Dingo\Api\Http\FormRequest;
use Illuminate\Validation\Rule;

class ReportStoreRequest extends FormRequest
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
    ];
    }
}
