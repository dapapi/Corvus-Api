<?php

namespace App\Repositories;
use App\PrivacyType;
use App\ModuleableType;
use App\Models\PrivacyUser;

class PrivacyUserRepository
{

    public function is_creator($array, $model)
    {
        if ($array['user_id'] == $model->creator_id) {
            return $this->response->errorMethodNotAllowed('不能添加');
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
                $isnot = PrivacyUser::where($array)->first();
                if(!$isnot){
                    $privacyUser = PrivacyUser::create($array);
                }
            }
        }

        if ($request->has('projected_expenditure')) {
            $fee = $payload['projected_expenditure'];
            unset($payload['projected_expenditure']);
            foreach ($fee as $key => &$value) {
                $array['moduleable_field'] = PrivacyType::PROJECT_EXPENDITURE;
                $array['is_privacy'] = PrivacyType::OTHER;
                $array['user_id'] = hashid_decode($value);
                $isnot = PrivacyUser::where($array)->first();
                if(!$isnot){
                    $privacyUser = PrivacyUser::create($array);
                }
            }
        }


        if ($request->has('expendituresum')) {
            $fee = $payload['expendituresum'];
            unset($payload['expendituresum']);
            foreach ($fee as $key => &$value) {
                $array['moduleable_field'] = PrivacyType::EXPENDITURESUM;
                $array['is_privacy'] = PrivacyType::OTHER;
                $array['user_id'] = hashid_decode($value);
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
                $isnot = PrivacyUser::where($array)->first();
                if(!$isnot){
                    $privacyUser = PrivacyUser::create($array);
                }
            }
        }

        if ($request->has('project_bill')) {
            $bill = $payload['project_bill'];
            unset($payload['project_bill']);
            foreach ($bill as $key => &$value) {
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
}
