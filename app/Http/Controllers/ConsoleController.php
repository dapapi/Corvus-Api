<?php

namespace App\Http\Controllers;

use App\Http\Transformers\UserTransformer;
use App\Http\Transformers\GroupRolesTransformer;
use App\Http\Transformers\RoleTransformer;
use App\Http\Transformers\DataDictionarieTransformer;

use App\Events\OperateLogEvent;
use App\Models\Department;
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


class ConsoleController extends Controller
{
    public function index(Request $request)
    {
        $users = User::orderBy('name')->get();

        return $this->response->collection($users, new UserTransformer());
    }

    public function my(Request $request)
    {
        $user = Auth::guard('api')->user();

        return $this->response->item($user, new UserTransformer());
    }

    private function department(Department $department)
    {
        $department = $department->pDepartment;
        if ($department->department_pid == 0) {
            return $department;
        } else {
            $this->department($department);
        }
    }

    public function getGroup(Request $request)
    {
        $groupInfo = GroupRoles::orderBy('name')->get();
        return $this->response->collection($groupInfo, new GroupRolesTransformer());
    }

    public function storeGroup(Request $request,groupRoles $groupRoles,User $user)
    {
        $payload = $request->all();
        $array = [
            'name' => $payload['name'],
        ];
        try {
            $groupRoles->create($array);
//            // 操作日志
//            $operate = new OperateEntity([
//                'obj' => $groupRoles,
//                'title' => null,
//                'start' => null,
//                'end' => null,
//                'method' => OperateLogMethod::CREATE,
//            ]);
//            event(new OperateLogEvent([
//                $operate,
//            ]));
        } catch (\Exception $exception) {
            Log::error($exception);
            return $this->response->errorInternal('修改失败');
        }
        return $this->response->accepted();
    }

    public function editGroup(Request $request,GroupRoles $groupRoles,User $user)
    {
        $payload = $request->all();
        try {
            $operate = new OperateEntity([
                'obj' => $groupRoles,
                'title' => null,
                'start' => $groupRoles->name,
                'end' => $payload['name'],
                'method' => OperateLogMethod::UPDATE,
            ]);
            event(new OperateLogEvent([
                $operate,
            ]));
            $array = [
                'name' => $payload['name'],
            ];
            $groupRoles->update($array);

        } catch (\Exception $exception) {
            Log::error($exception);
            return $this->response->errorInternal('修改失败');
        }
        return $this->response->accepted();
    }

    public function deleteGroup(Request $request,GroupRoles $groupRoles)
    {
        $groupRoles->delete();
        return $this->response->noContent();
    }

    /*后台权限 角色 控制台*/
    public function getRole(Request $request)
    {
        $roleInfo = Role::orderBy('name')->get();

        return $this->response->collection($roleInfo, new RoleTransformer());
    }


    public function storeRole(RoleRequest $roleRequest,Role $role)
    {
        $payload = $roleRequest->all();
        $array = [
            'group_id' => $payload['group_id'],
            'name' => $payload['name'],
        ];
        try {
            $role->create($array);
//            // 操作日志
//            $operate = new OperateEntity([
//                'obj' => $role,
//                'title' => null,
//                'start' => null,
//                'end' => null,
//                'method' => OperateLogMethod::CREATE,
//            ]);
//            event(new OperateLogEvent([
//                $operate,
//            ]));
        } catch (\Exception $exception) {
            Log::error($exception);
            return $this->response->errorInternal('修改失败');
        }
        return $this->response->accepted();
    }


    public function mobileRole(Request $request,Role $role)
    {
        $payload = $request->all();
        if(isset($payload['group_id'])){
            $array = [
                'group_id' => $payload['group_id'],
            ];
            try {
                $role->update($array);
    //            // 操作日志
                $operate = new OperateEntity([
                    'obj' => $role,
                    'title' => $role->group_id,
                    'start' => $payload['group_id'],
                    'end' => null,
                    'method' => OperateLogMethod::CREATE,
                ]);
                event(new OperateLogEvent([
                    $operate,
                ]));
            } catch (\Exception $exception) {
                Log::error($exception);
                return $this->response->errorInternal('修改失败');
            }
        }else{
            return $this->response->errorInternal('分组ID错误');
        }
        return $this->response->accepted();
    }

