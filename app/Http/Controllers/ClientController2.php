<?php

namespace App\Http\Controllers;

use App\Events\OperateLogEvent;
use App\Exports\ClientsExport;
use App\Http\Requests\Client\FilterClientRequest;
use App\Http\Requests\Client\EditClientRequest;
use App\Http\Requests\Client\StoreClientRequest;
use App\Http\Requests\Excel\ExcelImportRequest;
use App\Http\Transformers\ClientTransformer;
use App\Imports\ClientsImport;
use App\Models\Client;
use App\Models\Contact;
use App\Models\Message;
use App\Models\OperateEntity;
use App\OperateLogMethod;
use App\Repositories\ScopeRepository;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Maatwebsite\Excel\Facades\Excel;

class ClientController extends Controller
{
    // todo 加日志
    public function index(Request $request)
    {
        //可查询的数据范围
        $arrUserId = (new ScopeRepository())->getDataViewUsers();
        if($arrUserId === null){
            return $this->response->errorInternal("没有查看数据的权限");
        }
        $pageSize = $request->get('page_size', config('app.page_size'));

        $clients = Client::orderBy('created_at', 'desc')
            ->where(function ($query)use ($arrUserId){
                //限制查询数据范围
                if(count($arrUserId) > 0){
                    $query->whereIn('creator_id',$arrUserId)
                        ->orWhereIn('principal_id',$arrUserId);
                }
            })
            ->paginate($pageSize);
        return $this->response->paginator($clients, new ClientTransformer());
    }

    public function all(Request $request)
    {
        //可查询的数据范围
        $arrUserId = (new ScopeRepository())->getDataViewUsers();
        if($arrUserId === null){
            return $this->response->errorInternal("没有查看数据的权限");
        }
        $isAll = $request->get('all', false);
        $clients = Client::orderBy('created_at', 'desc')
            ->where(function ($query)use($arrUserId){
                //限制查询数据范围
                if(count($arrUserId) > 0){
                    $query->whereIn('creator_id',$arrUserId)
                        ->orWhereIn('principal_id',$arrUserId);
                }
            })
            ->get();

        return $this->response->collection($clients, new ClientTransformer($isAll));
    }

    public function store(StoreClientRequest $request)
    {
        $payload = $request->all();

        $payload['principal_id'] = hashid_decode($payload['principal_id']);

        $user = Auth::guard('api')->user();
        $payload['creator_id'] = $user->id;

        DB::beginTransaction();
        try {

            $client = Client::create($payload);
            // 操作日志
            $operate = new OperateEntity([
                'obj' => $client,
                'title' => null,
                'start' => null,
                'end' => null,
                'method' => OperateLogMethod::CREATE,
            ]);
            event(new OperateLogEvent([
                $operate,
            ]));

            if ($request->has('contact')) {
                $contact = Contact::create([
                    'name' => $payload['contact']['name'],
                    'phone' => $payload['contact']['phone'],
                    'position' => $payload['contact']['position'],
                    'client_id' => $client->id,
                    'type' => $payload['contact']['type']
                ]);
                $operate = new OperateEntity([
                    'obj' => $client,
                    'title' => '该用户',
                    'start' => '联系人',
                    'end' => null,
                    'method' => OperateLogMethod::ADD_PERSON,
                ]);
                event(new OperateLogEvent([
                    $operate,
                ]));
            }
        } catch (\Exception $exception) {
            Log::error($exception);
            DB::rollBack();
            return $this->response->errorInternal('创建失败');
        }
        DB::commit();

        return $this->response->item($client, new ClientTransformer());
    }

    public function edit(EditClientRequest $request, Client $client)
    {
        //客户没有参与人
        $res = [];
        //验证权限
        $power = (new ScopeRepository())->checkMangePower($client->creator_id,$client->principal_id,$res);
        if(!$power){
            return $this->response->errorInternal("你没有编辑该客户的权限");
        }
        $payload = $request->all();

        if (array_key_exists('_url', $payload))
            unset($payload['_url']);

        $columns = DB::getDoctrineSchemaManager()->listTableDetails('clients');
        if ($request->has('principal_id'))
            $payload['principal_id'] = hashid_decode($payload['principal_id']);

        try {
            foreach ($payload as $key => $value) {
                $lastValue = $client[$key];
                $comment = $columns->getColumn($key)->getComment();
                $this->editLog($client, $comment, $lastValue, $value);
            }
            $client->update($payload);
        } catch (\Exception $exception) {
            Log::error($exception);
            return $this->response->errorInternal('修改失败');
        }
        return $this->response->accepted();
    }

