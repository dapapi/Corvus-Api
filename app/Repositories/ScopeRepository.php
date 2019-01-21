<?php

namespace App\Repositories;

use App\Exceptions\NoFeatureInfoException;
use App\Exceptions\NoRoleException;
use App\Models\Department;
use App\Models\DepartmentUser;
use App\Models\RoleResource;
use App\Models\RoleResourceManage;
use App\Models\RoleResourceView;
use App\Models\RoleUser;
use App\Models\DataDictionarie;

use App\Models\RoleDataView;
use App\Models\RoleDataManage;

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;


class ScopeRepository
{

    //数据字典中parent_id查看数据范围
    //根据19-本人相关 20-本部门  21-本人及下属部门 22-全部 替换userIds
    public function getDataViewUsers($model_dic_id=null,bool $arr=false){
        $user = Auth::guard('api')->user();
        $userId = $user->id;
//        $path = request()->path();
//        $operation = preg_replace('/\\d+/', '{id}', $path);
        $method = request()->getMethod();
//        return $this->getUserIds($userId,"/".$operation,$method,$arr);
        return $this->getUserIds($userId,$model_dic_id,$method,$arr);
    }

    /**
     * 获取查询权限的规则
     * @param $userId
     * @param $operation
     * @param $method
     * @return array|null 返回空数组表示能查看所有数据  返回null表示不能查看任何数据  返回不为空的userid数组表示能查看着几个用户创建的负责的数据
     */
    public function getUserIds($userId,$operation,$method,bool $arr=false)
    {

        //获取用户角色列表
        $roleIdList = RoleUser::where('user_id', $userId)->get()->toArray();
        if(count($roleIdList) == 0){//用户没有角色
            return null;
        }
        if (is_int($operation)){//如果是数字传入的直接是模块id
            $resourceId = $operation;
        }else{
//            根据子级模块id 查询父级模块id
            $resource = DataDictionarie::where([['val',$operation],['code',$method]])->select('parent_id')->first();
            if($resource == null){
                return null;
            }
            $resourceId = $resource->parent_id;
        }

        $arrviewSql = array();
        //角色id 列表
        foreach ($roleIdList as $value){
            $arrRoleId[] = $value['role_id'];
        }


        //根据roleid数组查找所有对应的模块权限，取最大值,用户和角色是一对多的
        $viewSql = RoleDataView::select('data_view_id')->whereIn('role_id',$arrRoleId)->where('resource_id',$resourceId)->get()->toArray();
        if(count($viewSql) == 0){//没有对应模块的权限
            return null;
        }
        $dataDictionarieId = max(array_column($viewSql,'data_view_id'));//获取用户对要访问的模块最大的权限
        $dataViewSql = RoleDataView::where('resource_id',$resourceId)->where('data_view_id',$dataDictionarieId)->get()->toArray();
        //查询本人相关 19
        if($dataDictionarieId == 19){
//                $dataArr = json_decode($viewSql[0]['data_view_sql'],true);
            $arrayUserid = array();
            $arrayUserid[] = $userId; //只能查看自己

        //查询本部门 20
        }elseif($dataDictionarieId == 20){
            $arrayUserid = array();
            $departmentIdArr = DepartmentUser::where('user_id',$userId)->get()->toArray();
            $departmentId = $departmentIdArr[0]['department_id'];

            $departmentUserArr = DepartmentUser::where('department_id',$departmentId)->get()->toArray();
            foreach ($departmentUserArr as $key=>$value){
                $arrayUserid[] = $value['user_id'];//查看本部门下的所有人的
            }

        //查询本部门及下属部门 21
        }elseif($dataDictionarieId == 21){
            //根据userid 查部门及一下部门
            $arrayUserid = array();
            $departmentIdArr = DepartmentUser::where('user_id',$userId)->get()->toArray();

            $departmentId = $departmentIdArr[0]['department_id'];
            $result = $this->getSubdivision($departmentId);
            $arrayUserid = array_keys($result);//查看下属部门
        }elseif($dataDictionarieId == 22){//全部
            $arrayUserid = [];
            return $arrayUserid;
        }elseif($dataDictionarieId == 417){//本部门及同级部门
            //获取本部门id
            $departmentId = DepartmentUser::where('user_id',$userId)->first()->department_id;
            $arrayUserid = $this->getMyDepartmentAndSameLevelDepartmentUserList($departmentId);
        }else{
            return null;
        }
        if($arr === true){
            return $arrayUserid;
        }
        if(!empty($dataViewSql)){
            $dataArr = json_decode($dataViewSql[0]['data_view_sql'],true);
            foreach ($dataArr as $key => &$value){
                if($key == 'rules'){
                    foreach ($value as &$val){
                        $val['value'] = $arrayUserid;
                    }
                }
            }
        }else{
            return $this->response->errorInternal('没有查询到该类型数据');
        }
        return $dataArr;

    }

