<?php

namespace App\Models;

use App\User;
use Illuminate\Database\Eloquent\Model;
use App\OperateLogMethod;
use App\Traits\OperateLogTrait;
use Illuminate\Database\Eloquent\SoftDeletes;

class Announcement extends Model
{
    use SoftDeletes;
    use OperateLogTrait;
    protected $table =  'announcement';
    protected $fillable = [
        'title', // 标题
        'scope',//公告范围
        'classify',  //分类  1 规则制度   2 内部公告
        'desc', //输入内容
        'readflag', //默认 0  未读  1 读
        'is_accessory',  // 是否选择附件  默认  0   无附件    1 有附件
        'accessory',//附件
        'accessory_name',
        'creator_id',
        'stick'  //是否制定  默认  0 不 制顶  1  制顶

    ];
    protected $dates = ['deleted_at'];

    public function scopeCreateDesc($query)
    {
       return $query->orderBy('announcement.stick','desc')->orderBy('announcement.created_at', 'desc');
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
