<?php

namespace App\Http\Controllers\Wechat;


use App\Http\Controllers\Controller;
use App\Exceptions\SystemInternalException;
use App\Http\Requests\JsTicketRequest;
use App\Http\Requests\MergeUserRequest;
use App\Http\Requests\OauthCallbackRequest;
use App\Http\Requests\OauthRequest;
use App\Models\RegistToken;
use App\Models\UserWechatInfo;
use App\Repositories\UserRepository;
use App\Repositories\Wechat\OfficialRepository;
use App\WechatOauthWhiteList;
use Carbon\Carbon;
use EasyWeChat\Factory;
use Exception;
use Illuminate\Support\Facades\Log;
use Psr\SimpleCache\InvalidArgumentException;


class OfficialController extends Controller {
    private $repository;
    private $app;

    public function __construct(OfficialRepository $repository) {
        $this->repository = $repository;
        $this->app = Factory::officialAccount(config('wechat.official_account.default'));
    }

    public function oauth(OauthRequest $request) {
        $callbackUrl = $request->get('callback');
        //白名单配置
        $parseUrlArray = parse_url($callbackUrl);
        if (isset($parseUrlArray['host'])) {
            $whitelist = WechatOauthWhiteList::where('domain', $parseUrlArray['host'])->where('status', WechatOauthWhiteList::STATUS_NORMAL)->first();
            if (!$whitelist) {
                abort(403);
            }
        } else {
            abort(403);
        }
        $response = $this->app->oauth->scopes(['snsapi_userinfo'])
            ->redirect(env('WECHAT_OFFICIAL_OAUTH_URL') . '/wechat/oauth/callback?redirect=' . urlencode($callbackUrl));
        return $response;
    }

    public function oauthCallback(OauthCallbackRequest $request) {
        $redirect = $request->get('redirect');
        try {
            # 获取 OAuth 授权结果用户信息
            $userWechatInfo = $this->repository->findOrCreateUserWechatInfo($this->app);
            # 获取用户信息
            $user = $userWechatInfo->user;
            if (!$user) {
                //第一次来，需要绑定手机号
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

                $hasParams = explode('?', $redirect);
                if (count($hasParams) > 1) {
                    $redirect .= '&bind_token=' . $token->token;
                } else {
                    $redirect .= '?bind_token=' . $token->token;
                }
                return redirect($redirect);
            }

        } catch (SystemInternalException $exception) {
            Log::error($exception->getMessage());
            return $this->response->errorInternal($exception->getMessage());
        } catch (Exception $exception) {
            Log::error($exception->getMessage());
            return $this->response->errorInternal('创建Token失败');
        }

        //不是第一次，已经绑定过用户，直接登录
        $accessToken = $user->createToken('wechat login')->accessToken;
        $hasParams = explode('?', $redirect);
        if (count($hasParams) > 1) {
            $redirect .= '&access_token=' . $accessToken;
        } else {
            $redirect .= '?access_token=' . $accessToken;
        }
        return redirect($redirect);
    }

    /**
     * @param MergeUserRequest $request
     */
    public function mergeUser(MergeUserRequest $request) {
        $telephone = $request->get('telephone');
        $token = $request->get('bind_token');
        # 获取registToken对象
        $registToken = RegistToken::where('token', $token)->first();
        if (!$registToken) {
            return $this->response->errorForbidden('非法请求');
        }
        $now = Carbon::now();
        if (Carbon::parse($registToken->updated_at)->addSecond($registToken->expired_in)->lt($now)) {
            return $this->response->errorBadRequest('该Token已经过期');
        }

        if ($registToken->tokenable_type != UserWechatInfo::class) {
            return $this->response->errorBadRequest('该Token类型错误');
        }
        try {
            # 获取用户微信信息
            $userWechatInfo = UserWechatInfo::findOrFail($registToken->tokenable_id);
            //通过手机号查找用户
            $userRepo = new UserRepository();
            $user = $userRepo->findOrCreateFromPlatform($telephone, $userWechatInfo);
        } catch (SystemInternalException $exception) {
            Log::error($exception->getMessage());
            return $this->response->errorInternal($exception->getMessage());
        } catch (Exception $exception) {
            Log::error($exception->getMessage());
            return $this->response->errorBadRequest('该Token数据错误');
        }
        //删除数据
        try {
            $registToken->delete();
        } catch (Exception $exception) {
            Log::error($exception->getMessage());
        }

        //登录
        $accessToken = $user->createToken('wechat merge user')->accessToken;
        return $this->response->array([
            'access_token' => $accessToken
        ]);
    }

    public function accessToken() {
        $accessToken = $this->app->access_token;
        return $accessToken->getToken();
    }

    public function jsTicket() {
        $ticket = $this->app->jssdk->getTicket();
        return $this->response->array($ticket);
    }

    /**
     * 获取js config
     * @param JsTicketRequest $request
     * @return void
     */
    public function jsConfig(JsTicketRequest $request) {
        $url = $request->get('url');
        if ($url) {
            $this->app->jssdk->setUrl($url);
        }
        try {
            $configArray = $this->app->jssdk->buildConfig(['onMenuShareTimeline', 'onMenuShareAppMessage', 'scanQRCode', 'getNetworkType'], true, false, false);
        } catch (InvalidArgumentException $e) {
            Log::error($e->getMessage());
            return $this->response->errorInternal('获取失败');
        } catch (Exception $e) {
            Log::error($e->getMessage());
            return $this->response->errorInternal('获取失败');
        }
        return $this->response->array($configArray);
    }

    /**
     * 微信公众号接受信息操作
     * @return mixed
     * @throws \EasyWeChat\Kernel\Exceptions\BadRequestException
     * @throws \EasyWeChat\Kernel\Exceptions\InvalidArgumentException
     * @throws \EasyWeChat\Kernel\Exceptions\InvalidConfigException
     */
    public function serve() {
        $this->app->server->push(function ($message) {
            switch ($message['MsgType']) {
                case 'event':
                    return '收到事件消息';
                    break;
                case 'text':
                    return '收到文字消息';
                    break;
                case 'image':
                    return '收到图片消息';
                    break;
                case 'voice':
                    return '收到语音消息';
                    break;
                case 'video':
                    return '收到视频消息';
                    break;
                case 'location':
                    return '收到坐标消息';
                    break;
                case 'link':
                    return '收到链接消息';
                    break;
                case 'file':
                    return '收到文件消息';
                default:
                    return '收到其它消息';
                    break;
            }
        });
        return $this->app->server->serve();
    }
}
