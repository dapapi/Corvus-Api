<?php

namespace App\Http\Controllers;

use App\Http\Middleware\DataAuth\DataView;
use App\Http\Transformers\UserTransformer;
use App\Http\Transformers\GroupRolesTransformer;
use App\Http\Transformers\RoleTransformer;
use App\Http\Transformers\DataDictionarieTransformer;
use App\Http\Transformers\RoleUserTransformer;
use App\Events\OperateLogEvent;
use App\Models\Blogger;
use App\Models\Calendar;
use App\Models\Client;
use App\Models\Contract;
use App\Models\Department;
use App\Models\Project;
use App\Models\Role;
use App\Models\RoleUser;
use App\Models\DataDictionarie;
use App\Models\Star;
use App\Models\Task;
use App\Models\Trail;
use App\Repositories\ScopeRepository;
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
        $groupInfo = GroupRoles::orderBy('created_at')->get();
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
            'group_id' => hashid_decode($payload['group_id']),
            'name' => $payload['name'],
            'description' => isset($payload['description']) ? $payload['description'] : '',
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
                'group_id' => hashid_decode($payload['group_id']),
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
                'group_id' => hashid_decode($payload['group_id']),
                'name' => $payload['name'],
                'description' => isset($payload['description']) ? $payload['description'] : '',
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


    public function rolePerson(Request $request,Role $role)
    {
        $payload = $request->all();
        $roleId = $role->id;
        
        $roleInfo = RoleUser::where('role_id',$roleId)->get();
        return $this->response->collection($roleInfo, new RoleUserTransformer());
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
                        'user_id'=> hashid_decode($value)
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

    public function feature(Request $request,DataDictionarie $dataDictionarie,User $user,Role $role)
    {

        $roleId = $role->id;

        $depatments = DataDictionarie::where('parent_id', 1)->get();//获取功能模块列表
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
                    if($datum['id'] === $role['resouce_id']){
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
        $dataDictionarie = DataDictionarie::where('code', 1)->get()->toArray();//获取功能模块列表
        $roleId = $role->id;
        $tree_data = array();
        $res = array();
        foreach ($dataDictionarie as $key=>$value){

            //本人相关 本部门 部门下属 全部
            $reoureInfo = RoleResourceView::where('resource_id', $value['id'])->orderBy('sort_number')->get()->toArray();//获取查看功能的列表
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

    public function scopeStore(Request $request,Role $role,RoleResourceView $roleResourceView)
    {
        $payload = $request->all();
        $roleId = $role->id;
        if(!empty($payload)){
//            $dataViewSql = "{\"rules\": [{\"field\" : \"creator_id\", \"op\" : \"in\", \"value\" : \"{user_ids}\"}, {\"field\" : \"principal_id\", \"op\" : \"in\", \"value\" : \"{user_ids}\"}], \"op\" : \"or\"}";
            DB::beginTransaction();
            try {
                //首先清空角色对应的数据管理权限和数据查看权限
                RoleDataView::where('role_id',$roleId)->delete();
                RoleDataManage::where('role_id',$roleId)->delete();
                foreach($payload as $key=>$value){
                    if (is_array($value)) {
                        //删除原先的查看数据的权限
//                        $sum = RoleDataView::where('role_id',$roleId)->where('resource_id',$value['resource_id'])->delete();
                        $dataViewSql = RoleDataView::DATA_VIEW_SQL;
                        if(DataDictionarie::STAR == $value['resource_id']){ //如果是模块是艺人增加对应的搜索条件
                            $dataViewSql = RoleDataView::STAR_DATA_VIEW_SQL;
                        }
                        if(DataDictionarie::BLOGGER ==  $value['resource_id']){//如果是模块是博主增加对应的搜索条件
                            $dataViewSql = RoleDataView::BLOGGER_DATA_VIEW_SQL;
                        }
                        if(DataDictionarie::TASK ==  $value['resource_id']){//如果是模块是任务增加对应的搜索条件
                            $dataViewSql = RoleDataView::TASK_DATA_VIEW_SQL;
                        }

                        $array = [
                            'role_id'=>$roleId,
                            'resource_id'=>$value['resource_id'],
                            'data_view_id'=>$value['scope'],
                            'data_view_sql'=>$dataViewSql,
                        ];
                         $deparInfo = RoleDataView::insert($array);
                        //创建 参与 所见 删除再添加  删除原先的数据管理权限
//                        $info = RoleDataManage::where('role_id',$roleId)->where('resource_id',$value['resource_id'])->delete();
                        if(!empty($value['manage'])){
                            foreach ($value['manage'] as $mkey=>$mvalue){
                                $array = [
                                    'role_id'=>$roleId,
                                    'resource_id'=>$value['resource_id'],
                                    'data_manage_id'=>$mvalue,
                                ];
                                $depar = RoleDataManage::insert($array);
                            }
                        }
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
                DB::commit();
            } catch (Exception $e) {
                DB::rollBack();
                Log::error($e);
                return $this->response->errorInternal('创建失败');
            }
            return $this->response->accepted();
//
        }else{
            return $this->response->errorInternal('数据提交错误');
        }
    }


    /**
     * 检查权限
     */
    public function checkPower(Request $request)
    {
        $method = $request->get('method');
        $uri = $request->get('uri');
        $uri = preg_replace('/\\d+/', '{id}', $uri);
        $id = $request->get('id');
        //根据method和uri查询对应的模块
        $model_id = DataDictionarie::where('code',$method)->where('val',$uri)->first()->parent_id;
        if($model_id){//模块不存在
            return true;
        }

        $model = null;
        if($request->has("id")){
            $id = hashid_encode($id);
            if (DataDictionarie::BLOGGER == $model_id){//博主
                try {

                    $model = Blogger::findOrFail($id);
                } catch (Exception $exception) {
                    abort(404);
                }
            }elseif(DataDictionarie::PROJECT == $model_id){//项目
                try {

                    $model = Project::findOrFail($id);
                } catch (Exception $exception) {
                    abort(404);
                }
            }elseif(DataDictionarie::STAR == $model_id){//艺人
                try {

                    $model = Star::findOrFail($id);
                } catch (Exception $exception) {
                    abort(404);
                }
            }elseif(DataDictionarie::CLIENT == $model_id){//客户
                try {

                    $model = Client::findOrFail($id);
                } catch (Exception $exception) {
                    abort(404);
                }
            }elseif(DataDictionarie::TRAIL == $model_id){//销售线索
                try {

                    $model = Trail::findOrFail($id);
                } catch (Exception $exception) {
                    abort(404);
                }
            }elseif(DataDictionarie::TASK == $model_id){//任务
                try {

                    $model = Task::findOrFail($id);
                } catch (Exception $exception) {
                    abort(404);
                }
            }elseif(DataDictionarie::CONTRACTS == $model_id){//合同
                try {

                    $model = Contract::findOrFail($id);
                } catch (Exception $exception) {
                    abort(404);
                }
            }elseif(DataDictionarie::CALENDAR == $model_id) {//日历
                try {

                    $model = Calendar::findOrFail($id);
                } catch (Exception $exception) {
                    abort(404);
                }
            }
//            }elseif(DataDictionarie::CALENDAR == $model_id){//审批暂时不清楚
//                try {
//
//                    $model = Appr::findOrFail($id);
//                } catch (Exception $exception) {
//                    abort(404);
//                }
//            }
        }
        $user = Auth::guard('api')->user();
        $userId = $user->id;
        //获取用户角色
        $role_ids = array_column(RoleUser::where('user_id',$userId)->select('role_id')->get()->toArray(),'role_id');

        (new ScopeRepository())->checkPower($uri,$method,$role_ids,$model);

    }
    //返回用户有哪些模块的功能权限
    public function getPowerModel()
    {
        $user = Auth::guard('api')->user();
        $userId = $user->id;
        $role_ids = array_column(RoleUser::where('user_id',$userId)->select('role_id')->get()->toArray(),'role_id');
        $result = RoleResource::leftJoin('data_dictionaries as dd',function ($join){
            $join->on('dd.id','role_resources.resouce_id');
        })->leftJoin('data_dictionaries as ddd','ddd.id','dd.parent_id')
            ->select('ddd.*')
            ->whereIn('role_id',$role_ids)->groupBy('ddd.id')->get()->toArray();

        return $result;
    }

    public function directorList(Request $request)
    {
        $payload = $request->all();

        $pageSize = $request->get('page_size', config('app.page_size'));
        $data = DB::table('department_principal as dp')
            ->join('users', function ($join) {
                $join->on('users.id', '=','dp.user_id');
            })
            ->groupBy('users.id')
            ->select('users.name','users.icon_url','users.phone','users.email')
            ->paginate($pageSize)->toArray();

        return $data;
    }
}
