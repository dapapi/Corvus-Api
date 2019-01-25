<?php

namespace App\Http\Transformers;

use App\Models\Blogger;
use App\Models\Contract;
use App\Models\Star;
use League\Fractal\TransformerAbstract;

class ContractDateTransformer extends TransformerAbstract
{
    protected $availableIncludes = ['project', 'star'];

    private $isAll = true;

    public function __construct($isAll = true)
    {
        $this->isAll = $isAll;
    }


    public function transform(Contract $contract)
    {
        $arr = [
            //'contract_number' => $contract->contract_number,
            'contract_start_date' => $contract->contract_start_date,
            'contract_end_date' => $contract->contract_end_date,
         //   'type' => $contract->type,
          //  'creator_name' => $contract->creator_name,
        ];
//        if ($contract->project)
//            $arr['project'] = $contract->project->title;
//
//        if ($contract->star_type) {
//            $stars = explode(',', $contract->stars);
//            $talentArr = [];
//            if ($contract->star_type == 'bloggers') {
//                foreach ($stars as $star) {
//                    $blogger = Blogger::find($star);
//                    if ($blogger)
//                        $talentArr[] = $blogger->nickname;
//                    else
//                        continue;
//                }
//            } else {
//                foreach ($stars as $star) {
//                    $star = Star::find($star);
//                    if ($star)
//                        $talentArr[] = $star->name;
//                    else
//                        continue;
//                }
//            }
//
//            $talents = implode('、', $talentArr);
//            $arr['talents'] = $talents;
//        }

        return $arr;
    }

    public function includeProject(Contract $contract)
    {
        $project = $contract->project;
        if (!$project)
            return nullValue();

        return $this->item($project, new ProjectTransformer($this->isAll));
    }


    public function includeStars(Contract $contract)
    {
        $project = $contract->project;
        if (!$project)
            return nullValue();

        return $this->item($project, new ProjectTransformer($this->isAll));
    }
}