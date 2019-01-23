<?php

namespace App\Http\Transformers;

use App\Models\RequestVerityToken;
use League\Fractal\TransformerAbstract;

class RequestTokenTransformer extends TransformerAbstract {

    protected $availableIncludes = [];

    public function transform(RequestVerityToken $requestVerityToken) {
        return [
            'token' => $requestVerityToken->token,
            'created_at' => $requestVerityToken->created_at->formatLocalized('%Y-%m-%d %H:%I'),//时间去掉秒,,
            'expired_in' => (int)$requestVerityToken->expired_in
        ];
    }


}