    public function getSubdivision($pid)
    {
        $arr = [];
        $department = DB::table('departments')->where(['department_pid'=>$pid])->get(['id']);
        $user = DB::table('department_user')->where(['department_id'=>$pid])->get(['user_id','department_id']);
        if ($user) {
            foreach ($user as $u) {
                $arr[$u->user_id] = $u->department_id;
            }
        }
        if ($department) {
            foreach ($department as $value) {
                $tmparr = $this->getSubdivision($value->id);
                if ($tmparr) {
                    foreach ($tmparr as $key=>$v) {
                        $arr[$key] = $v;
                    }
                }
            }
        }
        return $arr;
    }
    //获取本部门及同级部门用户列表
    public function getMyDepartmentAndSameLevelDepartmentUserList($department_id)
    {
        try{
            //获取父级部门id
            $department = Department::findOrFail($department_id);
            //获取所有同级部门
            $departments = Department::where('department_pid',$department->department_pid)->get()->toArray();
            //获取同级部门id列表
            $department_ids = array_column($departments,'id');
            //获取同级部门用户列表
            $users = DepartmentUser::whereIn('department_user.department_id',$department_ids)->leftJoin('users as u','u.id','department_user.user_id')->select('u.id')->get()->toArray();
            $user_ids = array_column($users,'id');
            return $user_ids;
        }catch (\Exception $e){
            Log::error($e);
            //如果失败则返回null，表示查看不了任何数据
            return null;
        }

    }

    /**
     * 判断用户是否有修改数据的权限
     * @param $creator_id 创建者
     * @param $principal_id 负责人
     */
    public function checkMangePower($creator_id,$principal_id,$participated_ids)
    {
        $user = Auth::guard('api')->user();
        $userId = $user->id;
        //获取用户角色
        $roleList = RoleUser::where('user_id',$userId)->select('role_id')->get();
        if(count($roleList->toArray()) == 0) {
            return false;
        }
        $roleList = array_column($roleList->toArray(),'role_id');
        //获取请求资源的父id
        $path = request()->path();
        $method = request()->getMethod();

        $operation = preg_replace('/\\d+/', '{id}', $path);
        //根据子级模块id 查询父级模块id
        $resource = DataDictionarie::where([['val',"/".$operation],['code',$method]])->select('parent_id')->first();
        if($resource == null) {//如果请求资源不存在
            return false;
        }
        $resourceId = $resource->parent_id;
        //获取用户管理数据范围
        $manageSql = RoleDataManage::whereIn('role_id',$roleList)->where('resource_id',$resourceId)->get()->toArray();
        foreach ($manageSql as $value){

            if($value['data_manage_id'] == 24){//我负责的
                if($userId == $principal_id) {
                    return true;
                }

            }elseif($value['data_manage_id'] == 25){//我创建的
                if($userId == $creator_id){
                    return true;
                }

            }elseif ($value['data_manage_id'] == 26){//我参与的

                if(in_array($userId,$participated_ids)) {
                    return true;
                }

            }elseif($value['data_manage_id'] == 27){//27 我可见的
                $arrUserId = $this->getUserIds($userId,"/".$operation,$method);//获取有查看权限的用户
                if($arrUserId != null && (in_array($userId,$arrUserId) || count($arrUserId) == 0)){
                    return true;
                }

            }else{
                return false;
            }
        }
        return false;

    }

