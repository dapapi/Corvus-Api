<?php
/**
 * Created by PhpStorm.
 * User: apple
 * Date: 2018/12/20
 * Time: 10:38 AM
 */

namespace App\Scopes;


use App\ModuleableType;
use App\Repositories\ScopeRepository;
use function foo\func;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Scope;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class SearchDataScope implements Scope
{
    public function apply(Builder $builder, Model $model)
    {
        $builder->where(function ($query){
            $this->getDataViewCondition($query);
        });
    }

    public function getDataViewCondition($query)
    {
        $user = Auth::guard("api")->user();
        $userid = $user->id;
        $condition = [];
        $rules = (new ScopeRepository())->getDataViewUsers();
        if(count($rules) == 0){
            return $query;
        }
        $this->getCondition($query,$rules,$userid);
    }
    /**
     * 获取搜索条件
     */
    public function getCondition($query,$rules,$userid)
    {
        if($rules === null){
            return $query->whereRaw('0 = 1'); //不查询任何数据
        }
        if(is_array($rules) && count($rules) == 0){
            return $query;
        }
//        $op_list = ['>','>=','<','<=','like','in'];
        //"{"rules":[{"field":"created_id","op":"in","value":[1,16,2]},{"field":"principal_id","op":"in","value":[1,16,2]}],"op":"or"}"


//        foreach ($rules['rules'] as $key => $value){
//            switch ($value['op']){
//                case 'in':
//                    $condition[] = $query->whereIn($value['field'],$value['value']);
//                    break;
//                case '>':
//                case '>=':
//                case '<':
//                case '<=':
//                case 'like':
//                    $condition[] = $query->where($value['field'],$value['op'],$value['value']);
//
//            }
//        }

        switch ($rules['op']){
            case 'or':
                $query->where(function ($query)use ($rules){
                    foreach ($rules['rules'] as $key => $value){
                        switch ($value['op']){
                            case 'in':
                                if($value['value'] == null){
                                    $condition[] = $query->orWhereRaw("{$value['field']} in (null)");
                                }else{
                                    $condition[] = $query->orWhereIn($value['field'],$value['value']);
                                }
                                break;
                            case '>':
                            case '>=':
                            case '<':
                            case '<=':
                            case 'like':
                                $condition[] = $query->orWhere($value['field'],$value['op'],$value['value']);
                        }
                    }
                });
                break;
            case 'and':
                $query->where(function ($query)use ($rules){
                    foreach ($rules['rules'] as $key => $value){
                        switch ($value['op']){
                            case 'in':
                                if($value['value'] == null){
                                    $condition[] = $query->whereRaw("{$value['field']} in (null)");
                                }else{
                                    $condition[] = $query->whereIn($value['field'],$value['value']);
                                }
                                break;
                            case '>':
                            case '>=':
                            case '<':
                            case '<=':
                            case 'like':
                                $condition[] = $query->Where($value['field'],$value['op'],$value['value']);
                        }
                    }
                });
                break;
            default:
                break;
        }
//        $sub_query = "select u.id from projects as p left join module_users as mu on mu.moduleable_id = p.id and mu.moduleable_type=".ModuleableType::PROJECT." left join users as u on u.id = mu.user_id";
//        拼接默认搜索条件
        return $query;
    }
    public function getManageDataCondition()
    {
        $rules = (new ScopeRepository())->checkMangePower();
    }
}