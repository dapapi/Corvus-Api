<?php

namespace App\Models;

use App\Repositories\ScopeRepository;
use App\Scopes\SearchDataScope;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;

class ProjectImplode extends Model
{
    protected $table = 'project_implode';

    private static $model_dic_id = DataDictionarie::PROJECT;

    protected $fillable = [
        # project
        'id',
        'principal_id',
        'form_instance_number',
        'creator_id',
        'project_name',
        'project_type',
        'project_priority',
        'project_start_at',
        'project_end_at',
        'project_store_at',
        'principal',
        'projected_expenditure',
        'creator',
        'department_id',
        'department',
        'project_status',
        # 模版
        'sign_at',
        'launch_at',
        'platforms',
        'show_type',
        'guest_type',
        'record_at',
        'movie_type',
        'theme',
        'team_info',
//        'follow_up',
        'walk_through_at',
        'walk_through_location',
//        'walk_through_feedback',
//        'follow_up_result',
        'agreement_fee',
        'multi_channel',
        # 线索
        'resource_type',
        'trail_fee',
        'cooperation_type',
        'trail_status',
        'client',
        # 日志
        'last_follow_up_user_id',
        'last_follow_up_user_name',
        'last_follow_up_at',
        'latest_time',
        'last_updated_at',
        # 艺人
        'team_m',
        'team_producer',
        'stars',
        'star_ids',
        'bloggers',
        'blogger_ids',
        'producer',
        'producer_id',
        'broker',
        'broker_id',
    ];

    public static function getConditionSql()
    {
        $user = Auth::guard("api")->user();
        $userid = $user->id;
        $rules = (new ScopeRepository())->getDataViewUsers(self::$model_dic_id);
        if (array_key_exists('rules', $rules))
            array_walk($rules['rules'], function(&$item, $key) {
                $item['field'] = str_replace('projects', 'project_implode', $item['field']);
            });
        $where = (new SearchDataScope())->getConditionSql($rules);
        $where .= <<<AAA
        or ({$userid} in (
                select u.id from project_implode as s
                left join module_users as mu on mu.moduleable_id = s.id and 
                mu.moduleable_type='project' 
                left join users as u on u.id = mu.user_id where s.id = project_implode.id
            )
        )
AAA;
        return $where;

    }
}
