<?php

namespace App\Models;

use App\User;
use Illuminate\Database\Eloquent\Model;

class DraftIssuesAnswer extends Model
{
    protected $table = 'draft_issues_answer';
    protected $fillable = [
        'draft_id',
        'issues_id',
        'answer'
    ];
//隐藏字段
//'contract_type',//合同类型
//'divide_into_proportion',//分成比例

    public function scopeCreateDesc($query)
    {
        return $query->orderBy('created_at');
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'creator_id', 'id');
    }

    public function affixes()
    {
        return $this->morphMany(Affix::class, 'affixable');
    }

    public function operateLogs()
    {
        return $this->morphMany(OperateLog::class, 'logable');
    }

    public function tasks()
    {
        return $this->morphToMany(Task::class, 'resourceable', 'task_resources');
    }
    public function project()
    {
        return $this->morphToMany(Project::class, 'resourceable', 'project_resources');
    }
    public function producer()
    {
        return $this->belongsTo(User::class, 'producer_id', 'id');
    }


}
