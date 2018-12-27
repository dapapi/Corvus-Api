<?php

namespace App\Http\Middleware\DataAuth;

use App\Exceptions\NoRoleException;
use App\Models\DataDictionarie;
use App\Models\RoleDataManage;
use App\Models\RoleResourceManage;
use App\Models\RoleUser;
use App\Repositories\ScopeRepository;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class DataManage
{
    private $role_list;
    private $module_id;
    private $manageSql;
    private $user_id;
    private $operation;
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle($request, Closure $next)
    {
        //非get即为修改数据
        if($request->method() != "GET" && $request->route()->uri != "datadic/add"){
            if($this->checkHasUri()){//检查uri在数据字典中是否存在
                $res = $this->isNeedDataManage();//检查是否需要数据权限
                if($res != null){
                    $this->checkHasRole();//检查用户角色
                    if($this->checkRolePower()) {//检查角色权限,角色有该模块的权限才进行前线控制
                        $preg = "/{.*}/";
                        $uri = $request->route()->uri;
                        if(preg_match($preg,$uri,$model)){//放过了没有携带model的访问，例如新增
                            $model = $model[0];
                            $model = trim($model,"{");
                            $model = trim($model,"}");
                            $model = $request->$model;
                            $this->checkDatPower($model);//检查用户对数据权限
                        }
                    }

                }
            }

        }


        return $next($request);
    }


    /**
     * 检查用户是否有角色
     */
    public function checkHasRole()
    {
        $user = Auth::guard('api')->user();
        $userId = $user->id;
        //获取用户角色
        $roleList = RoleUser::where('user_id',$userId)->select('role_id')->get();
        if(count($roleList->toArray()) == 0) {
            throw new NoRoleException("你没有角色，请联系管理员");
        }
        $this->role_list = array_column($roleList->toArray(),'role_id');
        $this->user_id = $userId;
    }

    /**
     * 检查用户访问的uri是否存在
     */
    public function checkHasUri()
    {
        $path = request()->path();
        $method = request()->getMethod();

        $operation = preg_replace('/\\d+/', '{id}', $path);
        //根据子级模块id 查询父级模块id
        $resource = DataDictionarie::where([['val',"/".$operation],['code',$method]])->select('parent_id')->first();
        if($resource == null) {//请求地址不在数据字典中则不限制权限
            return false;
        }
        $this->module_id = $resource->parent_id;
        $this->operation = $operation;
        return true;
    }
    /**
     * 检查是否需要数据权限验证
     */
    public function isNeedDataManage()
    {
        return RoleResourceManage::where('resource_id',$this->module_id)->first();
    }
    /**
     * 检查角色是否有对该模块的数据修改权限
     */
    public function checkRolePower()
    {
        //获取角色管理数据范围
        $manageSql = RoleDataManage::whereIn('role_id',$this->role_list)->where('resource_id',$this->module_id)->get()->toArray();
        if(count($manageSql) == 0){//如果权限管理表中没有记录不进行权限控制
            return false;
        }
        $this->manageSql = $manageSql;
        return true;
    }

    /**
     * 检查数据权限
     */
    public function checkDatPower($model)
    {
        foreach ($this->manageSql as $value){
            if($value['data_manage_id'] == 24){//我负责的
                if($this->user_id == $model->principal_id) {
                    return true;
                }

            }elseif($value['data_manage_id'] == 25){//我创建的
                if($this->user_id == $model->creator_id){
                    return true;
                }

            }elseif ($value['data_manage_id'] == 26){//我参与的
                //获取该项目对应的参与人
                $res = $model->participants()->get();
                $participated_ids = array_column($res->toArray(),'id');
                if(in_array($this->user_id,$participated_ids)) {
                    return true;
                }

            }elseif($value['data_manage_id'] == 27){//27 我可见的
                $arrUserId = (new ScopeRepository())->getUserIds($this->user_id,"/".$this->operation,\request()->method(),true);//获取有查看权限的用户
                //$arrUserId为空数组表示全部数据可见，所以可以操作全部数据
                if(($arrUserId != null && (in_array($this->user_id,$arrUserId)) || count($arrUserId) == 0)){
                    return true;
                }

            }
        }
        throw new NoRoleException("你没有操作该数据的权限!!");
    }
}
