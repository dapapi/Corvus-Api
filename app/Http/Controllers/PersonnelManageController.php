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
        $data['onjob'] = $user->where('position_type',1)->where('status','!=',User::USER_ARCHIVE)->count();
        $data['departure'] = $user->where('position_type',2)->count();

        $user = User::orderBy('entry_time','asc')
            ->where(function($query) use($request){
                //检测关键字
                //$query->where('position_type',1)->count();

                $status = addslashes($request->input('status'));//状态
                $positionType = addslashes($request->input('position_type'));//在职状态
                $entryTime = addslashes($request->input('entry_time'));//入职日期
                $ehireShape = addslashes($request->input('hire_shape'));//聘用形式

                $position = addslashes($request->input('position'));//职位

                $search = addslashes($request->input('search'));//职位

                $startDate = date('Y-'.$entryTime.'-01');
                $endDate =  date('Y-m-d', strtotime("$startDate +1 month -1 day"));


//                if(!empty($username)) {
//                    $query->where('name','like','%'.$username.'%');
//                }

                if($positionType !==2) {
                    //dd($positionType);
                    $query->where('position_type',2);
                }else {

                    // 1 正式 2实习 3管培生 4外包
                    if (!empty($status)) {
                        if ($status == 1) {
                            $query->whereIn('status', [1, 2, 4]);
                            $query->where('hire_shape', '!=', 4);

                        } elseif ($status == 2) {
                            $query->where('status', 1)->Where('hire_shape', 2);

                        } elseif ($status == 3) {
                            $query->where('status', 4)->Where('hire_shape', 3);

                        } elseif ($status == 4) {
                            //dd(11);
                            //$query->where('hire_shape',User::HIRE_SHAPE_OUT)->orWhere('status',User::USER_STATUS_OUT);
                            //  $query->where('status',5)->Where('hire_shape',4);
                            $query->where('hire_shape', User::HIRE_SHAPE_OUT)->Where('status', User::USER_STATUS_OUT);

                        }
                    }
                }

                if(!empty($entryTime)) {
                    $query->whereDate('entry_time', '>=', $startDate.' 00:00:00')->whereDate('entry_time', '<=', $endDate.' 23:59:59');
                }
                if(!empty($ehireShape)) {
                    $query->where('ehire_shape',$ehireShape);
                }

                if(!empty($search)) {
                    $query->where('name', 'like', '%'.$search.'%')->orWhere('phone', 'like', '%'.$search.'%')->orWhere('position', 'like', '%'.$search.'%')->orWhere('department', 'like', '%'.$search.'%');
                }
                //不显示存档信息
                $query->where('status','!=',User::USER_ARCHIVE);

             })->paginate($pageSize);

        $result = $this->response->paginator($user, new UserTransformer());
        $result->addMeta('date', $data);
        return $result;

    }

    public function store(Request $request,User $user)
    {

        $payload = $request->all();
        $user = Auth::guard('api')->user();

        $userEmail = User::where('email', $payload['email'])->get()->keyBy('email')->toArray();
        $pageSize = config('api.page_size');


        if(!empty($userEmail)){
            return $this->response->errorInternal('邮箱已经注册！');
        }else{
            if($payload['status_type']==1){
                $payload['status'] =  User::USER_STATUS_TRIAL;
            }elseif($payload['status_type']==2){
                $payload['status'] =  User::USER_STATUS_POSITIVE;
            }elseif($payload['status_type']==3){
                $payload['status'] =  User::USER_STATUS_DEPARTUE;
            }elseif($payload['status_type']==4){
                $payload['status'] =  User::USER_STATUS_INTERN;
            }else{
                $payload['status'] =  User::USER_STATUS_OUT;
            }
            $payload['password'] = User::USER_PSWORD;

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

        if ($user->status == $status)
            return $this->response->noContent();
        $now = Carbon::now();

        if($status == 1){
            $array = [
                'status' => User::USER_STATUS_POSITIVE,
                'hire_shape' => User::HIRE_SHAPE_OFFICIAL,
            ];
        }
        //离职
        if($status == 2){
            $array = [
                'status' => User::USER_STATUS_DEPARTUE,
                'hire_shape' => User::HIRE_SHAPE_INTERN,
            ];
         //归档
        }elseif($status == 6) {
            $array = [
                'status' => User::USER_ARCHIVE,
                'archive_time' => date('Y-m-d h:i:s',time()),
            ];
        }
        DB::beginTransaction();
        try {

             if (!empty($array)) {
                 if($status == 3){
                     $operate = new OperateEntity([
                         'obj' => $user,
                         'title' => null,
                         'start' => $user->status,
                         'end' => $array['status'],
                         'method' => OperateLogMethod::TRANSFER,

                     ]);

                 }else{
                     $operate = new OperateEntity([
                    'obj' => $user,
                    'title' => null,
                    'start' => $user->status,
                    'end' => $array['status'],
                    'method' => OperateLogMethod::UPDATE,
                ]);

                 }


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
        //$res = User::belongsTo('App\User');
        //dd($user->skills());
        return $this->response->item($user, new UserTransformer());

    }

    //增加个人信息
    public function storePersonal(Request $request, User $user,PersonalDetail $personalDetail)
    {

        $payload = $request->all();

        $userid = $user->id;

        try {
            $operate = new OperateEntity([
                    'obj' => $personalDetail,
                    'title' => null,
                    'start' => null,
                    'end' => null,
                    'method' => OperateLogMethod::CREATE,
                ]);
                event(new OperateLogEvent([
                    $operate,
                ]));

            $payload['user_id'] = $userid;
            $userArr = [
                'hire_shape' => $payload['userarr']['hire_shape'],
                'position' => $payload['userarr']['position'],
                'department' => $payload['userarr']['department'],
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
    public function editPersonal(Request $request, User $user,PersonalDetail $personalDetail)
    {

        $payload = $request->all();
        $userid = $user->id;
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

            $personalDetail->update($payload);


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

    //增加薪资信息
    public function securityDetail(Request $request, User $user,PersonalJob $personalJob)
    {
        $payload = $request->all();
        $userid = $user->id;

        $PersonalJobInfo = PersonalJob::where('user_id', $userid)->get();
        //dd($depatments);
        return $this->response->collection($depatments, new JobTransformer());
    }





}
