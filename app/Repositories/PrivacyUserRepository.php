<?php

namespace App\Repositories;
use App\Models\ModuleUser;
use App\PrivacyType;

use Doctrine\Common\Collections\Collection;
use Illuminate\Http\Request;
use App\ModuleableType;
use Illuminate\Support\Facades\DB;
use App\Models\PrivacyUser;
use Exception;
use Illuminate\Support\Facades\Auth;
class PrivacyUserRepository
{

    public function is_creator($array, $model)
    {
        if ($array['user_id'] != $model->creator_id && $array['user_id'] != $model->principal_id) {

            return false;

        }
        return true;

    }
    public function getPrivacy($array)
    {
        $array['is_privacy'] = PrivacyType::OTHER;
       // $isnot = PrivacyUser::where($array)->groupby('moduleable_field')->select('moduleable_field',DB::raw('group_concat(user_id) as user_ids'))->get();
        if($array['moduleable_type'] == ModuleableType::PROJECT) {
            $isnot = PrivacyUser::where($array)->orderby('moduleable_field')->get();
        }elseif ($array['moduleable_type'] == ModuleableType::BLOGGER){
            $isnot = PrivacyUser::where($array)->orderby('moduleable_field')->get();
        }
        return $isnot;
    }
    public function updatePrivacy($array,$request, $payload)
    {

        if ($request->has('projected_expenditure')) {
                $p_id = $payload['projected_expenditure'];
                $array['moduleable_field'] = PrivacyType::PROJECT_EXPENDITURE;
                $array['is_privacy'] = PrivacyType::OTHER;
                $this->addAll($p_id,$array);

//                $isnot = PrivacyUser::where($array)->first();
//                if(!$isnot){
//                    $privacyUser = PrivacyUser::create($array);
//                }

                }
         if ($request->has('progect_bill')) {
                        $p_id = $payload['progect_bill'];
                        $array['moduleable_field'] = PrivacyType::PROJECT_BILL;
                        $array['is_privacy'] = PrivacyType::OTHER;
                        $this->addAll($p_id,$array);

        //                $isnot = PrivacyUser::where($array)->first();
        //                if(!$isnot){
        //                    $privacyUser = PrivacyUser::create($array);
        //                }

                }
             if ($request->has('fee')) {
                            $p_id = $payload['fee'];
                            $array['moduleable_field'] = PrivacyType::FEE;
                            $array['is_privacy'] = PrivacyType::OTHER;
                            $this->addAll($p_id,$array);

            //                $isnot = PrivacyUser::where($array)->first();
            //                if(!$isnot){
            //                    $privacyUser = PrivacyUser::create($array);
            //                }

                    }
             if ($request->has('sign_contract_status')) {
                            $p_id = $payload['sign_contract_status'];
                            $array['moduleable_field'] = PrivacyType::SIGN_CONTRACT_STATUS;
                            $array['is_privacy'] = PrivacyType::OTHER;
                            $this->addAll($p_id,$array);

            //                $isnot = PrivacyUser::where($array)->first();
            //                if(!$isnot){
            //                    $privacyUser = PrivacyUser::create($array);
            //                }

                    }
             if ($request->has('expendituresum')) {
                            $p_id = $payload['expendituresum'];
                            $array['moduleable_field'] = PrivacyType::EXPENDITURESUM;
                            $array['is_privacy'] = PrivacyType::OTHER;
                            $this->addAll($p_id,$array);

            //                $isnot = PrivacyUser::where($array)->first();
            //                if(!$isnot){
            //                    $privacyUser = PrivacyUser::create($array);
            //                }

                    }
             if ($request->has('contractmoney')) {
                            $p_id = $payload['contractmoney'];
                            $array['moduleable_field'] = PrivacyType::CONTRACTMONEY;
                            $array['is_privacy'] = PrivacyType::OTHER;
                            $this->addAll($p_id,$array);

            //                $isnot = PrivacyUser::where($array)->first();
            //                if(!$isnot){
            //                    $privacyUser = PrivacyUser::create($array);
            //                }

                    }
             if ($request->has('blogger_bill')) {
                            $p_id = $payload['blogger_bill'];
                            $array['moduleable_field'] = PrivacyType::BLOGGER_BILL;
                            $array['is_privacy'] = PrivacyType::OTHER;
                            $this->addAll($p_id,$array);

            //                $isnot = PrivacyUser::where($array)->first();
            //                if(!$isnot){
            //                    $privacyUser = PrivacyUser::create($array);
            //                }

                    }
        if ($request->has('hatch_at')) {
                $p_id = $payload['hatch_at'];
                unset($payload['hatch_at']);
                $array['moduleable_field'] = PrivacyType::HATCH_STAR_AT;
                $array['is_privacy'] = PrivacyType::OTHER;
                $isnot = PrivacyUser::where($array)->first();
                $this->addAll($p_id,$array);
                $array['moduleable_field'] = PrivacyType::HATCH_END_AT;
                $array['is_privacy'] = PrivacyType::OTHER;
                $this->addAll($p_id,$array);

            }


    }
    public function addPrivacy($array,$request, $payload)
    {

        if ($request->has('sign_contract_status')) {
            $sign_contract_status = $payload['sign_contract_status'];
            unset($payload['sign_contract_status']);
            foreach ($sign_contract_status as $key => &$value) {
                $array['moduleable_field'] = PrivacyType::SIGN_CONTRACT_STATUS;
                $array['is_privacy'] = PrivacyType::OTHER;
                $array['user_id'] = hashid_decode($value);
//                $this->add($array);
                $isnot = PrivacyUser::where($array)->first();
                if(!$isnot){
                    $privacyUser = PrivacyUser::create($array);
                }
            }
        }
        if ($request->has('blogger_bill')) {
            $bill = $payload['blogger_bill'];
            unset($payload['blogger_bill']);
            foreach ($bill as $key => &$value) {
                $array['moduleable_field'] = PrivacyType::BLOGGER_BILL;
                $array['is_privacy'] = PrivacyType::OTHER;
                $array['user_id'] = hashid_decode($value);
                $isnot = PrivacyUser::where($array)->first();
                if(!$isnot){
                    $privacyUser = PrivacyUser::create($array);
                }
            }
        }

        if ($request->has('fee')) {
            $fee = $payload['fee'];
            unset($payload['fee']);
            foreach ($fee as $key => &$value) {
                $array['moduleable_field'] = PrivacyType::FEE;
                $array['is_privacy'] = PrivacyType::OTHER;
                $array['user_id'] = hashid_decode($value);
//                $this->add($array);
                $isnot = PrivacyUser::where($array)->first();
                if(!$isnot){
                    $privacyUser = PrivacyUser::create($array);
                }
            }
        }

        if ($request->has('projected_expenditure')) {
            $projected_expenditure = $payload['projected_expenditure'];
            unset($payload['projected_expenditure']);
            foreach ($projected_expenditure as $key => &$value) {
                $array['moduleable_field'] = PrivacyType::PROJECT_EXPENDITURE;
                $array['is_privacy'] = PrivacyType::OTHER;
                $array['user_id'] = hashid_decode($value);

//                $this->add($array);
                $isnot = PrivacyUser::where($array)->first();
                if(!$isnot){
                    $privacyUser = PrivacyUser::create($array);
                }
            }
        }


        if ($request->has('expendituresum')) {
            $expendituresum = $payload['expendituresum'];
            unset($payload['expendituresum']);
            foreach ($expendituresum as $key => &$value) {
                $array['moduleable_field'] = PrivacyType::EXPENDITURESUM;
                $array['is_privacy'] = PrivacyType::OTHER;
                $array['user_id'] = hashid_decode($value);
//                $this->add($array);
                $isnot = PrivacyUser::where($array)->first();
                if(!$isnot){
                    $privacyUser = PrivacyUser::create($array);
                }
            }
        }

        if ($request->has('contractmoney')) {
            $fee = $payload['contractmoney'];
            unset($payload['contractmoney']);
            foreach ($fee as $key => &$value) {
                $array['moduleable_field'] = PrivacyType::CONTRACTMONEY;
                $array['is_privacy'] = PrivacyType::OTHER;
                $array['user_id'] = hashid_decode($value);
//                $this->add($array);
                $isnot = PrivacyUser::where($array)->first();
                if(!$isnot){
                    $privacyUser = PrivacyUser::create($array);
                }
            }
        }

        if ($request->has('project_bill')) {
            $project_bill = $payload['project_bill'];
            unset($payload['project_bill']);
            foreach ($project_bill as $key => &$value) {
                $array['moduleable_field'] = PrivacyType::PROJECT_BILL;
                $array['is_privacy'] = PrivacyType::OTHER;
                $array['user_id'] = hashid_decode($value);
                $isnot = PrivacyUser::where($array)->first();
                if(!$isnot){
                    $privacyUser = PrivacyUser::create($array);
                }
            }
        }
        if ($request->has('hatch_at')) {
            $project_bill = $payload['hatch_at'];
            unset($payload['hatch_at']);
            foreach ($project_bill as $key => &$value) {
                $array['moduleable_field'] = PrivacyType::HATCH_STAR_AT;
                $array['is_privacy'] = PrivacyType::OTHER;
                $array['user_id'] = hashid_decode($value);
                $isnot = PrivacyUser::where($array)->first();
                if(!$isnot){
                    $privacyUser = PrivacyUser::create($array);
                }
                $array['moduleable_field'] = PrivacyType::HATCH_END_AT;
                $array['is_privacy'] = PrivacyType::OTHER;
                $array['user_id'] = hashid_decode($value);
                $isnot = PrivacyUser::where($array)->first();
                if(!$isnot){
                    $privacyUser = PrivacyUser::create($array);
                }
            }
        }

    }
    public function addAll($p_ids,$array)
{

    $participantDeleteId = PrivacyUser::where('moduleable_id',$array['moduleable_id'])->where('moduleable_type',$array['moduleable_type'])->where('moduleable_field',$array['moduleable_field'])->where('is_privacy',$array['is_privacy'])->get(['id'])->toArray();

    foreach ($participantDeleteId as $key => &$participantDeleteId) {
        try {

            $moduleUser = PrivacyUser::where($participantDeleteId)->first();
            if ($moduleUser) {//数据存在则从数据库中删除
                $moduleUser->delete();
            } else {
                array_splice($participantDeleteId, $key, 1);
            }
        } catch (Exception $e) {
            array_splice($participantDeleteId, $key, 1);
        }
    }


    $p_ids = array_unique($p_ids);

    foreach ($p_ids as $key => &$p_id) {
        try {
            $p_id = hashid_decode($p_id);
            $array['user_id'] = $p_id;
            $moduleUser = PrivacyUser::where('user_id', $array['user_id'])->where('moduleable_id', $array['moduleable_id'])->where('moduleable_type',$array['moduleable_type'])->where('moduleable_field', $array['moduleable_field'])->where('is_privacy',  $array['is_privacy'])->first();

            if (!$moduleUser) {//不存在则添加
                PrivacyUser::create($array);

            } else {//存在则从列表中删除
                array_splice($p_ids, $key, 1);
//
            }
        } catch (Exception $e) {
            array_splice($p_ids, $key, 1);
        }
    }
    //返回添加成功或者删除成功的参与人和宣传人
    return [$p_ids, $participantDeleteId];
    }

    /**
     *判断一个人对某张表的某条数据的某个字段是否有权限
     * @param $table 表明
     * @param $field    字段名
     * @param $data_id   数据id
     * @param $userid 用户id
     * @return bool
     * @author 李乐
     * @date 2019-03-11 14:55
     */
    public function has_power($table,$field,$data_id,$userid)
    {
        $user_ids = PrivacyUser::where('moduleable_id',$data_id)
            ->where('moduleable_field',$field)
            ->where('moduleable_type',$table)
            ->pluck('user_id');
        if ($user_ids->count() == 0){//没有针对$table表$data_id 的$field字段的数据权限管理，则表示可以查看日志
            return true;
        }else{
            if($user_ids.contains($userid)){ //如果$userid在有权限用户范围内则可以查看日志
                return true;
            }else{
                return false;
            }
        }
    }

}
