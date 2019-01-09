<?php

namespace App\Repositories;
use App\PrivacyType;

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

        if ($array['user_id'] != $model->creator_id) {

            return false;

        }
        return true;

    }
    public function getPrivacy($array)
    {
        $array['is_privacy'] = PrivacyType::OTHER;
       // $isnot = PrivacyUser::where($array)->groupby('moduleable_field')->select('moduleable_field',DB::raw('group_concat(user_id) as user_ids'))->get();
        $isnot = PrivacyUser::where($array)->orderby('moduleable_field')->get();
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
}
