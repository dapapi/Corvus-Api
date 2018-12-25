<?php

namespace App\Http\Controllers;

use App\Http\Transformers\UserTransformer;
use App\Http\Transformers\JobTransformer;

use App\Events\OperateLogEvent;

use App\Models\Department;
use Carbon\Carbon;
use App\User;
use App\Models\Record;
use App\Models\Education;
use App\Models\FamilyData;
use App\Models\PersonalDetail;
use App\Models\PersonalJob;
use App\Models\PersonalSalary;
use App\Models\PersonalSocialSecurity;
use App\Models\PersonalSkills;
use App\Models\DepartmentUser;
use App\Models\RoleUser;

use Illuminate\Http\Request;
use App\Models\OperateEntity;
use App\OperateLogMethod;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;


class PersonnelManageController extends Controller
{
    public function index(Request $request,User $user)
    {

        $payload = $request->all();

        $pageSize = $request->get('page_size', config('app.page_size'));
        //在职，聘用形式等于劳动和实习，且状态等于非已离职；
        $hire_shape = array(User::HIRE_SHAPE_LOWE,User::HIRE_SHAPE_INTERNSHIP);
       
        $data['onjob'] = $user->whereIn('hire_shape',$hire_shape)->where('status','!=',User::USER_STATUS_DEPARTUE)->where('entry_status',User::USER_ENTRY_STATUS)->count(); //在职
        //离职，聘用形式等于劳动和实习，且状态等于已离职
        $data['departure'] = $user->whereIn('hire_shape',$hire_shape)->where('status',User::USER_STATUS_DEPARTUE)->where('entry_status',User::USER_ENTRY_STATUS)->count(); //离职

        $user = User::orderBy('updated_at','DESC')
            ->where(function($query) use($request){
                //检测关键字

                $status = addslashes($request->input('status'));//状态
                $positionType = addslashes($request->input('position_type'));//在职状态
                $ehireShape = addslashes($request->input('hire_shape'));//聘用形式
                $search = addslashes($request->input('search'));//姓名 手机号
                if(!empty($status)) {

                    $query->where('status', $status);
                }

                if(!empty($positionType)) {

                    $query->where('position_type', $positionType);
                }
                if(!empty($ehireShape)) {
                    $query->where('hire_shape',$ehireShape);
                }
                if(!empty($search)) {
                    $query->where('name', 'like', '%'.$search.'%')->orWhere('phone', 'like', '%'.$search.'%');
                }
                //不显示存档信息 禁用
                $query->where('status','!=',User::USER_ARCHIVE)->where('disable','!=',User::USER_TYPE_DISABLE)->where('entry_status',User::USER_ENTRY_STATUS);

             })->paginate($pageSize);

        $result = $this->response->paginator($user, new UserTransformer());
        $result->addMeta('date', $data);
        return $result;

    }

    //随机颜色 名字
    public function getColorName($name){
        srand ((float) microtime() * 10000000);
        $input = array('#F23E7C','#FF68E2','#FB8C00','#B53FAF','#27D3A8','#2CCCDA','#38BA5D','#3F51B5');
        $rand_keys = array_rand ($input, 2);

        if(preg_match("/^[a-zA-Z\s]+$/",$name)){
            $icon_name  = strtoupper(substr($name,0,2));
        }else{
            if(strlen($name) > 6){

                if (preg_match('/[a-zA-Z]/',$name)){
                    $icon_name = mb_substr($name,0,2, 'utf-8');
                }else{
                    $icon_name = substr($name,(strlen($name)-6));
                }
            }else{
                $icon_name = $name;
            }
        }
        return $input[$rand_keys[0]].'|'.$icon_name;
    }

