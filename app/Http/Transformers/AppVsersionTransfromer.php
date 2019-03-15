<?php

namespace App\Http\Transformers;

use App\Models\AppVersion;
use League\Fractal\TransformerAbstract;

class AppVsersionTransfromer extends TransformerAbstract
{
    public function transform(AppVersion $appVersion)
    {
        return [
            'version_code'  =>  $appVersion->version_code,
            'update_log'    =>  $appVersion->update_log,
            'update_install'    =>  $appVersion->update_install,
            'download_url'  =>  $appVersion->download_url
        ];
    }
}