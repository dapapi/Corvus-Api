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
    public function index(Request $request)
    {
        $users = User::orderBy('name')->get();

        return $this->response->collection($users, new UserTransformer());
    }

    public function store(Request $request)
    {

        $payload = $request->all();
        $user = Auth::guard('api')->user();
        //dd($payload['family']);

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
            // 教育背景
            foreach ($payload['education'] as $key=>$value){
                $education = [
                    'user_id' => $userid,
                    'school' =>$value['school'],
                    'specialty' =>$value['specialty'],
                    'start_time' =>$value['start_time'],
                    'end_time' =>$value['end_time'],
                    'graduate' =>$value['graduate'],
                    'degree' =>$value['degree'],
                ];
                $eduInfo = Education::create($education);
            }

            // 任职履历
            foreach ($payload['record'] as $key=>$value){
                $erecord = [
                    'user_id' => $userid,
                    'unit_name' =>$value['unit_name'],
                    'department' =>$value['department'],
                    'position' =>$value['position'],
                    'entry_time' =>$value['entry_time'],
                    'departure_time' =>$value['departure_time'],
                    'monthly_pay' =>$value['monthly_pay'],
                    'departure_why' =>$value['departure_why'],
                ];
                $recordInfo = Record::create($erecord);
            }

            // 家庭资料
            foreach ($payload['family'] as $key=>$value){
                $familyData = [
                    'user_id' => $userid,
                    'name' =>$value['name'],
                    'relation' =>$value['relation'],
                    'position' =>$value['position'],
                    'birth_time' =>$value['birth_time'],
                    'work_units' =>$value['work_units'],
                    'position' =>$value['position'],
                    'contact_phone' =>$value['contact_phone'],
                ];
                $familyInfo = FamilyData::create($familyData);
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
