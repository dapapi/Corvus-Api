<?php

namespace App\Http\Controllers;

use App\Http\Requests\GetRequestTokenRequest;
use App\Http\Requests\SendSmsRequest;
use App\Http\Transformers\RequestTokenTransformer;
use App\Models\RequestVerityToken;
use App\Sms\VerifyCodeSms;
use Exception;
use Illuminate\Support\Facades\Log;
use Overtrue\EasySms\EasySms;
use Overtrue\EasySms\Exceptions\GatewayErrorException;
use Overtrue\EasySms\Exceptions\NoGatewayAvailableException;
use Qiniu\Auth;
use Webpatser\Uuid\Uuid;

class ServiceController extends Controller {

    public function cloudStorageToken()
    {
        $auth = new Auth(config('app.QINIU_ACCESS_KEY'), config('app.QINIU_SECRET_KEY'));
        $upToken = $auth->uploadToken(config('app.QINIU_BUCKET'), null, 3600, null);
        return $this->response->array(['data' => ['token' => $upToken]]);
    }

    /**
     * @throws Exception
     */
    public function requestToken(GetRequestTokenRequest $request) {

        $token = Uuid::generate(4)->string;
        $payload = $request->all();

        $requestToken = RequestVerityToken::where('device', $payload['device'])->first();

        if ($requestToken) {
            $requestToken->token = $token;
            try {
                $requestToken->save();
            } catch (Exception $e) {
                return $this->response->errorInternal('获取Token失败');
            }
        } else {
            try {
                $requestToken = RequestVerityToken::create(['token' => $token, 'device' => $payload['device'], 'expired_in' => env('Token_Expired_In', 1800)]);
            } catch (Exception $e) {
                return $this->response->errorInternal('获取Token失败');
            }
        }

        return $this->response->item($requestToken, new RequestTokenTransformer());
    }

    /**
     * 获取短信验证码
     * @param SendSmsRequest $request
     * @return \Dingo\Api\Http\Response|void
     * @throws \Overtrue\EasySms\Exceptions\InvalidArgumentException
     * @throws \Overtrue\EasySms\Exceptions\NoGatewayAvailableException
     */
    public function sendSMSCode(SendSmsRequest $request) {
        $payload = $request->all();
        $telephone = $payload['telephone'];
        $device = $payload['device'];
        $token = $payload['token'];

        #查询该请求
        $requestToken = RequestVerityToken::where('device', $device)->where('token', $token)->first();

        #生成随机号码
        $pool = '0123456789';
        $randomString = substr(str_shuffle(str_repeat($pool, 5)), 0, 4);

        #发送短信
        $easySms = new EasySms(config('sms'));
        dd(config('sms'));
        try {
            $easySms->send($telephone, new VerifyCodeSms($randomString));
        } catch (GatewayErrorException $exception) {
            Log::error($exception->getMessage());
            return $this->response->errorBadRequest('发送短信失败');
        } catch (NoGatewayAvailableException $exception) {
            dd($exception->getExceptions());
            return ;
        }

        #保存验证码
        $requestToken->sms_code = $randomString;
        $requestToken->telephone = $telephone;

        try {
            $requestToken->save();
        } catch (Exception $exception) {
            Log::error($exception->getMessage());
            return $this->response->errorInternal('缓存短信验证码失败');
        }

        return $this->response->accepted('','发送成功');
    }

    public function getQiniuToken() {
        $qiniuAuth = new Auth(env('QINIU_ACCESS_KEY'), env('QINIU_SECRET_KEY'));
        try {
            $qiniuToken = $qiniuAuth->uploadToken(env('QINIU_BUCKET'));
        } catch (Exception $exception) {
            Log::error($exception->getMessage());
            return $this->response->errorInternal('生成七牛上传token失败');
        }
        return $this->response->array(['token' => $qiniuToken]);
    }

    public function getImageQiniuToken() {
        $qiniuAuth = new Auth(env('QINIU_ACCESS_KEY'), env('QINIU_SECRET_KEY'));
        try {
            $qiniuToken = $qiniuAuth->uploadToken(env('QINIU_BUCKET', null, 3600, ['mimeLimit' => 'image/*']));
        } catch (Exception $exception) {
            Log::error($exception->getMessage());
            return $this->response->errorInternal('生成七牛上传token失败');
        }
        return $this->response->array(['token' => $qiniuToken]);
    }
}