    public function store(Request $request,User $user)
    {
        $payload = $request->all();
        $user = Auth::guard('api')->user();
        $userPhone = User::where('phone', $payload['phone'])->get()->keyBy('phone')->toArray();
        $pageSize = config('api.page_size');

        if(!empty($userPhone)){
            return $this->response->errorInternal('手机号已经注册！');
        }else{

            if(!isset($payload['icon_url'])){

                $iconName = $this->getColorName($payload['name']);
                $payload['icon_url'] = $iconName;
            }
            $payload['status'] = User::USER_STATUS_DEFAULT;
            $payload['hire_shape'] = User::USER_STATUS_DEFAULT;
            $payload['position_type'] = User::USER_STATUS_DEFAULT;

            DB::beginTransaction();

        try {
            $user = User::create($payload);
            $userid = DB::getPdo()->lastInsertId();

            // 添加个人技能
            $skills = [
                'user_id' => $userid,
                'language_level' => $payload['language_level'],
                'certificate' => $payload['certificate'],
                'computer_level' => $payload['computer_level'],
                'specialty' => $payload['specialty'],
                'disease' => $payload['disease'],
                'pregnancy' => $payload['pregnancy'],
                'migration' => $payload['migration'],
                'remark' => $payload['remark'],
            ];

            if(!empty($payload['education'])) {
                // 教育背景
                foreach ($payload['education'] as $key => $value) {
                    $education = [
                        'user_id' => $userid,
                        'school' => $value['school'],
                        'specialty' => $value['specialty'],
                        'start_time' => $value['start_time'],
                        'end_time' => $value['end_time'],
                        'graduate' => $value['graduate'],
                        'degree' => $value['degree'],
                    ];
                    $eduInfo = Education::create($education);
                }
            }
            if(!empty($payload['record'])) {
                // 任职履历
                foreach ($payload['record'] as $key => $value) {
                    $erecord = [
                        'user_id' => $userid,
                        'unit_name' => $value['unit_name'],
                        'department' => $value['department'],
                        'position' => $value['position'],
                        'entry_time' => $value['entry_time'],
                        'departure_time' => $value['departure_time'],
                        'monthly_pay' => $value['monthly_pay'],
                        'departure_why' => $value['departure_why'],
                    ];
                    $recordInfo = Record::create($erecord);
                }
            }

            if(!empty($payload['family'])) {
                // 家庭资料
                foreach ($payload['family'] as $key => $value) {
                    $familyData = [
                        'user_id' => $userid,
                        'name' => $value['name'],
                        'relation' => $value['relation'],
                        'position' => $value['position'],
                        'birth_time' => $value['birth_time'],
                        'work_units' => $value['work_units'],
                        'position' => $value['position'],
                        'contact_phone' => $value['contact_phone'],
                    ];
                    $familyInfo = FamilyData::create($familyData);
                }
            }
            //添加个人特长
            $skillsInfo = PersonalSkills::create($skills);
            if ($user) {
                // 操作日志
                $operate = new OperateEntity([
                    'obj' => $user,
                    'title' => null,
                    'start' => null,
                    'end' => null,
                    'method' => OperateLogMethod::CREATE,
                ]);
                event(new OperateLogEvent([
                    $operate,
                ]));
            } else {
                    return $this->response->noContent();
            }

         } catch (Exception $e) {
            DB::rollBack();
            Log::error($e);
            return $this->response->errorInternal();
        }
            DB::commit();
            return $this->response->accepted();


        }
    }

    public function statusEdit(Request $request, User $user)
    {
        $payload = $request->all();
        $status = $payload['status'];
        $user_id = $user->id;
        if ($user->status == $status)
            return $this->response->noContent();
        $now = Carbon::now();


        if($status == 2){
            $array = [
                'status' => User::USER_STATUS_POSITIVE,
            ];
        }
        //离职
        if($status == 3){
            $array = [
                'position_type' => User::USER_STATUS_DEPARTUE,
            ];
            $num = DB::table("role_users")->where('user_id',$user_id)->delete();
            //归档
        }elseif($status == 5) {
            $array = [
                'status' => User::USER_ARCHIVE,
                'archive_time' => date('Y-m-d h:i:s',time()),
            ];
        }
        DB::beginTransaction();
        try {

             if (!empty($array)) {

                 $operate = new OperateEntity([
                     'obj' => $user,
                     'title' => null,
                     'start' => $user->status,
                     'end' => $payload['status'],
                     'method' => OperateLogMethod::TRANSFER,
                 ]);
                $user->update($array);
                 event(new OperateLogEvent([
                     $operate,
                 ]));
             } else {
                 return $this->response->noContent();
             }

        } catch (Exception $e) {
            DB::rollBack();
            Log::error($e);
            return $this->response->errorInternal('操作失败');
        }
        DB::commit();
        return $this->response->accepted();
    }




    //存档列表
    public function archiveList(Request $request)
    {
        $pageSize = $request->get('page_size', config('app.page_size'));
        $user = User::orderBy('entry_time','asc')
            ->where(function($query) use($request){
                $query->where('status',User::USER_ARCHIVE);
            })->paginate($pageSize);

        return $this->response->paginator($user, new UserTransformer());

    }


    public function detail(Request $request,User $user)
    {
        return $this->response->item($user, new UserTransformer());
    }

    //增加个人信息
    public function storePersonal(Request $request, User $user,PersonalDetail $personalDetail)
    {
        $payload = $request->all();
        $userid = $user->id;

        try {
//            $operate = new OperateEntity([
//                    'obj' => $personalDetail,
//                    'title' => null,
//                    'start' => null,
//                    'end' => null,
//                    'method' => OperateLogMethod::CREATE,
//                ]);
//                event(new OperateLogEvent([
//                    $operate,
//                ]));

            $payload['user_id'] = $userid;
            $userArr = [
//                'hire_shape' => $payload['userarr']['hire_shape'],
//                'position' => $payload['userarr']['position'],
//                'department' => $payload['userarr']['department'],

                'hire_shape' => $payload['hire_shape'],
                'department' => $payload['department'],
                'email' => $payload['email'],
                'department_id' => $payload['department_id'],
                'id_number' => $payload['id_number'],

            ];

            $user->update($userArr);
            $personalDetail->create($payload);

        } catch (\Exception $exception) {
            Log::error($exception);
            return $this->response->errorInternal('修改失败');
        }
        return $this->response->accepted();
    }


