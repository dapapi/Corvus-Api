<?php

namespace App\Models;

use App\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Report extends Model
{
    use SoftDeletes;
    protected $fillable = [
        'template_name', // 模板名称
        'colour',
        'frequency',  //频率
        'department_id', //模板对象id
        'member', //成员id
        'creator_id',  // 创建人  id


    ];
    //设置主键
//    public $primaryKey = 'id';

//    //设置日期时间格式
//    public $dateFormat = 'U';
//
//    protected $guarded = ['id','updated_at','created_at'];
    protected $dates = ['deleted_at'];
    public function scopeCreateDesc($query)
    {

        return $query->orderBy('id');

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
//    public function getgroup(){
//        echo 1;die;
//        return $this->hasOne(Groups::class,'group_id','id');
//    }

}
