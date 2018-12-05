<?php

namespace App\Http\Controllers;

use App\Http\Requests\BindTelephoneRequest;
use App\Http\Transformers\UserTransformer;
use App\Models\Department;
use App\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class UserController extends Controller
{
    public function index(Request $request)
    {
        $users = User::orderBy('name')->get();

        return $this->response->collection($users, new UserTransformer());
    }

    public function my(Request $request)
    {
        $user = Auth::guard('api')->user();

        return $this->response->item($user, new UserTransformer());
    }

    private function department(Department $department)
    {
        $department = $department->pDepartment;
        if ($department->department_pid == 0) {
            return $department;
        } else {
            $this->department($department);
        }
    }

    // 微信登录后手机号绑定接口
    public function telephone(BindTelephoneRequest $request) {
        $telephone = $request->get('telephone');
        #验证是否过期
        $requestVerityToken = RequestVerityToken::where('token', $request->get('token'))->where('device', $request->get('device'))->where('telephone', $telephone)->first();
        if (!$requestVerityToken){
            return $this->response->errorBadRequest('缺少必要参数');
        }
        $updatedTime = $requestVerityToken->updated_at;
        $expiredTime = $updatedTime->addSeconds($requestVerityToken->expired_in);
        $now = Carbon::now();
        if ($now > $expiredTime) {
            return $this->response->errorBadRequest('短信验证码已经过期了');
        }
        #查找用户
        try {
            $userRepository = new UserRepository();
            $user = $userRepository->findOrCreateByTelephone($telephone);
        } catch (SystemInternalException $exception) {
            return $this->response->errorInternal('登录失败');
        } catch (UserBadRequestException $exception) {
            return $this->response->errorBadRequest($exception->getMessage());
        } catch (Exception $exception) {
            Log::error($exception->getMessage());
            return $this->response->errorInternal('未知错误');
        }

        #删除登录token
        try {
            $requestVerityToken->delete();
        } catch (Exception $exception) {
            Log::error($exception->getMessage());
        }

        return $this->response->accepted();
    }
}
