<?php

namespace App\Http\Controllers;

use App\Exceptions\SystemInternalException;
use App\Exceptions\UserBadRequestException;
use App\Http\Requests\BindTelephoneRequest;
use App\Http\Transformers\UserTransformer;
use App\Models\Department;
use App\Models\Project;
use App\Models\RequestVerityToken;
use App\Models\RoleUser;
use App\Models\Task;
use App\Repositories\ScopeRepository;
use App\Repositories\UserRepository;
use App\TaskStatus;
use App\User;
use Carbon\Carbon;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Facades\Hash;
use League\Fractal;
use League\Fractal\Manager;


class UserController extends Controller
{
    protected $userRepository;

    public function __construct(UserRepository $userRepository)
    {
        $this->userRepository = $userRepository;
    }

    // todo 过一遍redis，如果redis有数据则取redis的
    public function index(Request $request)
    {
        // 直接从缓存拿数组
        if (Cache::has(config('app.users'))) {
            return response(Cache::get(config('app.users')));
        }

        $users = User::where('entry_status',3)->orderBy('name')->get();//where('entry_status',3) 用户 状态等于3

        $data = new Fractal\Resource\Collection($users, new UserTransformer());
        $manager = new Manager();

        if ($request->has('include')) {
            $manager->parseIncludes($request->get('include'));
        }

        $userArr = $manager->createData($data)->toArray();
        Cache::put(config('app.users'), $userArr, 30);

        return response($userArr);
    }


    public function all(Request $request)
    {
        $users = DB::select('select users.name,users.id,users.icon_url from users where users.entry_status = 3');
        $arr['data'] = $users;

        foreach ($arr['data'] as $key => &$value) {
            $value->id = hashid_encode($value->id);
        }
        return  $arr;
    }

    public function my(Request $request,ScopeRepository $repository)
    {
        $user = Auth::guard('api')->user();
        $cache_key = "power:user:".$user->id.":all:list";
        $power = Cache::get($cache_key);
        if (!$power) {
            $power = [];
            //获取当前用户所有角色
            $role_ids = RoleUser::where('user_id', $user->id)->pluck('role_id')->all();

            $api_list = [
                'star' => [
                    'add' => ['method' => 'post', 'uri' => 'stars'],
                    'export' => ['method' => 'post', 'uri' => 'stars/export'],
                    'import' => ['method' => 'post', 'uri' => 'stars/import']
                ],
                'project' => [
                    'add' => ['method' => 'post', 'uri' => 'projects'],
                    'export' => ['method' => 'post', 'uri' => 'projects/export'],
                    'import' => ['method' => 'post', 'uri' => 'projects/import']
                ],
                'blogger' => [
                    'add' => ['method' => 'post', 'uri' => 'bloggers'],
                    'export' => ['method' => 'post', 'uri' => 'bloggers/export'],
                    'import' => ['method' => 'post', 'uri' => 'bloggers/import']
                ],
                'task' => [
                    'add' => ['method' => 'post', 'uri' => 'tasks'],
                    'export' => ['method' => 'post', 'uri' => 'tasks/export'],
                    'import' => ['method' => 'post', 'uri' => 'tasks/import']
                ],
                'client' => [
                    'add' => ['method' => 'post', 'uri' => 'clients'],
                    'export' => ['method' => 'post', 'uri' => 'clients/export'],
                    'import' => ['method' => 'post', 'uri' => 'clients/import']
                ],
                'trail' => [
                    'add' => ['method' => 'post', 'uri' => 'trails'],
                    'export' => ['method' => 'post', 'uri' => 'trails/export'],
                    'import' => ['method' => 'post', 'uri' => 'trails/import']
                ],


            ];
            foreach ($api_list as $key => $value) {
                foreach ($value as $k => $v) {
                    //获取对线索新增权限
                    try {
                        $repository->checkPower($v['uri'], $v['method'], $role_ids, null);
                        $power[$key][$k] = "true";
                    } catch (Exception $exception) {
                        $power[$key][$k] = "true";//权限控制暂时取消
                    }
                }
            }
            Cache::put($cache_key,$power,1);
        }

        $user->power = $power;
        //我负责的项目数
        $my_project_number = Project::where('principal_id',$user->id)->count();
        //我的任务数
        $my_task_number = Task::where('principal_id',$user->id)->count();
        //我的审批数pendingSum
        $Approval = new ApprovalFormController();
        $sumInfo = $Approval->pendingSum($request);
        $my_approval_number = array_sum($sumInfo);
        //待完成任务数
        $my_wait_task_number = Task::where('principal_id',$user->id)->whereIn('status',[TaskStatus::NORMAL,TaskStatus::DELAY])->count();
        $user->my_number = [
            'my_project_number' =>  $my_project_number,
            'my_task_number'    =>  $my_task_number,
            'my_approval_number'    =>  $my_approval_number,
            'my_wait_task_number'   =>  $my_wait_task_number
        ];
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
//        $requestVerityToken = RequestVerityToken::where('token', $request->get('token'))->where('device', $request->get('device'))->where('telephone', $telephone)->first();
//
//        if (!$requestVerityToken){
//            return $this->response->errorBadRequest('缺少必要参数');
//        }
//        $updatedTime = $requestVerityToken->updated_at;
//        $expiredTime = $updatedTime->addSeconds($requestVerityToken->expired_in);
//        $now = Carbon::now();
//        if ($now > $expiredTime) {
//            return $this->response->errorBadRequest('短信验证码已经过期了');
//        }
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

//        #删除登录token
//        try {
//            $requestVerityToken->delete();
//        } catch (Exception $exception) {
//            Log::error($exception->getMessage());
//        }

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
            return $this->response->accepted(null,'修改成功!');
        } else {
            return $this->response->errorInternal('修改失败！');
        }
    }


}