    //修改个人信息
    public function editPersonal(Request $request, User $user,PersonalDetail $personalDetail,DepartmentUser $departmentUser)
    {
        $payload = $request->all();
        $userid = $user->id;

        $userid = $user->id;
        $data = $departmentUser->where('department_id',$payload['department_id'])->where('user_id',$userid)->count();

        try {
//            $operate = new OperateEntity([
//                    'obj' => $user,
//                    'title' => '个人',
//                    'start' => '信息',
//                    'end' => '档案',
//                    'method' => OperateLogMethod::UPDATE,
//                ]);
//                event(new OperateLogEvent([
//                    $operate,
//                ]));
            $array = [
                'department_id' => $payload['department_id'],
                'user_id' => $userid,
            ];

            if($data == 0){
                $departmentUser->create($array);
            }else{
                $departmentInfo = DepartmentUser::where('department_id', $payload['department_id'])
                    ->where('user_id', $userid)
                    ->first();
                $departmentInfo->delete();
                $departmentUser->create($array);

            }
            $userArr = [
                'hire_shape' => $payload['hire_shape'],
                'department' => $payload['department'],
                'email' => $payload['email'],
                'department_id' => $payload['department_id'],
                'id_number' => $payload['id_number'],

            ];

            $user->update($userArr);
            $personalDetail->update($payload);


        } catch (\Exception $exception) {
            Log::error($exception);
            return $this->response->errorInternal('修改失败');
        }
        return $this->response->accepted();

    }

    //修改user
    public function editUser(Request $request, User $user,DepartmentUser $departmentUser)
    {
        $payload = $request->all();

        $userid = $user->id;
        $data = $departmentUser->where('department_id',$payload['department_id'])->where('user_id',$userid)->count();

        try {
//            $operate = new OperateEntity([
//                    'obj' => $user,
//                    'title' => '个人',
//                    'start' => '信息',
//                    'end' => '档案',
//                    'method' => OperateLogMethod::UPDATE,
//                ]);
//                event(new OperateLogEvent([
//                    $operate,
//                ]));

            $array = [
                'department_id' => $payload['department_id'],
                'user_id' => $userid,
            ];

            if($data == 0){
                $departmentUser->create($array);
            }else{
                $departmentInfo = DepartmentUser::where('department_id', $payload['department_id'])
                    ->where('user_id', $userid)
                    ->first();
                $departmentInfo->delete();
                $departmentUser->create($array);

            }
            $user->update($payload);
        } catch (\Exception $exception) {
            Log::error($exception);
            return $this->response->errorInternal('修改失败');
        }
        return $this->response->accepted();

    }

    //增加职位信息
    public function storeJobs(Request $request, User $user,PersonalJob $personalJob)
    {
        $payload = $request->all();
        $userid = $user->id;

        try {
            $payload['user_id'] = $userid;
//                // 操作日志
//                $operate = new OperateEntity([
//                    'obj' => $personalJob,
//                    'title' => null,
//                    'start' => null,
//                    'end' => null,
//                    'method' => OperateLogMethod::CREATE,
//                ]);
//                event(new OperateLogEvent([
//                    $operate,
//                ]));
                $personalJob->create($payload);


        } catch (\Exception $exception) {
            Log::error($exception);
            return $this->response->errorInternal('修改失败');
        }
        return $this->response->accepted();
    }

    //修改职位信息
    public function editJobs(Request $request, User $user,PersonalJob $personalJob)
    {
        $payload = $request->all();
        $userid = $user->id;
        try {
//                // 操作日志
//                $operate = new OperateEntity([
//                    'obj' => $personalJob,
//                    'title' => null,
//                    'start' => null,
//                    'end' => null,
//                    'method' => OperateLogMethod::CREATE,
//                ]);
//                event(new OperateLogEvent([
//                    $operate,
//                ]));
                $personalJob->update($payload);


        } catch (\Exception $exception) {
            Log::error($exception);
            return $this->response->errorInternal('修改失败');
        }
        return $this->response->accepted();
    }

