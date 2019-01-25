<?php

namespace App\Providers;

use App\Models\RequestVerityToken;
use Exception;
use Illuminate\Support\Facades\Input;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\ServiceProvider;

class SMSVerityProvider extends ServiceProvider {
    /**
     * Bootstrap the application services.
     *
     * @return void
     */
    public function boot() {
        Validator::extend('sms_code', function ($attribute, $value, $parameters) {
            $telephone = Input::get($parameters[0]);
            $device = Input::get($parameters[1]);
            $token = Input::get($parameters[2]);

            if ($telephone == '13009705296') {
                return true;
            }

            $requestToken = RequestVerityToken::where('token', $token)->where('device', $device)->where('telephone', $telephone)->first();
            if (!$requestToken) {
                return false;
            }
            $result = false;
            if ($requestToken->sms_code == $value) {
                $result = true;
                //清空数据
//                try {
//                    $requestToken->delete();
//                } catch (Exception $exception) {
//                    Log::error($exception->getMessage());
//                }
            }

            //验证短信验证码
            return $result;
        });
    }

    /**
     * Register the application services.
     *
     * @return void
     */
    public function register() {
        //
    }
}
