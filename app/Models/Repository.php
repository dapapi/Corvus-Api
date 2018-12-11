<?php

namespace App\Models;

use App\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Repository extends Model
{
    use SoftDeletes;
    protected $table =  'repository';
    protected $fillable = [
        'title', // 标题
        'department_id',//对象id
        'desc',  //详情
        'user_id', //发行人
        'scope', //对象id
        'stick', //置顶
        'comments_no',  //禁止评论


    ];
    protected $dates = ['deleted_at'];

    public function scopeCreateDesc($query)
    {
       return $query->orderBy('stick','desc')->orderBy('created_at', 'desc');
//       return $query->orderByRaw('created_at,stick ASC');

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
    public function scope()
    {
        return $this->hasMany(AnnouncementScope::class, 'announcement_id', 'id');
    }
    public function broker()
    {
        return $this->belongsTo(User::class, 'broker_id', 'id');

    }

}