    public function delete(Request $request, Client $client)
    {
        //客户没有参与人
        $res = [];
        //验证权限
        $power = (new ScopeRepository())->checkMangePower($client->creator_id,$client->principal_id,$res);
        if(!$power){
            return $this->response->errorInternal("你没有删除该客户的权限");
        }
        try {
            $client->status = Client::STATUS_FROZEN;
            $client->save();
            $client->delete();
        } catch (Exception $exception) {
            Log::error($exception);
            return $this->response->errorInternal('删除失败');
        }

        return $this->response->noContent();
    }

    public function recover(Request $request, Client $client)
    {
        //客户没有参与人
        $res = [];
        //验证权限
        $power = (new ScopeRepository())->checkMangePower($client->creator_id,$client->principal_id,$res);
        if(!$power){
            return $this->response->errorInternal("你没有恢复该客户的权限");
        }
        $client->restore();
        $client->status = Client::STATUS_NORMAL;
        $client->save();

        return $this->response->item($client, new ClientTransformer());
    }

    public function detail(Request $request, Client $client)
    {
        $arrUserId = (new ScopeRepository())->getDataViewUsers();
        if($arrUserId == null || (count($arrUserId)!=0 && !in_array($client->creator_id,$arrUserId) && !in_array($client->principal_id,$arrUserId))){
            return $this->response->errorInternal("你没有查看该客户的权限");
        }
        return $this->response->item($client, new ClientTransformer());
    }

    public function filter(FilterClientRequest $request)
    {
        //可查询的数据范围
        $arrUserId = (new ScopeRepository())->getDataViewUsers();
        if($arrUserId === null){
            return $this->response->errorInternal("没有查看数据的权限");
        }
        $payload = $request->all();
        $pageSize = $request->get('page_size', config('app.page_size'));

        $clients = Client::where(function ($query) use ($request, $payload,$arrUserId) {
            if ($request->has('keyword'))
                $query->where('company', 'LIKE', '%' . $payload['keyword'] . '%');
            if ($request->has('grade'))
                $query->where('grade', $payload['grade']);
            if ($request->has('principal_ids') && $payload['principal_ids']) {
                $payload['principal_ids'] = explode(',', $payload['principal_ids']);
                foreach ($payload['principal_ids'] as &$id) {
                    $id = hashid_decode((int)$id);
                }
                unset($id);
                $query->whereIn('principal_id', $payload['principal_ids']);
                //限制查询数据范围
                if(count($arrUserId) > 0){
                    $query->whereIn('creator_id',$arrUserId)
                        ->orWhereIn('principal_id',$arrUserId);
                }
            }
        })->orderBy('created_at', 'desc')->paginate($pageSize);

        return $this->response->paginator($clients, new ClientTransformer());
    }

    private function editLog($client, $field, $old, $new)
    {
        $operate = new OperateEntity([
            'obj' => $client,
            'title' => $field,
            'start' => $old,
            'end' => $new,
            'method' => OperateLogMethod::UPDATE,
        ]);
        event(new OperateLogEvent([
            $operate,
        ]));
    }

    public function import(ExcelImportRequest $request)
    {
        DB::beginTransaction();
        try {
            Excel::import(new ClientsImport(), $request->file('file'));
        } catch (Exception $exception) {
            Log::error($exception);
            DB::rollBack();
            return $this->response->errorBadRequest('上传文件排版有问题，请严格按照模版格式填写');
        }
        DB::commit();
        return $this->response->created();
    }

    public function export()
    {
        $file = '当前用户导出'. date('YmdHis', time()).'.xlsx';
        return (new ClientsExport())->download($file);
    }
}