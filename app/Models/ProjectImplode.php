<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ProjectImplode extends Model
{
    protected $table = 'project_implode';

    protected $fillable = [
        # project
        'id',
        'principal_id',
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
        'follow_up',
        'walk_through_at',
        'walk_through_location',
        'walk_through_feedback',
        'follow_up_result',
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
}
