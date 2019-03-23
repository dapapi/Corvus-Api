<?php

namespace App\Http\Controllers;

use App\Http\Middleware\DataAuth\DataView;
use App\Http\Transformers\UserTransformer;
use App\Http\Transformers\GroupRolesTransformer;
use App\Http\Transformers\SupplierTransformer;
use App\Http\Transformers\SupplierRelateTransformer;
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

    public function edit(Request $request,Supplier $supplier)
    {
        $payload = $request->all();
        $array = [];
        $arrayOperateLog = [];
        $old_star = clone $supplier;
        $user = Auth::guard('api')->user();
        if ($request->has('name') && !empty($payload['name'])) {

            $array['name'] = $payload['name'];//姓名
            if ($array['name'] != $supplier->name) {
                $operateName = new OperateEntity([
                    'obj' => $supplier,
                    'title' => '供应商名称',
                    'start' => $supplier->name,
                    'end' => $array['name'],
                    'method' => OperateLogMethod::UPDATE,
                ]);
                $arrayOperateLog[] = $operateName;
                event(new OperateLogEvent([
                    $operateName,
                ]));
            }
        }
        if ($request->has('address') && !empty($payload['address'])) {
            $array['name'] = $payload['name'];//姓名
            if ($array['name'] != $supplier->name) {
                $operateaddress = new OperateEntity([
                    'obj' => $supplier,
                    'title' => '地址',
                    'start' => $supplier->name,
                    'end' => $array['name'],
                    'method' => OperateLogMethod::UPDATE,
                ]);
                $arrayOperateLog[] = $operateaddress;
                event(new OperateLogEvent([
                    $operateaddress,
                ]));
            }
        }
        if ($request->has('level')) {//等级
            $array['level'] = $payload['level'];
            $operateAvatar = new OperateEntity([
                'obj' => $supplier,
                'title' => '等级',
                'start' => $supplier->level,
                'end' => $payload['level'],
                'method' => OperateLogMethod::UPDATE,
            ]);
            $arrayOperateLog[] = $operateAvatar;
            event(new OperateLogEvent([
                $operateAvatar,
            ]));
        }
        DB::beginTransaction();
        try {
            $supplier->update($array);
            //删除供应商关联表
            $num = DB::table("supplier_relates")->where('supplier_id',$supplier->id)->where('type',1)->delete();

            $array = array();
            foreach ($payload['currency'] as $value){
                $array['key']=$value['name'];
                $array['value']=$value['account'];
                $array['currency']=$value['coin'];
                $array['supplier_id']=$supplier->id;
                $array['type']=1;
                SupplierRelate::create($array);
            }

        } catch (Exception $e) {
            DB::rollBack();
            Log::error($e);
            return $this->response->errorInternal('创建失败');
        }
        DB::commit();

    }


    public function contactShow(Request $request,Supplier $supplier)
    {
        $payload = $request->all();
        $pageSize = $request->get('page_size', config('app.page_size'));
        $supplierRelates = SupplierRelate::where('supplier_id',$supplier->id)->where('type',2)->orderBy('updated_at')->paginate($pageSize);

        return $this->response->paginator($supplierRelates, new SupplierRelateTransformer());

    }




    public function addContact(Request $request,Supplier $supplier,SupplierRelate $supplierRelate)
    {

        $payload = $request->all();
        $phone = SupplierRelate::where('value',$payload['value'])->where('type',2)->get()->toArray();
        if(!empty($phone)){
            return $this->response->errorInternal('该手机号已存在！');
        }

        $array = [
            'key' => $payload['key'],
            'value' => $payload['value'],
            'type' => 2,
            'supplier_id' => $supplier->id,
        ];

        DB::beginTransaction();
        try {

            $supplier = SupplierRelate::create($array);
          if ($request->has('value')) {//等级
              $array['value'] = $payload['value'];
              $operateAvatar = new OperateEntity([
                  'obj' => $supplier,
                  'title' => '手机号',
                  'start' => null,
                  'end' => null,
                  'method' => OperateLogMethod::CREATE,
              ]);
              $arrayOperateLog[] = $operateAvatar;
              event(new OperateLogEvent([
                  $operateAvatar,
              ]));
          }
        } catch (Exception $e) {
            DB::rollBack();
            Log::error($e);
            return $this->response->errorInternal('操作失败');
        }
        DB::commit();
        return $this->response->accepted();
    }

    public function editContact(Request $request,SupplierRelate $supplierRelate)
    {
        $payload = $request->all();
        $array = [];
        $arrayOperateLog = [];
        $old_star = clone $supplierRelate;
        $user = Auth::guard('api')->user();
        if ($request->has('key') && !empty($payload['key'])) {

            $array['key'] = $payload['key'];//姓名
            if ($array['key'] != $supplierRelate->key) {
                $operateName = new OperateEntity([
                    'obj' => $supplierRelate,
                    'title' => '联系人',
                    'start' => $supplierRelate->key,
                    'end' => $array['key'],
                    'method' => OperateLogMethod::UPDATE,
                ]);
                $arrayOperateLog[] = $operateName;
                event(new OperateLogEvent([
                    $operateName,
                ]));

            }
        }
        if ($request->has('value') && !empty($payload['value'])) {
            $array['value'] = $payload['value'];//姓名
            if ($array['value'] != $supplierRelate->value) {
                $operateaddress = new OperateEntity([
                    'obj' => $supplierRelate,
                    'title' => '电话',
                    'start' => $supplierRelate->value,
                    'end' => $array['value'],
                    'method' => OperateLogMethod::UPDATE,
                ]);
                $arrayOperateLog[] = $operateaddress;
                event(new OperateLogEvent([
                    $operateaddress,
                ]));
            }
        }

        DB::beginTransaction();
        try {
            $supplierRelate->update($array);

        } catch (Exception $e) {
            DB::rollBack();
            Log::error($e);
            return $this->response->errorInternal('创建失败');
        }
        DB::commit();

    }

    public function removeContact(Request $request,SupplierRelate $supplierRelate){
        DB::beginTransaction();
        try {
            $user = Auth::guard('api')->user();
            if($user->id == $supplierRelate->creator_id){
                $supplierRelate->delete();
            }

            // 操作日志
            $operate = new OperateEntity([
                'obj' => $supplierRelate,
                'title' => null,
                'start' => null,
                'end' => null,
                'method' => OperateLogMethod::DELETE,
            ]);
            event(new OperateLogEvent([
                $operate,
            ]));
        } catch (Exception $e) {
            DB::rollBack();
            Log::error($e);
            return $this->response->errorInternal('删除失败');
        }
        DB::commit();
    }

}
