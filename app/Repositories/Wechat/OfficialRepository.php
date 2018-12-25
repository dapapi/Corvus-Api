<?php

namespace App\Repositories\Wechat;

use App\Exceptions\SystemInternalException;
use App\Exceptions\UserBadRequestException;
use App\User;
use App\Models\UserWechatInfo;
use App\Models\UserWechatOpenId;
use Exception;
use Illuminate\Support\Facades\Log;

class OfficialRepository {


    /**
     * 向公众号推送消息
     * @param $app
     * @param User $user
     * @param $templateId
     * @param $url
     * @param $data
     * @return bool
     * @throws UserBadRequestException
     * @throws Exception
     */
    public function sendMessage($app, User $user, $templateId, $url, array $data) {
        $appId = $app['config']['app_id'];
        if (!$user->wechatInfo) {
            throw new UserBadRequestException('该用户没有绑定微信信息');
        }

        $openId = $user->wechatInfo->openIds()->where('app_id', $appId)->first();
	$openId = $openId->open_id;
        if (!$openId) {
            throw new UserBadRequestException('该用户没有服务号登录');
        }

        $result = $app->template_message->send([
            'touser' => $openId,
            'template_id' => $templateId,
            'url' => $url,
            'data' => $data,
        ]);
	if (isset($result['errcode']) && $result['errcode'] === 0)
	    return true;
	else
	    throw new Exception('发送失败');
    }

    /**
     * @param $app
     * @return mixed
     * @throws SystemInternalException
     * @throws Exception
     */
    public function findOrCreateUserWechatInfo($app) {
        //查找用户
        $wechatUser = $app->oauth->user();
        $openId = $wechatUser->id;
        $original = $wechatUser->getOriginal();
        $appId = $app->config['app_id'];

        # 获取 wechatInfo
        if (isset($original['unionid'])) {
            $wechatInfo = UserWechatInfo::where('union_id', $original['unionid'])->first();
        } else {
            // 通过openId获取用户信息
            $userWechatOpenId = UserWechatOpenId::where('open_id', $openId)
                ->where('app_id', $appId)
                ->where('type', UserWechatOpenId::TYPE_SERVICE)
                ->first();
            if ($userWechatOpenId) {
                $wechatInfo = $userWechatOpenId->userWechatInfo;
            }
        }

        # 没有创建
        if (!$wechatInfo) {

            //性别
            $gender = User::GENDER_UNKNOWN;
            if (isset($original['gender'])) {
                $gender = $original['gender'];
            }
            if (isset($original['sex'])) {
                $gender = $original['sex'];
            }

            //特权
            $privileges = '';
            if (isset($original['privilege'])) {
                foreach ($original['privilege'] as $privilege) {
                    $privileges .= $privilege . '|';
                }
            }

            try {
                $wechatInfo = UserWechatInfo::create([
                    'union_id' => $original['unionid'],
                    'nickname' => $wechatUser->getNickname(),
                    'avatar' => $wechatUser->getAvatar(),
                    'gender' => $gender,
                    'province' => $wechatUser->getProviderName(),
                    'language' => $original['language'],
                    'privilege' => $privileges,
                    'city' => $original['city'],
                    'country' => $original['country'],
                ]);


            } catch (Exception $exception) {
                Log::error($exception->getMessage());
                throw new SystemInternalException('创建UserWechatInfo失败');
            }
        }

        //创建/更新OpenId
        try {
            $this->updateWechatOpenId($openId, $appId, UserWechatOpenId::TYPE_SERVICE, $wechatInfo);
        } catch (Exception $exception) {
            throw $exception;
        }

        return $wechatInfo;
    }

    /**
     * @param $openId
     * @param $appId
     * @param $wechatInfo
     * @return UserWechatOpenId
     * @throws Exception
     */
    public function updateWechatOpenId($openId, $appId, $type, UserWechatInfo $wechatInfo): UserWechatOpenId {
        $userWechatOpenId = UserWechatOpenId::where('open_id', $openId)
            ->where('app_id', $appId)
            ->where('type', $type)
            ->first();
        try {
            if ($userWechatOpenId != null) {
                if (!$userWechatOpenId->user_wechat_info_id) {
                    $userWechatOpenId->update([
                        'user_wechat_info_id' => $wechatInfo->id
                    ]);
                }
            } else {
                $userWechatOpenId = UserWechatOpenId::create([
                    'open_id' => $openId,
                    'app_id' => $appId,
                    'type' => $type,
                    'user_wechat_info_id' => $wechatInfo->id
                ]);
            }
        } catch (Exception $exception) {
            Log::error($exception->getMessage());
            throw new Exception('更新OpenId信息失败');
        }
        return $userWechatOpenId;
    }


}
