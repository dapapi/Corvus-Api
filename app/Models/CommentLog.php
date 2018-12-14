<?php

namespace App\Models;
use App\Helper\Common;
use App\User;
use Illuminate\Database\Eloquent\Model;

class CommentLog extends Model
{
    protected $fillable = [
        'user_id',
        'parent_id',
        'Pipe',
        'logable_id',
        'logable_type',
        'content',
        'method',
        'status',
        'level'
    ];

    public function scopeCreateDesc($query)
    {
        return $query->orderBy('created_at', 'desc');
    }

    public function user()
    {
       return $this->belongsTo(User::class);
     //   return $this->hasManyThrough(User::class, CommentLog::class, 'parent_id', 'id', 'id', 'user_id');

    }

    public function parent(){

        return $this->hasMany(CommentLog::class, 'parent_id', 'id');
    }
    public function tree()
    {

        $categorys = $this->all();
        return $this->getTree($categorys,'content','id','parent_id');
    }
//一般传进三个参数。默认P_id=0；
    public function getTree($data,$field_name,$field_id='id',$field_pid='parent_id',$pid=0)
    {
        $arr = array();
        foreach ($data as $k=>$v){
            if($v->$field_pid==$pid){
                $data[$k]["_".$field_name] = $data[$k][$field_name];
                $arr[] = $data[$k];
                foreach ($data as $m=>$n){
                    if($n->$field_pid == $v->$field_id){
                        $data[$m]["_".$field_name] = '├─ '.$data[$m][$field_name];
                        $arr[] = $data[$m];
                    }
                }
            }
        }
        return $arr;
    }
    public function pParent()
    {
        return $this->belongsTo(CommentLog::class, 'parent_id', 'id');
    }
//    public function ToCompany()
//    {
//        $commentLog = $this->parent()->first();
//        if (!$commentLog) {
//            return null;
//        }
//        $company = $this->departmentToCompany($commentLog);
//        return $company;
////        return $this->parent()->with('ToCompany');
//    }

    public function logable()
    {
        return $this->morphTo();
    }
//    public function departmentToCompany(CommentLog $commentLog)
//    {
//        if ($commentLog->parent_id == 0) {
//            return $commentLog;
//        } else {
//            $commentLog = $commentLog->pParent;
//            return $this->departmentToCompany($commentLog);
//        }
//    }

}
