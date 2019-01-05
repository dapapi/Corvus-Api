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
        if ($request->has('bill')) {
            $bill = $payload['bill'];
            unset($payload['bill']);
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
    }
}
