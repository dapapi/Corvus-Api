<?php

namespace App\Http\Controllers;

use App\Http\Middleware\DataAuth\DataView;
use App\Http\Transformers\UserTransformer;
use App\Http\Transformers\GroupRolesTransformer;
use App\Http\Transformers\SupplierTransformer;
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
use App\Models\Supplier;
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
use App\Models\SupplierRelate;

use App\Models\RoleDataManage;
use App\Http\Requests\SupplierRequest;

use App\Models\OperateEntity;
use App\OperateLogMethod;
use App\Http\Requests\RoleRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;


class SupplierController extends Controller
{
    public function index(Request $request)
    {

        $payload = $request->all();
        $pageSize = $request->get('page_size', config('app.page_size'));
        $suppliers = Supplier::orderBy('created_at')->paginate($pageSize);

        return $this->response->paginator($suppliers, new SupplierTransformer());

    }

    public function detail(Request $request,Supplier $supplier)
    {
        // 操作日志
        $operate = new OperateEntity([
            'obj' => $supplier,
            'title' => null,
            'start' => null,
            'end' => null,
            'method' => OperateLogMethod::LOOK,
        ]);
        event(new OperateLogEvent([
            $operate,
        ]));

        return $this->response()->item($supplier, new SupplierTransformer());
    }



    public function store(SupplierRequest $request,Supplier $supplier)
    {
        $user = Auth::guard('api')->user();
        $userId = $user->id;
        $payload = $request->all();
        $suppliersInfo = Supplier::where('name',$payload['name'])->get()->toArray();
        if(!empty($suppliersInfo)){
            return $this->response->errorInternal('供应商名称已存在！');
        }

        $array = [
            'name' => $payload['name'],
            'create_id' => $userId,
            'address' => $payload['address'],
            'level' => $payload['level'],
        ];

        DB::beginTransaction();
        try {
            $supplier = $supplier->create($array);
            $id = DB::getPdo()->lastInsertId();

            foreach ($payload['currency'] as $value){
                $currencyArr = [
                    'key' => $value['name'],
                    'value' => $value['account'],
                    'currency' => $value['coin'],
                    'supplier_id' => $id,
                    'type' => 1,
                ];
                SupplierRelate::create($currencyArr);
            }
            $contactArr = [
                'key' => $payload['contact'],
                'value' => $payload['phone'],
                'supplier_id' => $id,
                'type' => 2,
            ];

            SupplierRelate::create($contactArr);

//            // 操作日志
            $operate = new OperateEntity([
                'obj' => $supplier,
                'title' => null,
                'start' => null,
                'end' => null,
                'method' => OperateLogMethod::CREATE,
            ]);
            event(new OperateLogEvent([
                $operate,
            ]));
        } catch (Exception $e) {
            DB::rollBack();
            Log::error($e);
            return $this->response->errorInternal('操作失败');
        }
        DB::commit();
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









}
