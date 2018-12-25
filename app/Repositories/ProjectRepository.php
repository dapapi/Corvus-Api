<?php

namespace App\Repositories;

use App\Models\Project;
use App\Models\Trail;
use App\Models\TrailStar;
use Illuminate\Support\Facades\DB;

class ProjectRepository
{
    public static function getProjectBySatrId($star_id)
    {
        $result =
            (new Project())->setTable("p")->from("projects as p")
            ->leftJoin('trails as t','t.id','=','p.trail_id')
            ->leftJoin('trail_star as ts','ts.trail_id','=','t.id')
            ->where('ts.star_id',$star_id)
            ->where('ts.type',TrailStar::EXPECTATION)
            ->get([
                'p.title',
                'p.principal_id',
                DB::raw(//1:进行中 2:完成  3:终止 4:删除
                    " case p.status
                    when 1 then '进行中'
                    when 2 then '完成'
                    when 3 then '终止'
                    when 4 then '删除'
                    end 'status'"
                ),
                'p.created_at',
            ]);
        return $result;
    }
}
