<?php

namespace App\Http\Transformers;

use App\Models\Platform;
use League\Fractal\TransformerAbstract;

class PlatformTransformer extends TransformerAbstract
{
    public function transform(Platform $platform)
    {
        return [
            'id' => hashid_encode($platform->id),
            'name' => $platform->name,
            'logo' => $platform->logo,
            'active_logo' => $platform->active_logo,
            'url' => $platform->url,
        ];
    }
}