<?php

namespace App\Http\Controllers;

use Qiniu\Auth;

class ServiceController extends Controller
{

    public function cloudStorageToken()
    {
        $auth = new Auth(config('app.QINIU_ACCESS_KEY'), config('app.QINIU_SECRET_KEY'));
        $upToken = $auth->uploadToken(config('app.QINIU_BUCKET'), null, 3600, null);
        return $this->response->array(['data' => ['token' => $upToken]]);
    }
}