    public function editRole(RoleRequest $roleRequest,Role $role)
    {
        $payload = $roleRequest->all();
        try {
            $operate = new OperateEntity([
                'obj' => $role,
                'title' => null,
                'start' => $role->name,
                'end' => $role['name'],
                'method' => OperateLogMethod::UPDATE,
            ]);
            event(new OperateLogEvent([
                $operate,
            ]));
            $array = [
                'group_id' => $payload['group_id'],
                'name' => $payload['name'],
            ];
            $role->update($array);

        } catch (\Exception $exception) {
            Log::error($exception);
            return $this->response->errorInternal('修改失败');
        }
        return $this->response->accepted();
    }

    public function deleteRole(Request $request,Role $role)
    {
        $role->delete();
        return $this->response->noContent();
    }


    public function groupPerson(Request $request,GroupRoles $groupRoles)
    {
        $payload = $request->all();
        $group_id = $groupRoles->id;
        $groupInfo = Role::where('group_id',$group_id)->get();

        return $this->response->collection($groupInfo, new RoleTransformer());
    }

    public function setRoleUser(Request $request,Role $role,RoleUser $roleUser)
    {
        $payload = $request->all();
        $role_id = $role->id;
        if(!empty($payload)){
            //删除所有角色ID信息 然后在添加关联数据
            RoleUser::where('role_id',$role_id)->delete();
            try {
                foreach($payload['user'] as $key=>$value){
                    $array = [
                        'role_id'=> $role_id,
                        'user_id'=> $value
                    ];
                    $roleUser->create($array);
                }
            } catch (\Exception $exception) {
                Log::error($exception);
                return $this->response->errorInternal('修改失败');
            }
            return $this->response->accepted();
        }else{
            return $this->response->errorBadRequest('用户类型是数组格式');
        }
    }

    public function feature(Request $request,DataDictionarie $dataDictionarie,User $user)
    {
        $userid = $user->id;
        $roleId = RoleUser::where('user_id', $userid)->first()->role_id;
        $depatments = DataDictionarie::where('parent_id', 1)->get();
        $roleInfo = RoleResource::where('role_id', $roleId)->get()->toArray();

        $tree_data=array();
        foreach ($depatments as $key=>$value){
            $tree_data[$value['id']]=array(
                'id'=>$value['id'],
                'parentid'=>$value['parent_id'],
                'name'=>$value['name'],
                'data'=>DataDictionarie::where('parent_id', $value['id'])->get()->toArray()
            );
            foreach($tree_data[$value['id']]['data'] as $k=>&$datum){
                foreach ($roleInfo as $rkey=>$role){
                    if($datum['id'] == $role['resouce_id']){
                        $datum['selected'] = true;
                    }else{
                        $datum['selected'] = false;
                    }
                    if($datum['selected'] == true) {
                        break;
                    }
                }
            }
        }
        return $tree_data;
 //     return $this->response->collection($depatments, new DataDictionarieTransformer());
    }

    public function featureRole(Request $request,Role $role,RoleUser $roleUser,RoleResource $roleResource)
    {
        $payload = $request->all();
        $role_id = $role->id;
        if(!empty($payload)){
            //删除所有角色ID信息 然后在添加关联数据
            RoleResource::where('role_id',$role_id)->delete();
            try {
                foreach($payload['resouce'] as $key=>$value){
                    $array = [
                        'role_id'=>$role_id,
                        'resouce_id'=>(int)$value
                    ];
                    $roleResource->create($array);
                }
            } catch (\Exception $exception) {
                Log::error($exception);
                return $this->response->errorInternal('修改失败');
            }
            return $this->response->accepted();
        }else{
            return $this->response->errorBadRequest('用户类型是数组格式');
        }
    }

