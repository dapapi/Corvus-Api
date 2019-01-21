<?php

namespace App\Http\Controllers;

use App\Http\Transformers\UserTransformer;
use App\Http\Transformers\GroupRolesTransformer;
use App\Http\Transformers\RoleTransformer;
use App\Http\Transformers\DataDictionarieTransformer;

use App\Events\OperateLogEvent;
use App\Models\Department;
use App\Models\DepartmentUser;
use App\Models\Role;
use App\Models\RoleUser;
use App\Models\DataDictionarie;
use App\User;
use App\Models\GroupRoles;
use App\Models\RoleResource;
use App\Models\RoleResourceView;
use App\Models\RoleResourceManage;
use App\Models\RoleDataView;
use App\Models\RoleDataManage;
use App\Models\OperateEntity;
use App\OperateLogMethod;
use App\Http\Requests\RoleRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;


class ScopeController extends Controller
{

    //数据字典中parent_id查看数据范围
    //根据19-本人相关 20-本部门  21-本人及下属部门 22-全部 替换userIds
    //public function index(Request $request,User $user,Role $role,DataDictionarie $dataDictionarie,Department $department)
    public function index($userId,$operation)
    {
        $userId = $userId->id;
//        $userId = hashid_decode($userId);
        //获取用户角色列表
        $roleIdList = RoleUser::where('user_id', $userId)->get()->toArray();
//        $operationId = hashid_decode($operation);
        $operationId = $operation->id;
        //根据子级模块id 查询父级模块id
        $resourceId = DataDictionarie::where('id',$operationId)->first()->parent_id;
        $arrviewSql = array();
        //角色id 列表
        foreach ($roleIdList as $value){
            $arrRoleId[] = $value['role_id'];
        }
        //根据roleid数组查找所有对应的模块id取最大值
        $viewSql = RoleDataView::whereIn('role_id',$arrRoleId)->where('resource_id',$resourceId)->get()->toArray();
        $dataDictionarie = [];
        foreach ($viewSql as $value){
            $dataDictionarie[] = $value['data_view_id'];

        }
        $dataDictionarieId = max($dataDictionarie);
        if(!empty($viewSql)){
            //查询本人相关 19
            if($dataDictionarieId == 19){
                $dataArr = json_decode($viewSql[0]['data_view_sql'],true);
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
                $departmentIdArr = DepartmentUser::where('user_id',$userId)->where('type',0)->get()->toArray();
                $departmentId = $departmentIdArr[0]['department_id'];
                $result = $this->getSubdivision($departmentId);
                $arrayUserid = array_keys($result);//查看下属部门

            }elseif($dataDictionarieId == 22){//全部
                return $array=['rules'=>array(array('field'=>'created_id','op'=>'in','value'=>''),array('field'=>'principal_id','op'=>'in','value'=>''),'op'=>'or')];;
            }
            //我创建的,我负责的 $arrayUserid能查看的用户列表
//            $array = ['rules'=>array(array('field'=>'created_id','op'=>'in','value'=>$arrayUserid),array('field'=>'principal_id','op'=>'in','value'=>$arrayUserid),'op'=>'or')];

            $dataViewSql = RoleDataView::where('resource_id',$resourceId)->where('data_view_id',$dataDictionarieId)->get()->toArray();

            if(!empty($dataViewSql)){

                $dataArr = json_decode($dataViewSql[0]['data_view_sql'],true);
                $resArr = array();
               foreach ($dataArr as $value){
                   if(is_array($value))
                   {
                   $resArr[0]['field']=$value[0]['field'];
                   $resArr[0]['op']=$value[0]['op'];
                   $resArr[0]['value']=$arrayUserid;

                   $resArr[1]['field']=$value[0]['field'];
                   $resArr[1]['op']=$value[0]['op'];
                   $resArr[1]['value']=$arrayUserid;
                   }

               }
            }else{
                return $this->response->errorInternal('没有查询到该类型数据');
            }

            $res = json_encode($resArr);
//            $num = DB::table('role_data_view')
//                ->where('role_id',$roleId)
//                ->where('resource_id',$resourceId)
//                ->update(['data_view_sql'=>$res]);
        }else{
            return $this->response->errorInternal('没有查询到该类型数据');
        }

       return $resArr;
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



    //public function show(Request $request,User $user,DataDictionarie $dataDictionarie)
    public function show($userId,$operation)
    {
//        $userId = hashid_decode($userId);
        $userId = $userId->id;
        //$roleId = RoleUser::where('user_id', $userId)->first()->role_id;
        $roleIdList = RoleUser::where('user_id', $userId)->get()->toArray();
        //模块id
//        $operationId = hashid_decode($operation);
        $operationId = $operation->id;
        $resourceId = DataDictionarie::where('id',$operationId)->first()->parent_id;
//        dd($resourceId,$roleIdList,$userId);
        //dd($resourceId);
        $arrRoleId = [];
        //用户所属角色id列表
        foreach ($roleIdList as $value){
            $arrRoleId[] = $value['role_id'];
        }

        //根据roleid数组查找所有对应的模块id取最大值
        //角色对应的
        $viewSql = RoleDataView::whereIn('role_id',$arrRoleId)->where('resource_id',$resourceId)->get()->toArray();
        $dataDictionarie = [];
        foreach ($viewSql as $value){
            $dataDictionarie[] = $value['data_view_id'];
        }
        $dataDictionarieId = max($dataDictionarie);
       //根据模块最大的值 查询role_id
        $roleIdInfo = RoleDataManage::where('data_manage_id',$dataDictionarieId)->where('resource_id',$resourceId)->get()->toArray();

        $manageInfo = RoleDataManage::where('role_id', $roleIdInfo[0]['role_id'])->where('resource_id', $resourceId)->get()->toArray();

       // $dataDictionarieId = $dataDictionarie->id;
        //$dataDictionarieId = 21;
        //$dataDictionarieId = 21;
        $manageSql = RoleDataManage::where('role_id',$roleIdInfo[0]['role_id'])->where('resource_id',$resourceId)->get()->toArray();

        if(!empty($manageSql)){
            //查询本人相关 19
            if($dataDictionarieId == 19){
                $arrayUserid = array();
                $arrayUserid[] = $userId;

                //查询本部门 20
            }elseif($dataDictionarieId == 20){
                $arrayUserid = array();
                $departmentIdArr = DepartmentUser::where('user_id',$userId)->get()->toArray();
                $departmentId = $departmentIdArr[0]['department_id'];

                $departmentUserArr = DepartmentUser::where('department_id',$departmentId)->get()->toArray();
                foreach ($departmentUserArr as $key=>$value){
                    $arrayUserid[] = $value['user_id'];
                }

                //查询本部门及下属部门 21
            }elseif($dataDictionarieId == 21){
                //根据userid 查部门及一下部门
                $arrayUserid = array();
                $departmentIdArr = DepartmentUser::where('user_id',$userId)->where('type',0)->get()->toArray();
                $departmentId = $departmentIdArr[0]['department_id'];
                $result = $this->getSubdivision($departmentId);
                $arrayUserid = array_keys($result);
            //全部
            }elseif($dataDictionarieId == 22){
                return $array=[];
            }
            $array = ['rules'=>array(array('field'=>'created_id','op'=>'in','value'=>$arrayUserid),array('field'=>'principal_id','op'=>'in','value'=>$arrayUserid),'op'=>'or')];
            $res = json_encode($array);
            $arr['rules'] = array();

            foreach ($manageSql as $value){

                if($value['data_manage_id'] == 24){
                    $arr['rules'][] = array(array('field'=>'charge_id','op'=>'in','value'=>$arrayUserid),'op'=>'or');

                }elseif($value['data_manage_id'] == 25){
                    $arr['rules'][] = array(array('field'=>'created_id','op'=>'in','value'=>$arrayUserid),'op'=>'or');

                }elseif ($value['data_manage_id'] == 26){
                    $arr['rules'][] = array(array('field'=>'participated_id','op'=>'in','value'=>$arrayUserid),'op'=>'or');

                }else{
                    $arr['rules'][] = array(array('field'=>'visual_by','op'=>'in','value'=>$arrayUserid),'op'=>'or');

                }
            }

        }else{

            return $this->response->errorInternal('没有查询到该类型数据');

        }
        return $array;

    }

}
