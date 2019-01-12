<?php

namespace App\Http\Controllers;

use App\Http\Transformers\DepartmentTransformer;
use App\Http\Transformers\DepartmentUserTransformer;
use App\Http\Transformers\PositionTransformer;


use App\Models\Department;
use App\Models\DepartmentPrincipal;

use App\Models\Position;
use App\Models\DepartmentUser;
use App\Models\OperateEntity;
use App\Models\RoleUser;
use App\OperateLogMethod;
use App\Events\OperateLogEvent;
use App\Http\Transformers\UserTransformer;
use App\Http\Requests\DepartmentRequest;
use App\User;
use Illuminate\Http\Request;
use App\Http\Requests\PositionRequest;

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class DepartmentController extends Controller
{
    public function index(Request $request)
    {

        $depatments = Department::where('department_pid', 0)->get();
        return $this->response->collection($depatments, new DepartmentTransformer());
    }

    //添加部门
    public function store(DepartmentRequest $departmentrequest,DepartmentUser $departmentUser)
    {
        $payload = $departmentrequest->all();
        $departmentPid = hashid_decode($payload['department_pid']);

        $sortSum = DB::table("departments")->where('department_pid',$departmentPid)->max('sort_number');
        $departmentArr = [
            "department_pid"=>hashid_decode($payload['department_pid']),
            "name"=>$payload['name'],
            "city"=> isset($payload['city']) ? $payload['city'] : '',
            "company_id"=> $payload['company_id'],
            "sort_number"=> ++$sortSum,
            "order_by"=> 'sort_number',
        ];
        
        $userId = isset($payload['user_id']) ? hashid_decode($payload['user_id']) : 0;
        DB::beginTransaction();
        try {
            if($userId == 0){

                $contact = Department::create($departmentArr);

            }else{

                $contact = Department::create($departmentArr);
                $id = DB::getPdo()->lastInsertId();

                $array = [
                    "department_id"=>$id,
                    "user_id"=>$userId,

                ];
                $depar = DepartmentPrincipal::create($array);
                //查询修改前部门主管如果存在多个部门则不更新角色表 反之则删除
                $userIdSum = DB::table("role_users")->where('user_id',$userId)->get()->count();

                if($userIdSum ==0){
                    $array = [
                        "role_id" => 75,
                        "user_id" => $userId,
                    ];
                    $depar = RoleUser::create($array);
                }
            }
            // 操作日志
//            $operate = new OperateEntity([
//                'obj' => $department,
//                'title' => null,
//                'start' => null,
//                'end' => null,
//                'method' => OperateLogMethod::CREATE,
//            ]);
//            event(new OperateLogEvent([
//                $operate,
//            ]));
        } catch (Exception $e) {
            DB::rollBack();
            Log::error($e);
            return $this->response->errorInternal('创建失败');
        }
        DB::commit();
    }


    //编辑部门
    public function edit(DepartmentRequest $departmentrequest,Department $department,User $user,DepartmentUser $departmentUser)
    {
        $payload = $departmentrequest->all();
        $departmentId = $department->id;
       // $userId = hashid_decode($payload['user_id']);
        $departmentArr = [
            "department_pid"=>hashid_decode($payload['department_pid']),
            "name"=>$payload['name'],
            "company_id"=> isset($payload['company_id']) ? $payload['company_id'] : '',
            "city"=>isset($payload['city']) ? $payload['city'] : '',
        ];

        $userId = isset($payload['user_id']) ? hashid_decode($payload['user_id']) : 0;
        DB::beginTransaction();
        try {

            if($userId == 0){

                $principalInfo = DB::table("department_principal")->where('department_id',$departmentId)->get()->toArray();

                if(!empty($principalInfo)) {
                    $departmentUserId = $principalInfo[0]->user_id;

                    //查询修改前部门主管如果存在多个部门则不更新角色表 反之则删除
                    $userIdSum = DB::table("department_principal")->where('user_id',$departmentUserId)->get()->count();

                    if($userIdSum >= 2){

                    }else{
                        $num = DB::table("role_users")->where('user_id',$departmentUserId)->where('role_id',75)->delete();

                    }
                    $num = DB::table("department_principal")->where('department_id',$departmentId)->delete();

                }

            }else{

            //先查询修改之前的部门主管是否存在别的部门
            $principalInfo = DB::table("department_principal")->where('department_id',$departmentId)->get()->toArray();

            if(empty($principalInfo)){

                $principalArr = [
                    "department_id"=>$departmentId,
                    "user_id"=>hashid_decode($payload['user_id']),
                ];
                $depar = DepartmentPrincipal::create($principalArr);
                //根据传过来的user_id 查询是部门主管角色
                $roleUser = DB::table("role_users")->where('user_id',$userId)->where('role_id',75)->get()->toArray();
                if(empty($roleUser)) {
                    $array = [
                        "role_id" => 75,
                        "user_id" => $userId,
                    ];
                    $depar = RoleUser::create($array);
                }

            }else{

                $departmentUserId = $principalInfo[0]->user_id;

                //查询修改前部门主管如果存在多个部门则不更新角色表 反之则删除
                $userIdSum = DB::table("department_principal")->where('user_id',$departmentUserId)->get()->count();

                if($userIdSum >= 2){

                }else{
                    $num = DB::table("role_users")->where('user_id',$departmentUserId)->where('role_id',75)->delete();

                }

                //根据传过来的user_id 查询是部门主管角色
                $roleUser = DB::table("role_users")->where('user_id',$userId)->where('role_id',75)->get()->toArray();
                if(empty($roleUser)){
                    $array = [
                        "role_id"=>75,
                        "user_id"=>$userId,
                    ];
                    $depar = RoleUser::create($array);
                }

                $principalArr = [
                    "department_id"=>$departmentId,
                    "user_id"=>hashid_decode($payload['user_id']),
                ];
                $num = DB::table("department_principal")->where('department_id',$departmentId)->delete();
                $depar = DepartmentPrincipal::create($principalArr);
            }


            }
            $depar = $department->update($departmentArr);

//            // 操作日志
//            $operate = new OperateEntity([
//                'obj' => $department,
//                'title' => null,
//                'start' => null,
//                'end' => null,
//                'method' => OperateLogMethod::CREATE,
//            ]);
//            event(new OperateLogEvent([
//                $operate,
//            ]));

        } catch (Exception $e) {
            DB::rollBack();
            Log::error($e);
            return $this->response->errorInternal('创建失败');
        }
        DB::commit();
    }


    //移动部门
    public function mobile(Request $request,Department $department,User $user,DepartmentUser $departmentUser)
    {
        $payload = $request->all();
        $userId = $user->id;
        $departmentId = $department->id;
        $departmentArr = [
            "department_pid"=>isset($payload['department_pid']) ? hashid_decode($payload['department_pid']) : 0,
        ];

        DB::beginTransaction();
        try {
            //  dd($payload['user_id']);

            // 操作日志
            $operate = new OperateEntity([
                'obj' => $department,
                'title' => null,
                'start' => $department->department_pid,
                'end' => $payload['department_pid'],
                'method' => OperateLogMethod::UPDATE,
            ]);
            event(new OperateLogEvent([
                $operate,
            ]));
            $contact = $department->update($departmentArr);

        } catch (Exception $e) {
            DB::rollBack();
            Log::error($e);
            return $this->response->errorInternal('创建失败');
        }
        DB::commit();
    }


    //删除部门 TODO 删除父级部门 判断下级部门负责人是否存在多个部门 存在则不更新角色表 反之则删除 删除部门成员及其下属部门成员都更新为未分配部门
    public function remove(Request $request,Department $department)
    {

        $departmentId = $department->id;
        $departmentPid = $department->department_pid;

        $depatmentRes = DB::table("departments")->where('department_pid', $departmentId)->first();
        if($depatmentRes !==null){
            return $this->response->errorInternal('该部门有下级部门');
        }

        try {

            $depatments = DepartmentUser::where('department_id', $departmentId)->where('type','!=',1)->get()->toArray();

            //$num = DB::table("department_principal")->where('department_id',$departmentId)->delete();
            //删除部门 把其下面部门成员移交到未分配部门
            foreach ($depatments as $value){
                $snum = DB::table('department_user')
                    ->where('user_id',$value['user_id'])
                    ->update(['department_id'=>1]);
            }
            //获取删除部门负责人id
            $principalInfo = DB::table("department_principal")->where('department_id',$departmentId)->first();

            if(isset($principalInfo)){
                //查找部门负责人表 如果值大于2 则 userid 存在多个部门负责人 反之则删除 role_user 表 75
                $userIdSum = DB::table("department_principal")->where('user_id',$principalInfo->user_id)->count();
                if($userIdSum >= 2){

                }else{
                    $num = DB::table("role_users")->where('user_id',$principalInfo->user_id)->where('role_id',75)->delete();

                }
            }
           //////////
            $num = DB::table("department_principal")->where('department_id',$departmentId)->delete();
            $nums = DB::table("departments")->where('id',$departmentId)->delete();


//            if(empty($depatments)){
//
//                $num = DB::table("departments")->where('id',$departmentId)->delete();
//                return $this->response->noContent();
//            }else{
//                return $this->response->errorInternal('该部门有下级部门或部门下有成员');
//
//            }
        } catch (Exception $e) {

            return $this->response->errorInternal('删除失败');
        }

    }

    //选择成员
    public function select(Request $request,Department $department)
    {

        $departmentId = $department->id;
        $departmentPid = $department->department_pid;
        $depatments = DepartmentUser::where('department_id', $departmentId)->get();
        $data = DB::table('department_user')
                    ->join('users', function($join)
                    {
                        $join->on('department_user.user_id', '=', 'users.id');
                    })->select('users.id', 'users.name')
                     ->where('department_user.department_id',$departmentId)
                    ->where('department_user.type',0)
                    ->get();
        return $this->response->item($data, new DepartmentUserTransformer());

    }

//    //选择成员
    public function selectStore(Request $request,Department $department,DepartmentUser $departmentUser)
    {
        $payload = $request->all();
        $departmentId = $department->id;
        $departmentPid = $department->department_pid;
        $depatments = DepartmentUser::where('department_id', $departmentId)->get()->toArray();
        $depatmentNotid = Department::where('name', Department::NOT_DISTRIBUTION_DEPARTMENT)->first()->id;
        $users = isset($payload['user']) ? $payload['user'] : 1;

        DB::beginTransaction();
        try {

            if($users != 1){

//                $num = DB::table('department_user')
//                    ->where('department_id',$departmentId)
//                    ->where('type','!=',1)
//                    ->update(['department_id'=>1]);
                foreach ($payload['user'] as $key=>$value){
                    $userId = hashid_decode($value);
                    $snum = DB::table('department_user')
                        ->where('department_id',$departmentId)
                        ->update(['department_id'=>1]);
                }

                foreach ($payload['user'] as $key=>$value){
                    $userId = hashid_decode($value);
                    $snum = DB::table('department_user')
                        ->where('user_id',$userId)
                        ->update(['department_id'=>$departmentId,'type'=>0]);
                }

                // 操作日志
                $operate = new OperateEntity([
                    'obj' => $department,
                    'title' => null,
                    'start' => $departmentId,
                    'end' => json_encode($payload['user']),
                    'method' => OperateLogMethod::UPDATE,
                ]);
                event(new OperateLogEvent([
                    $operate,
                ]));

            }else{

                //return $this->response->errorInternal('用户id错误');
                $depatments = DepartmentUser::where('department_id', $departmentId)->get()->toArray();
                foreach ($depatments as $key=>$value){
                    $snum = DB::table('department_user')
                        ->where('user_id',$value['user_id'])
                        ->update(['department_id'=>1]);
                }
            }

        } catch (Exception $e) {
            DB::rollBack();
            Log::error($e);
            return $this->response->errorInternal('删除失败');
        }
        DB::commit();

    }

    public function show(Request $request,User $user)
    {
        //$data = $user->where('entry_status',3)->get()->toArray();

        $dataInfo = DB::table('users')//

            ->leftJoin('position', function ($join) {
                $join->on('position.id', '=', 'users.position_id');
            })
            ->leftJoin('department_user as du', function ($join) {
                $join->on('du.user_id', '=', 'users.id');
            })
            ->leftJoin('departments as dt', function ($join) {
                $join->on('dt.id', '=', 'du.department_id');
            })
            ->where('users.entry_status',3)
            ->select('users.*','dt.name as department_name', 'position.name as position_name')->get()->toArray();
        $data = json_decode(json_encode($dataInfo), true);

        $targetKey = 'name';
        $data = array_map(function ($item) use ($targetKey) {
            return array_merge($item, [
                'initials' => $this->getFirstChar($item[$targetKey]),

            ]);
        }, $data);
        $data = $this->sortInitials($data);

         return $data;
    }

    public function departmentsList(Request $request,User $user,Department $department)
    {
        return $department->get()->toArray();
    }


    //Todo sql
    public function detail(Request $request,User $user,Department $department)
    {
        $id = $department->id;

        $results = DB::select('select departments.name,departments.department_pid,departments.city,users.id as user_id,users.name as username,department_user.type 
                            from departments 
                            LEFT JOIN department_user on department_user.department_id = departments.id 
                            LEFT JOIN users on department_user.user_id = users.id 
                            where department_user.department_id ='.$id.'
                            and department_user.type = :id', ['id' => 1]);
        $res['data'] = array();
        if($results[0]->department_pid !==0){
            $res['data'] = Department::where('id', $results[0]->department_pid)->get()->keyBy('id')->toArray();
            $arr = array_merge($results,$res);
        }else{
            $arr = array_merge($results,$res);
        }

        return $arr;

    }





    public function positionList(Request $request)
    {
        $positions = Position::get();
        return $this->response->collection($positions, new positionTransformer());
    }


    public function positionStore(PositionRequest $positionRequest)
    {
        $payload = $positionRequest->all();
        $name = Position::where('name', $payload['name'])->get()->keyBy('name')->toArray();

        if(!empty($name) ) {
            return $this->response->errorInternal('该职位已存在!');
        }
        DB::beginTransaction();
        try {
            $depar = Position::create($payload);
            // 操作日志
//            $operate = new OperateEntity([
//                'obj' => $department,
//                'title' => null,
//                'start' => null,
//                'end' => null,
//                'method' => OperateLogMethod::CREATE,
//            ]);
//            event(new OperateLogEvent([
//                $operate,
//            ]));
        } catch (Exception $e) {
            DB::rollBack();
            Log::error($e);
            return $this->response->errorInternal('创建失败');
        }
        DB::commit();
    }

    public function positionEdit(PositionRequest $positionRequest,Position $position)
    {
        $payload = $positionRequest->all();

        $array = [
            'name'=>$payload['name'],
        ];

        DB::beginTransaction();
        try {
            $position->update($array);
            // 操作日志
//            $operate = new OperateEntity([
//                'obj' => $department,
//                'title' => null,
//                'start' => null,
//                'end' => null,
//                'method' => OperateLogMethod::CREATE,
//            ]);
//            event(new OperateLogEvent([
//                $operate,
//            ]));
        } catch (Exception $e) {
            DB::rollBack();
            Log::error($e);
            return $this->response->errorInternal('创建失败');
        }
        DB::commit();
    }

    public function positionDel(Position $position)
    {
        DB::beginTransaction();
        try {
            $position->delete();
            // 操作日志
//            $operate = new OperateEntity([
//                'obj' => $department,
//                'title' => null,
//                'start' => null,
//                'end' => null,
//                'method' => OperateLogMethod::CREATE,
//            ]);
//            event(new OperateLogEvent([
//                $operate,
//            ]));
        } catch (Exception $e) {
            DB::rollBack();
            Log::error($e);
            return $this->response->errorInternal('创建失败');
        }
        DB::commit();
    }



    public function disableList(Request $request)
    {
        $search = addslashes($request->input('search'));//姓名 手机号

        $userInfo = User::where('disable', 1)->get();
        $pageSize = $request->get('page_size', config('app.page_size'));
        $userInfo = User::orderBy('updated_at','DESC')
            ->where(function($query) use($request,$search){
                $query->where('disable',1);
                if(!empty($search)) {
                    $query->where('name', 'like', '%'.$search.'%')->orWhere('phone', 'like', '%'.$search.'%');
                }
            })->paginate($pageSize);
        
        return $this->response->paginator($userInfo, new UserTransformer());
    }


    public function disableEdit(Request $request, User $user)
    {
        $payload = $request->all();
        $userId = $user->id;

        $array = [
            'disable'=>$payload['disable'],
        ];
        DB::beginTransaction();
        try {
            $user->update($array);
            // 操作日志
            $operate = new OperateEntity([
                'obj' => $user,
                'title' => null,
                'start' => $user->disable,
                'end' => $payload['disable'],
                'method' => OperateLogMethod::UPDATE,
            ]);
            event(new OperateLogEvent([
                $operate,
            ]));
        } catch (Exception $e) {
            DB::rollBack();
            Log::error($e);
            return $this->response->errorInternal('创建失败');
        }
        DB::commit();
    }



    /**
     * 按字母排序
     * @param  array  $data
     * @return array
     */
    public function sortInitials(array $data)
    {
        $sortData = [];
        foreach ($data as $key => $value) {
            $value['user_id'] = hashid_encode($value['id']);
            $sortData[$value['initials']][] = $value;
            //$sortData[$value['initials']][] = ;

        }
        ksort($sortData);
        return $sortData;
    }


    public function getFirstChar($s){

        $s0 = mb_substr($s,0,3); //获取名字的姓
        $s = iconv("UTF-8", "GBK//IGNORE", $s0);
        if (ord($s0)>128) { //汉字开头，汉字没有以U、V开头的
            $asc=ord($s{0})*256+ord($s{1})-65536;
            if($asc>=-20319 and $asc<=-20284)return "A";
            if($asc>=-20283 and $asc<=-19776)return "B";
            if($asc>=-19775 and $asc<=-19219)return "C";
            if($asc>=-19218 and $asc<=-18711)return "D";
            if($asc>=-18710 and $asc<=-18527)return "E";
            if($asc>=-18526 and $asc<=-18240)return "F";
            if($asc>=-18239 and $asc<=-17760)return "G";
            if($asc>=-17759 and $asc<=-17248)return "H";
            if($asc>=-17247 and $asc<=-17418)return "I";
            if($asc>=-17417 and $asc<=-16475)return "J";
            if($asc>=-16474 and $asc<=-16213)return "K";
            if($asc>=-16212 and $asc<=-15641)return "L";
            if($asc>=-15640 and $asc<=-15166)return "M";
            if($asc>=-15165 and $asc<=-14923)return "N";
            if($asc>=-14922 and $asc<=-14915)return "O";
            if($asc>=-14914 and $asc<=-14631)return "P";
            if($asc>=-14630 and $asc<=-14150)return "Q";
            if($asc>=-14149 and $asc<=-14091)return "R";
            if($asc>=-14090 and $asc<=-13319)return "S";
            if($asc>=-13318 and $asc<=-12839)return "T";
            if($asc>=-12838 and $asc<=-12557)return "W";
            if($asc>=-12556 and $asc<=-11848)return "X";
            if($asc>=-11847 and $asc<=-11056)return "Y";
            if($asc>=-11055 and $asc<=-10247)return "Z";

        }else if(ord($s)>=48 and ord($s)<=57){ //数字开头
            switch(iconv_substr($s,0,1,'utf-8')){
                case 1:return "Y";
                case 2:return "E";
                case 3:return "S";
                case 4:return "S";
                case 5:return "W";
                case 6:return "L";
                case 7:return "Q";
                case 8:return "B";
                case 9:return "J";
                case 0:return "L";
            }
        }else if(ord($s)>=65 and ord($s)<=90){ //大写英文开头
            return substr($s,0,1);
        }else if(ord($s)>=97 and ord($s)<=122){ //小写英文开头
            return strtoupper(substr($s,0,1));
        }
        else
        {
            return iconv_substr($s0,0,1,'utf-8');
        }
    }


    public function jobsList(Request $request)
    {
        $positions = Position::get();
        return $this->response->collection($positions, new positionTransformer());
    }

    public function storeJobs(Request $request)
    {
        $payload = $request->all();


        DB::beginTransaction();
        try {
            $array = [
                'name'=>$payload['name'],
            ];
            Position::create($array);
            // 操作日志
//            $operate = new OperateEntity([
//                'obj' => $user,
//                'title' => null,
//                'start' => $user->disable,
//                'end' => $payload['disable'],
//                'method' => OperateLogMethod::UPDATE,
//            ]);
//            event(new OperateLogEvent([
//                $operate,
//            ]));
        } catch (Exception $e) {
            DB::rollBack();
            Log::error($e);
            return $this->response->errorInternal('创建失败');
        }
        DB::commit();


    }



}
