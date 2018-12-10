<?php

namespace App\Http\Controllers;

use App\Http\Transformers\DepartmentTransformer;
use App\Http\Transformers\DepartmentUserTransformer;

use App\Models\Department;
use App\Models\DepartmentUser;
use App\Models\OperateEntity;
use App\OperateLogMethod;
use App\Events\OperateLogEvent;
use App\Http\Transformers\UserTransformer;
use App\Http\Requests\DepartmentRequest;
use App\User;
use Illuminate\Http\Request;
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
        $departmentArr = [
            "department_pid"=>$payload['department_pid'],
            "name"=>$payload['name'],
            "city"=> isset($payload['city']) ? $payload['city'] : '',
        ];
        DB::beginTransaction();
        try {
            $contact = Department::create($departmentArr);
            $id = DB::getPdo()->lastInsertId();

            $array = [
                "department_id"=>$id,
                "user_id"=>hashid_decode($payload['user_id']),
                "type"=>Department::DEPARTMENT_HEAD_TYPE,
            ];
            $depar = DepartmentUser::create($array);
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

        $departmentArr = [
            "department_pid"=>hashid_decode($payload['department_pid']),
            "name"=>$payload['name'],
            "city"=>isset($payload['city']) ? $payload['city'] : '',
        ];

        DB::beginTransaction();
        try {

            $contact = $department->update($departmentArr);
            $num = DB::table("department_user")->where('department_id',$departmentId)->where('type',1)->delete();

            $array = [
                "department_id"=>$departmentId,
                "user_id"=>hashid_decode($payload['user_id']),
                "type"=>Department::DEPARTMENT_HEAD_TYPE,
            ];

            $depar = DepartmentUser::create($array);
            // 操作日志
            $operate = new OperateEntity([
                'obj' => $department,
                'title' => null,
                'start' => null,
                'end' => null,
                'method' => OperateLogMethod::CREATE,
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


    //移动部门
    public function mobile(Request $request,Department $department,User $user,DepartmentUser $departmentUser)
    {
        $payload = $request->all();
        $userId = $user->id;
        $departmentId = $department->id;
        $departmentArr = [
            "department_pid"=>$payload['department_pid'],
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


    //删除部门
    public function remove(Request $request,Department $department)
    {

        $departmentId = $department->id;
        $departmentPid = $department->department_pid;
        $depatments = DepartmentUser::where('department_id', $departmentId)->get()->toArray();

        DB::beginTransaction();
        try {
            if($departmentPid !== 0  && !empty($depatments)){
                $department->delete();
                return $this->response->noContent();
            }else{

                return $this->response->errorInternal('该部门有下级部门或部门下有成员');

            }
        } catch (Exception $e) {
            DB::rollBack();
            Log::error($e);
            return $this->response->errorInternal('删除失败');
        }
        DB::commit();

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
                    ->get();


        return $this->response->item($data, new DepartmentUserTransformer());

    }

//    //选择成员
    public function selectStore(Request $request,Department $department,DepartmentUser $departmentUser)
    {
        $payload = $request->all();
        //dd($payload['user']);
        $departmentId = $department->id;
        $departmentPid = $department->department_pid;
        $depatments = DepartmentUser::where('department_id', $departmentId)->get()->toArray();
        $depatmentNotid = Department::where('name', Department::NOT_DISTRIBUTION_DEPARTMENT)->first()->id;

        DB::beginTransaction();
        try {
            if(!empty($payload['user'])){

                $num = DB::table('department_user')
                    ->where('department_id',$departmentId)
                    ->where('type',0)
                    ->update(['department_id'=>$depatmentNotid]);

                foreach ($payload['user'] as $key=>$value){
                    $array = [
                        "department_id"=>$departmentId,
                        "user_id"=>$value,
                    ];
                    $depar = DepartmentUser::create($array);
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
                return $this->response->errorInternal('用户id错误');
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
        $data = $user->get()->toArray();
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
        $s = iconv('UTF-8','gb2312', $s0); //将UTF-8转换成GB2312编码
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



}