    //修改薪资信息
    public function editSalary(Request $request, User $user,PersonalSalary $personalSalary)
    {
        $payload = $request->all();
        $userid = $user->id;
        try {
            //$payload['user_id'] = $userid;
//                // 操作日志
//                $operate = new OperateEntity([
//                    'obj' => $personalJob,
//                    'title' => null,
//                    'start' => null,
//                    'end' => null,
//                    'method' => OperateLogMethod::CREATE,
//                ]);
//                event(new OperateLogEvent([
//                    $operate,
//                ]));
            $personalSalary->update($payload);


        } catch (\Exception $exception) {
            Log::error($exception);
            return $this->response->errorInternal('修改失败');
        }
        return $this->response->accepted();
    }

    //增加薪资信息
    public function storeSalary(Request $request, User $user,PersonalSalary $personalSalary)
    {
        $payload = $request->all();
        $userid = $user->id;
        try {
            $payload['user_id'] = $userid;
//                // 操作日志
//                $operate = new OperateEntity([
//                    'obj' => $personalSalary,
//                    'title' => null,
//                    'start' => null,
//                    'end' => null,
//                    'method' => OperateLogMethod::CREATE,
//                ]);
//                event(new OperateLogEvent([
//                    $operate,
//                ]));
            $personalSalary->create($payload);

        } catch (\Exception $exception) {
            Log::error($exception);
            return $this->response->errorInternal('修改失败');
        }
        return $this->response->accepted();


    }

    //增加薪资信息
    public function storeSecurity(Request $request, User $user,PersonalSocialSecurity $personalSocialSecurity)
    {
        $payload = $request->all();
        $userid = $user->id;
        try {
            $payload['user_id'] = $userid;
//                // 操作日志
//                $operate = new OperateEntity([
//                    'obj' => $personalSalary,
//                    'title' => null,
//                    'start' => null,
//                    'end' => null,
//                    'method' => OperateLogMethod::CREATE,
//                ]);
//                event(new OperateLogEvent([
//                    $operate,
//                ]));
            $personalSocialSecurity->create($payload);

        } catch (\Exception $exception) {
            Log::error($exception);
            return $this->response->errorInternal('增加失败');
        }
        return $this->response->accepted();
    }

//    //增加薪资信息
//    public function securityDetail(Request $request, User $user,PersonalJob $personalJob)
//    {
//        $payload = $request->all();
//        $userid = $user->id;
//
//        $PersonalJobInfo = PersonalJob::where('user_id', $userid)->get();
//        //dd($depatments);
//        return $this->response->collection($depatments, new JobTransformer());
//    }


    //获取未审核人员信息
    public function entry(Request $request, User $user)
    {
        $payload = $request->all();
        $userInfo = $user->where('entry_status',$payload['entry_status'])->orderBy('created_at', 'desc')->get();

        return $this->response->collection($userInfo, new UserTransformer());
    }

    //审核人员信息
    public function audit(Request $request, User $user)
    {

        $payload = $request->all();

        $status = $payload['entry_status'];
        if ($user->entry_status == $status)
            return $this->response->noContent();
        $now = Carbon::now();
        $userid = $user->id;

        if($status == 3){

            $array = [
                'entry_status' => $payload['entry_status'],
                'password' => User::USER_PSWORD,
            ];

            $departmentarray = [
                'department_id' => User::USER_DEPARTMENT_DEFAULT,
                'user_id' => $userid,
            ];

            DepartmentUser::create($departmentarray);
        }else{
            $array = [
                'entry_status' => $payload['entry_status'],
            ];
        }
        try {
//                // 操作日志
                $operate = new OperateEntity([
                    'obj' => $user,
                    'title' => null,
                    'start' => $user->entry_status,
                    'end' => $payload['entry_status'],
                    'method' => OperateLogMethod::UPDATE,
                ]);
                event(new OperateLogEvent([
                    $operate,
                ]));
            $user->update($array);
        } catch (\Exception $exception) {
            Log::error($exception);
            return $this->response->errorInternal('修改失败');
        }
        return $this->response->accepted();
    }

    //获取用户门户
    public function portal(Request $request, User $user)
    {
        $payload = $request->all();
        $user_id = $user->id;

        $userInfo = $user->where('id',$user_id)->get();

        return $this->response->collection($userInfo, new UserTransformer());
    }

    //获取用户门户
    public function entryDetail(Request $request, User $user)
    {
        return $this->response->item($user, new UserTransformer());

    }

    public function editPosition(Request $request, User $user)
    {

        $payload = $request->all();
        $userId = $user->id;

        $array = [
            'user_id'=>$userId,
            'department_id'=>hashid_decode($payload['department_id']),
        ];

        DB::beginTransaction();
        try {
            $num = DB::table("department_user")->where('user_id',$userId)->where('type','!=',1)->delete();
            DepartmentUser::create($array);
            // 操作日志
            $operate = new OperateEntity([
                'obj' => $user,
                'title' => null,
                'start' => $user->id,
                'end' => $payload['department_id'],
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




}
