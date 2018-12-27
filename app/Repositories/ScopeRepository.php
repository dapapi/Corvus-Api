<?php

namespace App\Repositories;

use App\Models\DepartmentUser;
use App\Models\RoleUser;
use App\Models\DataDictionarie;

use App\Models\RoleDataView;
use App\Models\RoleDataManage;

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;


class ScopeRepository
{

    //数据字典中parent_id查看数据范围
    //根据19-本人相关 20-本部门  21-本人及下属部门 22-全部 替换userIds
    public function getDataViewUsers(){
        $user = Auth::guard('api')->user();
        $userId = $user->id;
        $path = request()->path();
        $operation = preg_replace('/\\d+/', '{id}', $path);
        $method = request()->getMethod();
        return $this->getUserIds($userId,"/".$operation,$method);
    }

    /**
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

        //根据子级模块id 查询父级模块id
        $resource = DataDictionarie::where([['val',$operation],['code',$method]])->select('parent_id')->first();
        if($resource == null){
            return null;
        }
        $resourceId = $resource->parent_id;
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
        $arrayUserid = null;
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

            }
        }
        return false;

    }

}
