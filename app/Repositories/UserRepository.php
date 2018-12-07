<?php

namespace App\Repositories;

use App\Exceptions\SystemInternalException;
use App\Exceptions\UserBadRequestException;
use App\User;
use App\Models\UserWechatInfo;
//use App\Models\UserWorktileInfo;
use Exception;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class UserRepository {

    /**
     * @param $telephone
     * @return mixed
     * @throws SystemInternalException
     * @throws UserBadRequestException
     */
    public function findOrCreateByTelephone($telephone) {
        $user = Auth::guard('api')->user();
        if (!$user->telephone) {
            $user->telephone = $telephone;
            try {
                $user->save();
            } catch (Exception $exception) {
                throw new SystemInternalException('绑定手机号失败 UserRepository@findOrCreateByTelephone#1 - ' . $exception->getMessage());
            }
        }
        return $user;
    }

    public function store(array $userArray) {
        try {
            $user = User::create($userArray);
        } catch (Exception $exception) {
            throw $exception;
        }
        return $user;
    }

    /**
     * @param $telephone
     * @param $userPlatformInfo
     * @return mixed
     * @throws SystemInternalException
     */
    public function findOrCreateFromPlatform($telephone, $userPlatformInfo) {
        $user = $userPlatformInfo->user;

        if (!$user) {
            // 查找是否已经存在手机号
            $user = User::where('phone', $telephone)->first();
            if (!$user) {
//                //创建用户
//                try {
//                    $user = User::create([
//                        'nickname' => $userPlatformInfo->nickname,
//                        'telephone' => $telephone,
//                        'status' => User::STATUS_NORMAL,
//                        'avatar' => $userPlatformInfo->avatar
//                    ]);
//                } catch (Exception $exception) {
//                    Log::error($exception->getMessage());
//                    throw new SystemInternalException('创建用户信息失败');
//                }
                throw new SystemInternalException('用户不在系统中');
            }

            //更新绑定信息
            try {
                $userPlatformInfo->update([
                    'user_id' => $user->id
                ]);
            } catch (Exception $exception) {
                Log::error($exception->getMessage());
                throw new SystemInternalException('更新用户worktile信息失败');
            }
        }


//        //判断名字是否是正确的 名字以Worktile为准
//        if (get_class($userPlatformInfo) == UserWorktileInfo::class) {
//            if ($user->nickname != $userPlatformInfo->nickname) {
//                try {
//                    $user->update([
//                        'nickname' => $userPlatformInfo->nickname
//                    ]);
//                } catch (Exception $exception) {
//                    Log::error('更新名字失败 ' . $exception->getMessage());
//                }
//            }
//        }

        //判断头像，头像以微信为准
        if (get_class($userPlatformInfo) == UserWechatInfo::class) {
            $avatarArray = explode('https://work.mttop.cn/', $user->avatar);
            if (count($avatarArray) > 0 || $user->avatar == 'no-avatar.png') {
                try {
                    $user->update([
                        'avatar' => $userPlatformInfo->avatar
                    ]);
                } catch (Exception $exception) {
                    Log::error('更新头像失败 ' . $exception->getMessage());
                }
            }
        }

        return $user;
    }
}