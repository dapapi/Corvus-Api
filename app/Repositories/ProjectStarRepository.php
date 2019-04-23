<?php

namespace App\Repositories;

use App\Models\Project;
use App\Models\ProjectTalent;
use App\ModuleableType;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class ProjectStarRepository
{
    /**
     * 批量为项目增加目标艺人
     * @param $project Project 项目
     * @param $star_list array 艺人和博主列表 [['id'=>1234],['flag'=>'star|blogger']]
     * 存储项目关联艺人
     */
    public function store($project, $star_list)
    {
        $trail_star_list = [];
        foreach ($star_list as $key => $star) {
            if ($star['flag'] == ModuleableType::BLOGGER)
                $starable_type = ModuleableType::BLOGGER;
            elseif ($star['flag'] == ModuleableType::STAR)
                $starable_type = ModuleableType::STAR;
            $trail_star_list[] = [
                'talent_id' => hashid_decode($star['id']),
                'talent_type' => $starable_type,
                'talent_name' => array_key_exists('name', $star) ? $star['name'] : $star['nickname'],
                'trail_id' => $project->id,
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now(),
            ];

        }
        DB::table("project_talent")->insert($trail_star_list);
    }

    /**
     * 删除项目对应的目标艺人
     * @param $project_id Project->id 项目id
     */
    public function deleteProjectStar($project_id)
    {
        ProjectTalent::where('project_id', $project_id)->delete();
    }

    /**
     * 获取项目关联的博主和艺人列表
     *
     * @param $project_id Project->id 项目id
     * @return string string 姓名字符串半角逗号连接
     */
    public function getStarListByProjectId($project_id)
    {
        $first = ProjectTalent::select('stars.id', 'stars.name', DB::raw('\'star\''))
            ->join('stars', function ($join) use ($project_id) {
                $join->on('stars.id', 'project_talent.talent_id')
                    ->whereRaw("project_talent.talent_type = '" . ModuleableType::STAR . "'");
            })
            ->where('project_talent.project_id', $project_id);

        $res = ProjectTalent::select('bloggers.id', 'nickname', DB::raw('\'blogger\' as flag'))->join('bloggers', function ($join) {
            $join->on('project_talent.talent_id', '=', 'bloggers.id')
                ->where("project_talent.talent_type", 'blogger');
        })->where('project_talent.trail_id', $project_id)
            ->union($first)
            ->get()->toArray();

        $starIdArr = [];
        $starNameArr = [];
        $bloggerIdArr = [];
        $bloggerNameArr = [];
        array_walk($res, function (&$item) use (&$starIdArr, &$bloggerIdArr, &$starNameArr, &$bloggerNameArr) {
            if ($item['flag'] == 'star') {
                $starIdArr[] = $item['id'];
                $starNameArr[] = $item['nickname'];
            } else {
                $bloggerIdArr[] = $item['id'];
                $bloggerNameArr[] = $item['nickname'];
            }
        });
        $starIds = implode(',', $starIdArr);
        $starName = implode(',', $starNameArr);
        $bloggerIds = implode(',', $bloggerIdArr);
        $bloggerName = implode(',', $bloggerNameArr);
        DB::table('project_implode')
            ->where('id', $project_id)
            ->update([
                'stars' => $starName,
                'star_ids' => $starIds,
                'bloggers' => $bloggerName,
                'blogger_ids' => $bloggerIds,
            ]);

        return implode(",", array_column($res, 'nickname'));
    }
}
