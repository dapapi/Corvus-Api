<?php

namespace App\Http\Controllers\Wechat;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Repositories\Wechat\OfficialRepository;
use App\Models\UserWechatInfo;
use App\Models\UserWechatOpenId;
use Carbon\Carbon;
use Exception;
use GuzzleHttp\Client;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class OpenPlatformController extends Controller
{
    public function __construct() {

    }

    public function getLoginUrl() {
        $url = 'https://open.weixin.qq.com/connect/qrconnect?appid=' . env('WECHAT_OPEN_PLATFORM_APPID') . '&redirect_uri=' . env('WECHAT_OPEN_PLATFORM_OAUTH_CALLBACK') . '&response_type=code&scope=snsapi_login&state=jack#wechat_redirect';
        return $this->response->array(['url' => $url]);
    }

    /**
     * @param Request $request
     * @return \Illuminate\Http\RedirectResponse|\Illuminate\Routing\Redirector
     */
    public function oauthCallback(Request $request) {
        $code = $request->get('code');
        //TODO - 完善跨站攻击
        $state = $request->get('state');
        if (!$code) {
            return redirect(env('MOE_DOMAIN') . '/errors/403');
        }
        $appId = env('WECHAT_OPEN_PLATFORM_APPID');

        # 获取AccessToken
        $url = 'https://api.weixin.qq.com/sns/oauth2/access_token?appid=' . env('WECHAT_OPEN_PLATFORM_APPID') . '&secret=' . env('WECHAT_OPEN_PLATFORM_SECRET') . '&code=' . $code . '&grant_type=authorization_code';
        $client = new Client();
        $response = $client->request('get', $url);
        if ($response->getStatusCode() != 200) {
            return redirect(env('MOE_DOMAIN') . '/errors/403');
        }
        $body = json_decode($response->getBody());
        $openId = isset($body->openid) ? $body->openid : null;
        # 获取UserWechatInfo
        $userWechatInfo = null;
        if (isset($body->unionid)) {
            $userWechatInfo = UserWechatInfo::where('union_id', $body->unionid)->first();
        } else if ($openId) {
            $userWechatOpenId = UserWechatOpenId::where('open_id', $body->openid)
                ->where('app_id', $appId)
                ->where('type', UserWechatOpenId::TYPE_OPEN)
                ->first();
            if ($userWechatOpenId) {
                $userWechatInfo = $userWechatOpenId->userWechatInfo;
            }
        } else {
            return redirect(env('MOE_DOMAIN') . '/errors/403');
        }

        if (!$userWechatInfo) {
            # 创建UserWechatInfo
            // 获取用户信息
            $accessToken = $body->access_token;
            $getUeserInfoUrl = 'https://api.weixin.qq.com/sns/userinfo?openid=' . $body->openid . '&access_token=' . $accessToken;
            $userInfoResponse = $client->request('get', $getUeserInfoUrl);
            if ($userInfoResponse->getStatusCode() != 200) {
                return redirect(env('MOE_DOMAIN') . '/errors/403');
            }
            $userInfo = json_decode($userInfoResponse->getBody());
            $userWechatArray = [
                'union_id' => $userInfo->unionid,
                'nickname' => $userInfo->nickname,
                'gender' => $userInfo->sex,
                'avatar' => $userInfo->headimgurl,
                'language' => $userInfo->language,
                'city' => $userInfo->city,
                'province' => $userInfo->province,
                'country' => $userInfo->country,
            ];
            $loginUser = Auth::guard('api')->user();
            if ($loginUser) {
                $userWechatArray['user_id'] = $loginUser->id;
            }

            try {
                $userWechatInfo = UserWechatInfo::create($userWechatArray);
            } catch (Exception $exception) {
                Log::error($exception->getMessage());
                return redirect(env('MOE_DOMAIN') . '/errors/500');
            }
        }

        # 更新OpenId信息
        $wechatRepos = new OfficialRepository();
        try {
            $wechatRepos->updateWechatOpenId($openId, $appId, UserWechatOpenId::TYPE_OPEN, $userWechatInfo);
        } catch (Exception $exception) {
            Log::error($exception->getMessage());
            return redirect(env('MOE_DOMAIN') . '/errors/500');
        }

        # 获取User信息
        $user = $userWechatInfo->user;
        if (!$user) {
            # 第一次来，需要绑定手机号
            $now = Carbon::now();

            $token = $userWechatInfo->registTokens()->orderBy('updated_at', 'desc')->first();
            if ($token) {
                $token->update([
                    'token' => str_random(32),
                    'updated_at' => $now
                ]);
            } else {
                $token = $userWechatInfo->registTokens()->create([
                    'token' => str_random(32),
                    'expired_in' => env('REGIST_TOKEN_EXPIRED_IN')
                ]);
            }
            return redirect(env('WECHAT_OPEN_PLATFORM_REDIRECT_URL') . '?bind_token=' . $token->token);
        }

        $accessToken = $user->createToken('wechat open login')->accessToken;
        //不是第一次，已经绑定过用户，直接登录
        return redirect(env('WECHAT_OPEN_PLATFORM_REDIRECT_URL') . '?access_token=' . $accessToken);
    }

}
