<?php

namespace App\Http\Controllers;

use App\Exceptions\SystemInternalException;
use App\Exceptions\UserBadRequestException;
use App\Http\Requests\BindTelephoneRequest;
use App\Http\Transformers\UserTransformer;
use App\Models\Department;
use App\Models\RequestVerityToken;
use App\Repositories\UserRepository;
use App\User;
use Carbon\Carbon;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Facades\Hash;


class UserController extends Controller
{
    protected $userRepository;

    public function __construct(UserRepository $userRepository)
    {
        $this->userRepository = $userRepository;
    }

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
    public function show(User $user)
    {
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

    // 微信登录后手机号绑定接口 => 修改密码接口
    public function telephone(BindTelephoneRequest $request) {
        $telephone = $request->get('telephone');
        $password = $request->get('password');
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
        #用户修改密码
        try {
            $userRepository = $this->userRepository;
            $user = $userRepository->changePassword($password, $telephone);
        } catch (SystemInternalException $exception) {
            return $this->response->errorInternal('修改失败');
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

        $accessToken = $user->createToken('telephone login')->accessToken;
        return $this->response->array([
            'access_token' => $accessToken
        ]);
    }
    //修改密码
    public function editpassword(Request $request)
    {

        $id = Auth::user()->id;
        $user = Auth::guard('api')->user();

        $oldpassword = $request->input('oldpassword');
        $newpassword = $request->input('newpassword');
        $res = DB::table('users')->where('id', $id)->select('password')->first();
        if (!Hash::check($oldpassword, $res->password)) {
            return $this->response->errorInternal('原密码不正确');
        }
        $update = array(
            'password' => bcrypt($newpassword),
        );
        $result = DB::table('users')->where('id', $id)->update($update);
        if ($result) {
            return $this->response->errorInternal('修改成功!');
        } else {
            return $this->response->errorInternal('修改失败！');
        }
    }
}
