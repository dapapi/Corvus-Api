<?php

namespace App\Http\Controllers;

use Gregwar\Captcha\CaptchaBuilder;
use Gregwar\Captcha\PhraseBuilder;
use Illuminate\Support\Facades\Request;
use Qiniu\Auth;

class ServiceController extends Controller
{

    public function cloudStorageToken()
    {
        $auth = new Auth(config('app.QINIU_ACCESS_KEY'), config('app.QINIU_SECRET_KEY'));
        $upToken = $auth->uploadToken(config('app.QINIU_BUCKET'), null, 3600, null);
        return $this->response->array(['data' => ['token' => $upToken]]);
    }

    /**
     * 返回图片验证码
     * @param ImageCodeRequest $request
     * @return 图片验证码
     */
    public function imageVerificationCode(Request $request)
    {
        $payload = $request->all();
        $width = isset($payload['width']) ? $payload['width'] : 240;
        $height = isset($payload['height']) ? $payload['height'] : 88;

        //crate image
        $phraseBuilder = new PhraseBuilder();
        $phrase = $phraseBuilder->build(4, '0123456789');
        $builder = new CaptchaBuilder($phrase, $phraseBuilder);
        $builder->setBackgroundColor(255,255,255);
        $builder->build($width, $height);

        $code = $builder->getPhrase();

        return response($builder->get(), 200)->header('Content-Type', 'image/jpeg');
    }

    /**
     * 获取短信验证码
     *
     * @param SendSMSCodeRequest $request
     */
    public function requestSMSCode(Request $request)
    {
        $resultJSON = ['success' => false];

        $payload = $request->all();
        $imageCode = $payload['image_code'];
        $telephone = $payload['telephone'];

        if (is_null($telephone)){
            $result['message'] = '手机号不能为空';
            return response()->json($result);
        }

        if (is_null($imageCode)){
            $result['message'] = '图片验证码不能为空';
            return response()->json($result);
        }

        return $this->sendSMSCode($telephone, $imageCode);
    }

    /**
     * 获取短信验证码
     *
     * @param $telephone
     * @param $imageCode
     * @return $this|\Illuminate\Http\JsonResponse
     */
    public function sendSMSCode($telephone, $imageCode)
    {
        $resultJSON = ['success' => false];

        if (!Utils::verifyTelephone($telephone)) {
            $result['message'] = '手机号格式错误';
            return response()->json($result);
        }

        if ($imageCode != Session::get("image_code")) {
            $result['message'] = '图片验证码不正确';
            return response()->json($result);
        }

        #生成随机号码
        $pool = '0123456789';
        $randomString = substr(str_shuffle(str_repeat($pool, 5)), 0, 4);

        #发送短信
        $singleSender = new SmsSingleSender(env('QCLOUD_SMS_APPID'), env('QCLOUD_SMS_KEY'));
        $params = array($randomString, "30");
        $result = $singleSender->sendWithParam("86", $telephone, env('QCLOUD_SMS_TEMPLATE_ID'), $params, "", "", "");
        $resultObject = json_decode($result);

        #查询发送结果
        if ($resultObject->result !== 0) {
            $resultJSON['message'] = '发送短信失败';
            return response()->json($resultJSON);
        }

        //save telephone and code to session
        Session::put('tp_' . $telephone, $randomString);
        Session::save();
        $cookie = cookie('_time', time(), env('SMS_CODE_TIME'));

        $resultJSON['success'] = true;
        return response()->json($resultJSON)->withCookie($cookie);
    }


    public function forgetSmsTime()
    {
//        Session::forget('_time');
        return response()->json(['success' => true])->withCookie(Cookie::forget('_time'));
    }
}
