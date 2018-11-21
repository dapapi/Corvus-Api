<?php
namespace App\Http\Requests;

use Dingo\Api\Http\FormRequest;
use Illuminate\Validation\Rule;
use App\Models\Work;


class WorkRequest extends FormRequest
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
            'name'  =>  'required|max:50',
            'director'  =>  'required|max:50',
            'release_time'  =>  'required|date',
            'works_type'  =>  Rule::in([
              Work::MOVIE,
              Work::TV_PLAY,
              Work::VARIETY_SHOW,
              Work::NET_PLAY,
            ]),
            'role'  =>  'required|max:50',
            'co_star' =>  'required|max:100',
        ];
    }
}
