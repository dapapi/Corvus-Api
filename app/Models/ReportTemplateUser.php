<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ReportTemplateUser extends Model
{
    protected $table = 'report_template_user';
    protected $fillable = [
        'report_template_name_id',
        'user_id',


    ];

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

        return $this->morphToMany(Task::class, 'resourceable','task_resources');
    }

    public function broker()
    {
        return $this->belongsTo(User::class, 'broker_id', 'id');

    }

}