    /**
     * 判断用户对某接口是否有权限
     * @param $api
     * @param $uri
     * @param $method
     * @param $role_id 角色数组
     * @param $user_id
     */
    public function checkPower($uri,$method,$role_ids,$model=null)
    {
        //1.获取接口在数据字典中的id
        $resource= DataDictionarie::where('val', '/'.$uri)->where('code', $method)->first();//检查数据字典里是否配置了该权限，没有则放过该请求
        if($resource != null){//请求地址在数据字典不存在则不进行权限控制
            $model_id = $resource->parent_id;
            //2.检查功能权限
            $featureInfo = RoleResource::whereIn('role_id', $role_ids)->where('resouce_id', $resource->id)->get()->toArray();
//            dd($featureInfo);
            if(count($featureInfo) == 0){//如果为空则表示没有权限
                if($method == "GET"){
                    return [];
                }
                throw new NoFeatureInfoException("你没有访问{$resource->name}功能权限");
            }
            //如果是get请求则检查role_data_view表中是检查用户对该接口的权限
            if($method == "GET"){

                //检查访问模块是否在role_resource_view表中，只限制配置了查看范围的模块
                $res = RoleResourceView::where('resource_id',$model_id)->first();
                if($res != null){//检查访问模块是否在role_resource_view表中，则进行权限限制
                    //检查role_data_view表中的权限
                    //用户和角色是多对多的关系，所以可能一个用户对同一个模块有多重权限
                    $viewSql = RoleDataView::whereIn('role_id',$role_ids)->where('resource_id',$model_id)->get()->toArray();
                    if(count($viewSql) != 0){//没有对应模块的权限记录，则不进行权限控制
                        //如果接口中传进了模型，则对模型进行权限控制
                        if($model != null){

                            if(!$this->checkDataViewPower($model)){//检查用户对数据权限
                                throw new NoRoleException("你没有查看{$resource->name}的权限");
                            }
                        }
                    }else{
                        throw new NoRoleException("你没有查看{$resource->name}的权限！");
                    }

                }
            }
            //如果method不是get
            if($method != "GET"){
                $res = RoleResourceManage::where('resource_id',$model_id)->first();
                if($res != null){ //数据管理权限列表中没有该模块权限放过
                    if($model != null){//放过了没有molde的数据例如新增
                        //获取角色管理数据范围
                        $manageSql = RoleDataManage::whereIn('role_id',$role_ids)->where('resource_id',$model_id)->get()->toArray();
                        if(count($manageSql) == 0){//如果权限管理表中没有记录不进行权限控制
                            throw new NoRoleException("你没有操作{$resource->name}的权限");
                        }

                        $user = Auth::guard("api")->user();
                        if(!$this->checkDataManagePower($user->id,$manageSql,$uri,$model)){//检查用户对数据权限
                            throw new NoRoleException("你没有操作{$resource->name}的权限！");
                        }
                    }
                }


            }

        }

    }
    private function checkDataViewPower($model)
    {
        DB::connection()->enableQueryLog();
        $model = $model->searchData()->find($model->id);
        dd(DB::getQueryLog());
        if($model == null){
            return false;
        }
        return true;
    }
    /**
     * 检查数据权限
     */
    public function checkDataManagePower($user_id,$manageSql,$uri,$model)
    {
        foreach ($manageSql as $value){
            if($value['data_manage_id'] == 24){//我负责的
                if($user_id == $model->principal_id) {
                    return true;
                }

            }elseif($value['data_manage_id'] == 25){//我创建的
                if($user_id == $model->creator_id){
                    return true;
                }

            }elseif ($value['data_manage_id'] == 26){//我参与的
                //获取该项目对应的参与人
                $res = $model->participants()->get();
                $participated_ids = array_column($res->toArray(),'id');
                if(in_array($user_id,$participated_ids)) {
                    return true;
                }

            }elseif($value['data_manage_id'] == 27){//27 我可见的
                $arrUserId = (new ScopeRepository())->getUserIds($user_id,"/".$uri,\request()->method(),true);//获取有查看权限的用户
                //$arrUserId为空数组表示全部数据可见，所以可以操作全部数据
                if(($arrUserId != null && (in_array($user_id,$arrUserId)) || count($arrUserId) == 0)){
                    return true;
                }

            }
        }
        return false;
    }
    //
}
