<?php

namespace App\Http\Middleware\DataAuth;

use App\Exceptions\NoRoleException;
use App\Models\DataDictionarie;
use App\Models\RoleDataView;
use App\Models\RoleResourceView;
use App\Models\RoleUser;
use Closure;
use http\Env\Request;
use Illuminate\Support\Facades\Auth;


class DataView
{
    /**
     * Handle an incoming request.
     *判断用户有没有查看数据的权限
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle($request, Closure $next)
    {
        $method = request()->getMethod();
        if($method == "GET"){//只拦截get方法
            $user = Auth::guard('api')->user();
            $userId = $user->id;
            $path = request()->path();
            $operation = preg_replace('/\\d+/', '{id}', $path);
            //根据子级模块id 查询父级模块id
            $resource = DataDictionarie::where([['val',"/".$operation],['code',$method]])->select('parent_id')->first();
            if($resource == null){ //访问url在数据字典不存在则不限制权限
                return $next($request);
//                throw new NoRoleException("你访问的模块不存在");
            }
            $resourceId = $resource->parent_id;
            //检查访问模块是否受数据权限查看范围限制，只限制配置了查看范围的模块
            $res = RoleResourceView::where('resource_id',$resourceId)->first();
            if($res != null){
                //获取用户角色列表
                $roleIdList = RoleUser::where('user_id', $userId)->get()->toArray();
                if(count($roleIdList) == 0){//用户没有角色
                    throw new NoRoleException("管理员尚未给你分配角色，请联系管理员");
                }
                $roleIdList = array_column($roleIdList,'role_id');
                //用户和角色是多对多的关系，所以可能一个用户对同一个模块有多重权限
                $viewSql = RoleDataView::select('data_view_id')->whereIn('role_id',$roleIdList)->where('resource_id',$resourceId)->get()->toArray();
                if(count($viewSql) == 0){//没有对应模块的权限记录，则不进行权限控制
                    return $next($request);
                }
                $preg = "/{.*}/";
                $uri = $request->route()->uri;
                if(preg_match($preg,$uri,$model)){
                    $model = $model[0];
                    $model = trim($model,"{");
                    $model = trim($model,"}");
                    $model = $request->$model;
                    $this->checkDataViewPower($model);//检查用户对数据权限
                }
            }

        }
        return $next($request);
    }
    public function checkDataViewPower($model)
    {
        $model = $model->searchData()->find($model->id);
        if($model == null){
            throw new NoRoleException("你没有查看该数据的权限");
        }
    }
}
