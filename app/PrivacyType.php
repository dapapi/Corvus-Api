<?php

namespace App;

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
    {           $project = array();
        $project[] = 'hatch_star_at';
        $project[] = 'hatch_end_at';


        return $project;
    }
}
