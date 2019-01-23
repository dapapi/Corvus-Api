<?php

namespace App\Http\Transformers;

use App\Models\PersonalDetail;

use League\Fractal\TransformerAbstract;

class DetailTransformer extends TransformerAbstract
{
    public function transform(PersonalDetail $detail)
    {

        return [
            'id' => hashid_encode($detail->id),
            'id_card_url' => $detail->id_card_url,
            'passport_code' => $detail->passport_code,
            'id_number' => $detail->id_number,
            'card_number_one' => $detail->card_number_one,
            'card_number_two' => $detail->card_number_two,
            'credit_card' => $detail->credit_card,
            'accumulation_fund' => $detail->accumulation_fund,
            'opening' => $detail->opening,
            'last_company' => $detail->last_company,
            'responsibility' => $detail->responsibility,
            'contract' => $detail->contract,
            'address' => $detail->address,
            'entry_time' => $detail->entry_time,

        ];
    }
}