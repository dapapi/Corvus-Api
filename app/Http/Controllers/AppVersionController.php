<?php

namespace App\Http\Controllers;

use App\Http\Transformers\AppVsersionTransfromer;
use App\Models\AppVersion;
use Illuminate\Http\Request;

class AppVersionController extends Controller
{
    //比较客户端版本与最新版本，最新版本大于客户端版本返回最新版本信息
    public function getNewAppVersion(Request $request)
    {
        $version = $request->get("version");
        $pt = $request->get('pt');
        $app_version = AppVersion::where('pt',$pt)->orderBy('id','desc')->first('version_code');
        if ($app_version && $version < $app_version->version_code){
            return $this->response->item($app_version,new AppVsersionTransfromer());
        }
    }

    public function addAppVersion(Request $request)
    {
        $app_version = [
            'pt'    =>  $request->get('pt'),
            'version_code'  =>  $request->get('version_code'),
            'update_log'    =>  $request->get('update_log'),
            'update_install'    =>  $request->get('update_install'),
            'download_url'  =>  $request->get('download_url'),
        ];
        AppVersion::create($app_version);
    }

    public function updateAppVersion(Request $request,AppVersion $appversion)
    {
        $all = $request->all();
        $appversion->save($all);
        $this->response->item($appversion,new AppVersion());
    }
}
