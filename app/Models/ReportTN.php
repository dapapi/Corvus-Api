<?php

namespace App\Models;

use App\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class ReportTN extends Model
{
    use SoftDeletes;
    protected $table = 'report_template_department';
    protected $fillable = [
        'report_template_name_id', // 模板名称ReportTN.php
        'department_id',



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
