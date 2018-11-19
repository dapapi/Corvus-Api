<?php

namespace App\Http\Controllers;

use App\Http\Transformers\UserTransformer;

use App\Models\Department;
use App\User;
use App\Models\Users;
use App\Models\Record;
use App\Models\Education;
use App\Models\FamilyData;
use App\Models\PersonalSkills;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class PersonnelManageController extends Controller
{
    public function index(Request $request,Users $users)
    {
       // $users = User::orderBy('name')->get();


        $payload = $request->all();
        $pageSize = $request->get('page_size', config('app.page_size'));
        $query = $users->affixes();
        $name = 'cxy';
        if($name){
            $query->where('name', $name);
        }

        $user = User::orderBy('entry_time','asc')
            ->where(function($query) use($request){
                //检测关键字

                $status = addslashes($request->input('status'));//状态
                $entryTime = addslashes($request->input('entry_time'));//入职日期
                $ehireShape = addslashes($request->input('hire_shape'));//聘用形式

                $username = addslashes($request->input('name'));//姓名
                $phone = addslashes($request->input('phone'));//手机号
                $position = addslashes($request->input('position'));//职位

                $search = addslashes($request->input('search'));//职位

                $startDate = date('Y-'.$entryTime.'-01');
                $endDate =  date('Y-m-d', strtotime("$startDate +1 month -1 day"));


                if(!empty($username)) {
                    $query->where('name','like','%'.$username.'%');
                }

                if(!empty($status)) {
                 $query->where('status',$status);
                }

                if(!empty($entryTime)) {
                    $query->whereDate('entry_time', '>=', $startDate.' 00:00:00')->whereDate('entry_time', '<=', $endDate.' 23:59:59');
                }

                if(!empty($ehireShape)) {
                    $query->where('ehire_shape',$ehireShape);
                }

                if(!empty($search)) {

                    $query->where('name', 'like', '%'.$search.'%')->orWhere('phone', 'like', '%'.$search.'%');//->orWhere('position', 'like', '%'.$search.'%');

                }



             }) ->paginate($request->input('num', 5));



        return $this->response->paginator($user, new UserTransformer());

    }

    public function store(Request $request)
    {

        $payload = $request->all();
        $user = Auth::guard('api')->user();

        //dd($payload['family']);

        $userEmail = User::where('email', $payload['email'])->get()->keyBy('email')->toArray();
        $pageSize = config('api.page_size');
//        if(!empty($payload['family'])) {
//            dd(1);
//        }else{
//            dd(2);
//        }

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
            $user = Users::create($payload);
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


         } catch (Exception $e) {
            DB::rollBack();
            Log::error($e);
            return $this->response->errorInternal();
        }
            DB::commit();
            return $this->response->accepted();


        }
    }
}