    public function scope(Request $request,Role $role,RoleUser $roleUser,RoleResource $roleResource,User $user,RoleDataView $roleDataView,RoleDataManage $roleDataManage)
    {

        $userid = $user->id;

        $roleId = RoleUser::where('user_id', $userid)->first()->role_id;

        $dataDictionarie = DataDictionarie::where('code', 1)->get()->toArray();
        $tree_data = array();
        $res = array();
        foreach ($dataDictionarie as $key=>$value){
            //本人相关 本部门 部门下属 全部
            $reoureInfo = RoleResourceView::where('resource_id', $value['id'])->get()->toArray();
           // dd($reoureInfo);
            $info = array_column($reoureInfo, 'data_view_id');
            $res = DataDictionarie::whereIn('id', $info)->get()->toArray();

            $roleManage = $roleDataView->where('role_id',$roleId)->where('resource_id',$value['id'])->get()->toArray();
            foreach ($res as $rkey=>&$rvale){

                foreach ($roleManage as $mkey=>&$mvalue){
                    if($mvalue['data_view_id'] == $rvale['id']){
                        $rvale['selected'] = true;
                    }else{
                        $rvale['selected'] = false;
                    }
                    if($rvale['selected'] == true) {
                        break;
                    }
                }
            }

            //我创建 负责  参与 可见
            $manageInfo = RoleResourceManage::where('resource_id', $value['id'])->get()->toArray();
            $minfo = array_column($manageInfo, 'data_manage_id');
            $resManage = DataDictionarie::whereIn('id', $minfo)->get()->toArray();

            $manage = $roleDataManage->where('role_id',$roleId)->where('resource_id',$value['id'])->get()->toArray();
//            //dd($roleManage);
            foreach ($resManage as $rkey=>&$rolevalue){
                foreach ($manage as $ekey=>$evalue){
                    if($rolevalue['id'] == $evalue['data_manage_id']){
                        $rolevalue['selected'] = true;
                    }else{
                        $rolevalue['selected'] = false;
                    }
                    if($rolevalue['selected'] == true) {
                        break;
                    }
                }
            }

            $tree_data[$value['id']] = array(
                'id' => $value['id'],
                'parentid' => $value['parent_id'],
                'name' => $value['name'],
                'data1' => $res,
                'data2' => $resManage,
            );

        }

        return $tree_data;
    }

    public function scopeStore(Request $request,Role $role,RoleResourceView $roleResourceView,User $user)
    {
        $payload = $request->all();
        $roleId = $role->id;
        $userId = $user->id;

        if(!empty($payload)){

            DB::beginTransaction();
            try {
                foreach($payload as $key=>$value){
                    //本人相关 本部门 部门下属 全部 直接update修改
                    $sum = RoleDataView::where('role_id',$roleId)->where('resource_id',$value['resource_id'])->update(
                        ['data_view_id' => $value['scope']]
                    );
                    //创建 参与 所见 删除再添加
                    $info = RoleDataManage::where('role_id',$roleId)->where('resource_id',$value['resource_id'])->delete();

                    foreach ($value['manage'] as $mkey=>$mvalue){

                        $array = [
                            'role_id'=>$roleId,
                            'resource_id'=>$value['resource_id'],
                            'data_manage_id'=>$mvalue,
                        ];
                        $depar = RoleDataManage::create($array);
                    }
                }
                // 操作日志
    //            $operate = new OperateEntity([
    //                'obj' => $department,
    //                'title' => null,
    //                'start' => null,
    //                'end' => null,
    //                'method' => OperateLogMethod::CREATE,
    //            ]);
    //            event(new OperateLogEvent([
    //                $operate,
    //            ]));

            } catch (Exception $e) {
                DB::rollBack();
                Log::error($e);
                return $this->response->errorInternal('创建失败');
            }
            DB::commit();
            return $this->response->accepted();
//
        }else{
            return $this->response->errorInternal('数据提交错误');
        }
    }
}
