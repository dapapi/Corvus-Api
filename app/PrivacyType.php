<?php

namespace App;

use App\Models\PrivacyUser;

abstract class PrivacyType
{
    const DEFULT = '0';
    const OTHER = '1';

    const SIGN_CONTRACT_STATUS ='sign_contract_status';

    const BLOGGER_BILL ='bill';

    const PROJECT_BILL ='bill';
    const FEE ='fee';

    const PROJECT_EXPENDITURE ='projected_expenditure';

    const EXPENDITURESUM ='expendituresum';

    const CONTRACTMONEY ='contractmoney';

    const HATCH_STAR_AT ='hatch_star_at';

    const HATCH_END_AT ='hatch_end_at';
    // 艺人的隐私字段
    const STAR_RISK_POINT ='star_risk_point';
    public static function getStar()
    {           $project = array();
        $project[] = 'star_risk_point';

        return $project;
    }
    public static function getProject()
    {           $project = array();
        $project[] = 'bill';
        $project[] = 'fee';
        $project[] = 'projected_expenditure';
        $project[] = 'expendituresum';
        $project[] = 'contractmoney';

        return $project;
    }
    public static function getBlogger()
    {           $blogger = array();
        $blogger[] = 'hatch_star_at';
        $blogger[] = 'hatch_end_at';


        return $blogger;
    }

    public static function getTrail()
    {           $trail = array();

        $trail[] = 'fee';
        return $trail;
    }

    public static function isPrivacy($moduleable_type, $moduleable_field)
    {
        if($moduleable_type ==  ModuleableType::PROJECT){
         $result =  in_array($moduleable_field, PrivacyType::getProject());
          return $result;
        }else  if($moduleable_type ==  ModuleableType::BLOGGER){
            $result =  in_array($moduleable_field, PrivacyType::getBlogger());
            return $result;
        }else  if($moduleable_type ==  ModuleableType::TRAIL){
            $result =  in_array($moduleable_field, PrivacyType::getTrail());
            return $result;
        }else  if($moduleable_type ==  ModuleableType::STAR){
            $result =  in_array($moduleable_field, PrivacyType::getStar());
            return $result;
        }
        else {
            return false;
        }
    }

    public static function excludePrivacy($user_id, $modulable_id,$moduleable_type, $moduleable_field)
    {
        $array['moduleable_id'] = $modulable_id;
        $array['moduleable_type'] = $moduleable_type;
        $array['moduleable_field'] = $moduleable_field;
        $array['user_id'] = $user_id;
        $result = PrivacyUser::where($array)->first();
        return $result;
    }
}
