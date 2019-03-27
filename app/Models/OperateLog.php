<?php

namespace App\Models;

use App\ModuleableType;
use App\ModuleUserType;
use App\Repositories\PrivacyUserRepository;
use App\Repositories\ScopeRepository;
use App\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;

class OperateLog extends Model
{
    protected $fillable = [
        'user_id',
        'logable_id',
        'logable_type',
        'content',
        'method',
        'status',
        'level',
        'field_name',
        'field_title',
    ];


    public function scopeCreateDesc($query)
    {
        return $query->orderBy('operate_logs.created_at', 'desc');
    }

    /**
     * 艺人跟进权限
     * @param $query
     * @author lile
     * @date 2019-03-27 11:02
     */
    public function scopeStarOperateLogSearchData($query)
    {
        //本人相关，本部门相关，本部门及下属部门，本部门及同级部门，全部，获取有权限查看的人
        $users = (new ScopeRepository())->getDataViewUsers(537,true);

        if (is_array($users) && count($users) == 0){
            $query->whereRaw("1=1");
        }
        elseif ($users == null){
            $query->whereRaw("0 = 1");
        }elseif(is_array($users)){
            $users = implode($users,",");
            $query->whereRaw(
                "(select s.id from stars as s 
            left join module_users as mu on mu.moduleable_id = s.id and mu.moduleable_type='".ModuleableType::STAR."' 
            and mu.type = ".ModuleUserType::BROKER."
            where s.id = operate_logs.logable_id and mu.user_id in ({$users}))"
            );
        }

    }

    /**
     * 博主跟进权限
     * @param $query
     * @author lile
     * @date 2019-03-27 11:02
     */
    public function scopeBloggerOperateLogSearchData($query)
    {
        //本人相关，本部门相关，本部门及下属部门，本部门及同级部门，全部，获取有权限查看的人
        $users = (new ScopeRepository())->getDataViewUsers(537,true);

        if (is_array($users) && count($users) == 0){
            $query->whereRaw("1=1");
        }
        elseif ($users == null){
            $query->whereRaw("0 = 1");
        }elseif (is_array($users)){
            $users = implode($users,",");
            $query->whereRaw(
                "(select b.id from bloggers as b 
                left join module_users as mu on mu.moduleable_id = b.id and mu.moduleable_type='" . ModuleableType::BLOGGER . "' 
                and mu.type = " . ModuleUserType::PRODUCER . "
                where b.id = operate_logs.logable_id and mu.user_id in ({$users}))"
            );
        }
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function logable()
    {
        return $this->morphTo();
    }



    /**
     * 获取日志的时候判断用户是否有权限查看该条日志
     * @return string
     * @author 李乐
     * @date 2019-03-11 14:56
     */
    public function getContentAttribute($content)
    {
        $user = Auth::guard('api')->user();
        $id = $this->attributes['logable_id'];//记录修改数据的id
        $table = $this->attributes['logable_type'];//记录修改数据的表
        $field_name = $this->attributes['field_name'];//记录修改数据的字段
        //判断是不是隐私字段
        $privacy_fields = DataDictionarie::getPrivacyFieldList();
        if (!in_array($table.".".$field_name,$privacy_fields)){
            return $content;
        }
        $repository = new PrivacyUserRepository();
        $power = $repository->has_power($table,$field_name,$id,$user->id);
        //如果是数据创建人或者有权限才可以看
        $creator = $this->user()->first();
        if ($creator->id == $user->id || $power){
            return $this->attributes['content'];
        }
        return "修改了".$this->attributes['field_title'];
    }

}
