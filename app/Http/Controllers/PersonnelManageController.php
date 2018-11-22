<?php

namespace App\Http\Controllers;

use App\Http\Transformers\UserTransformer;
use App\Events\OperateLogEvent;

use App\Models\Department;
use Carbon\Carbon;
use App\User;
use App\Models\Record;
use App\Models\Education;

use App\Models\FamilyData;
use App\Models\PersonalDetail;

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
                $query->where('position_type',1)->count();

                //$result1 = $query->where('position_type',2)->count();

                //dd($result1);
                $status = addslashes($request->input('status'));//状态
                $positionType = addslashes($request->input('position_type'));//在职状态
                $entryTime = addslashes($request->input('entry_time'));//入职日期
                $ehireShape = addslashes($request->input('hire_shape'));//聘用形式

                $position = addslashes($request->input('position'));//职位

                $search = addslashes($request->input('search'));//职位

                $startDate = date('Y-'.$entryTime.'-01');
                $endDate =  date('Y-m-d', strtotime("$startDate +1 month -1 day"));


                if(!empty($username)) {
                    $query->where('name','like','%'.$username.'%');
                }

                if(!empty($positionType)) {
                    $query->where('position_type',$positionType);
                }

                // 1 正式 2实习 3管培生 4外包

//                if(!empty($status)) {
//                   if($status == 1){
//                       $query->where('status',User::USER_STATUS_ONE)->orWhere('status',User::USER_STATUS_TOW);
//
//                   }elseif($status == 2){
//                       $query->where('status',User::USER_STATUS_FOUR)->orWhere('hire_shape',User::HIRE_SHAPE_INTERN);
//
//                   }elseif($status == 3){
//                       $query->where('status',User::USER_STATUS_FOUR)->orWhere('hire_shape',User::HIRE_SHAPE_GUANPEI);
//
//                   }elseif($status == 4){
//                       $query->where('status',User::USER_STATUS_FOUR)->orWhere('hire_shape',User::HIRE_SHAPE_OUT);
//
//                   }
//                }

                if($status == 5){
                    $query->where('status',User::USER_TYPE_DEPARTUE);
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
                $payload['status'] =  Users::USER_STATUS_ONE;
            }elseif($payload['status_type']==2){
                $payload['status'] =  Users::USER_STATUS_TOW;
            }elseif($payload['status_type']==3){
                $payload['status'] =  Users::USER_STATUS_THREE;
            }elseif($payload['status_type']==4){
                $payload['status'] =  Users::USER_STATUS_FOUR;
            }
            $payload['password'] = Users::USER_PSWORD;

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


//        const  HIRE_SHAPE_INTERN = 2;   //实习生
//        const  HIRE_SHAPE_GUANPEI = 3;   //管培生
//        const  HIRE_SHAPE_OUT = 4;      //外包
        //离职
//        if($status == 2){
//            $array = [
//                'archive_time' => User::USER_DEPARTUE,
//            ];
//         //归档
//        }elseif($status == 4) {
//
//            $array = [
//                'status' => User::USER_ARCHIVE,
//            ];
//
//        }else{
//            $array = [
//                'status' => User::USER_POSITIVE,
//            ];
//        }


            $array = [
                'status' => $payload['status'],
            ];
            //归档
 //       }
        DB::beginTransaction();
        try {
             if (!empty($array)) {
                 $operate = new OperateEntity([

                     'obj' => $user,
                     'title' => '状态',
                     'start' => $user->status,
                     //'end' => isset($array['status'])? $array['status'] : $array['archive_time'],
                     'end' => $array['status'],

                     'method' => OperateLogMethod::UPDATE,

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
        $res = User::belongsTo('App\User');
        dd($res);
        return $this->response->item($user, new UserTransformer());

    }

    public function edit(Request $request, User $user,PersonalDetail $personalDetail)
    {
        $payload = $request->all();
        //dd($payload['personalDetail']['id']);
        $userid=17;
        $payload['user_id'] = $userid;
        dd($payload['personalDetail']);
        try {
            $skillsData = $personalDetail->where('user_id',$userid)->count();
            if($payload['personalDetail']['id']!==""){
                dd(1);
                $personalDetail->create($payload['personalDetail']);
            }else{
                dd(2);
                $personalDetail->update($payload);
            }


        } catch (\Exception $exception) {
            Log::error($exception);
            return $this->response->errorInternal('修改失败');
        }
        return $this->response->accepted();

    }

}
