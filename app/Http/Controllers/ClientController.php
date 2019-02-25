<?php

namespace App\Http\Controllers;

use App\Events\ClientDataChangeEvent;
use App\Events\ClientMessageEvent;
use App\Events\OperateLogEvent;
use App\Exports\ClientsExport;
use App\Http\Requests\Client\EditClientRequest;
use App\Http\Requests\Client\FilterClientRequest;
use App\Http\Requests\Client\StoreClientRequest;
use App\Http\Requests\Excel\ExcelImportRequest;
use App\Http\Requests\Filter\FilterRequest;
use App\Http\Transformers\ClientTransformer;
use App\Imports\ClientsImport;
use App\Models\Client;
use App\Models\Contact;
use App\Models\OperateEntity;
use App\OperateLogMethod;
use App\TriggerPoint\ClientTriggerPoint;
use App\User;
use Carbon\Carbon;
use App\ModuleableType;
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
        $pageSize = $request->get('page_size', config('app.page_size'));
        $clients = Client::searchData()
            ->leftJoin('operate_logs',function($join){
                $join->on('clients.id','operate_logs.logable_id')
                    ->where('logable_type',ModuleableType::CLIENT)
                    ->where('operate_logs.method','4');

            })
            ->groupBy('clients.id')
            ->orderBy('up_time', 'desc')->orderBy('clients.created_at', 'desc')->select(['clients.id','company','type','grade','province','city','district',
                'address','clients.status','principal_id','creator_id','client_rating','size','desc','clients.created_at','clients.updated_at','protected_client_time',
                DB::raw( "max(operate_logs.updated_at) as up_time")])
            ->paginate($pageSize);
//        $sql_with_bindings = str_replace_array('?', $clients->getBindings(), $clients->toSql());
//        dd($sql_with_bindings);
        return $this->response->paginator($clients, new ClientTransformer());
    }

    public function all(Request $request)
    {
        $isAll = $request->get('all', false);
        $clients = Client::orderBy('created_at', 'desc')
            ->searchData()
            ->get();

        return $this->response->collection($clients, new ClientTransformer($isAll));
    }

    public function store(StoreClientRequest $request)
    {
        $payload = $request->all();

        $payload['principal_id'] = hashid_decode($payload['principal_id']);
        if ($payload['grade'] == Client::GRADE_NORMAL){
            $payload['protected_client_time'] = Carbon::now()->addDay(90)->toDateTimeString();//直客保护截止日期
        }
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

        //直客新增是
        if ($client->grade == Client::GRADE_NORMAL){
            $authorization = $request->header()['authorization'][0];
            event(new ClientMessageEvent($client,ClientTriggerPoint::CREATE_NEW_GRADE_NORMAL,$authorization,$user));
        }

        return $this->response->item($client, new ClientTransformer());
    }

    public function edit(EditClientRequest $request, Client $client)
    {
        $payload = $request->all();
        $old_client = clone $client; //记录客户初始对象，用于记录日志
        if (array_key_exists('_url', $payload))
            unset($payload['_url']);

        $columns = DB::getDoctrineSchemaManager()->listTableDetails('clients');
        if ($request->has('principal_id'))
            $payload['principal_id'] = hashid_decode($payload['principal_id']);

        try {
//            foreach ($payload as $key => $value) {
//                $lastValue = $client[$key];
//                if($lastValue != $value){
//                    if($key == "principal_id"){
//                        $lastValue = User::find($client->principal_id)->name;
//                        $value = User::findOrFail($value)->name;
//                    }
//                    if ($key == "grade"){
//                        $lastValue = $client->getGrade($client->grade);
//                        $value = $client->getGrade($value);
//                    }
//                    $comment = $columns->getColumn($key)->getComment();
////                    $this->editLog($client, $comment, $lastValue, $value);//修改客户日志
//                }
//
//            }
            $client->update($payload);
            event(new ClientDataChangeEvent($old_client,$client));

        } catch (\Exception $exception) {
            dd($exception);
            Log::error($exception);
            return $this->response->errorInternal('修改失败');
        }
        return $this->response->accepted();
    }

    public function delete(Request $request, Client $client)
    {
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
        $client->restore();
        $client->status = Client::STATUS_NORMAL;
        $client->save();

        return $this->response->item($client, new ClientTransformer());
    }

    public function detail(Request $request, Client $client)
    {
        $client = $client->searchData()->find($client->id);
        if($client == null){
            return $this->response->errorInternal("你没有查看该数据的权限");
        }
        // 操作日志
        $operate = new OperateEntity([
            'obj' => $client,
            'title' => null,
            'start' => null,
            'end' => null,
            'method' => OperateLogMethod::LOOK,
        ]);
        event(new OperateLogEvent([
            $operate,
        ]));
        return $this->response->item($client, new ClientTransformer());
    }

    public function filter(FilterClientRequest $request)
    {
        $payload = $request->all();
        $pageSize = $request->get('page_size', config('app.page_size'));

        $clients = Client::where(function ($query) use ($request, $payload) {
            if ($request->has('keyword'))
                $query->where('company', 'LIKE', '%' . $payload['keyword'] . '%');
            if ($request->has('grade'))
                $query->where('grade', $payload['grade']);
            if ($request->has('principal_ids') && count($payload['principal_ids'])) {
                foreach ($payload['principal_ids'] as &$id) {
                    $id = hashid_decode((int)$id);
                }
                unset($id);
                $query->whereIn('principal_id', $payload['principal_ids']);
            }
        })->searchData() ->leftJoin('operate_logs',function($join){
            $join->on('clients.id','operate_logs.logable_id')
                ->where('logable_type',ModuleableType::CLIENT)
                ->where('operate_logs.method','4');
        })->groupBy('clients.id')
            ->orderBy('up_time', 'desc')->orderBy('clients.created_at', 'desc')->select(['clients.id','company','type','grade','province','city','district',
                'address','clients.status','principal_id','creator_id','client_rating','size','desc','clients.created_at','clients.updated_at','protected_client_time',
                DB::raw("max(operate_logs.updated_at) as up_time")])
            ->paginate($pageSize);
//        $sql_with_bindings = str_replace_array('?', $clients->getBindings(), $clients->toSql());
//        dd($sql_with_bindings);

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

    public function export(Request $request)
    {
        $file = '当前用户导出'. date('YmdHis', time()).'.xlsx';
        return (new ClientsExport($request))->download($file);
    }

    /**
     * 暂时不用列表了，逻辑要换
     * @param FilterRequest $request
     * @return \Dingo\Api\Http\Response
     */
    public function getFilter(FilterRequest $request)
    {
        $pageSize = $request->get('page_size', config('app.page_size'));

        $all = $request->get('all', false);

        $query = Client::query();
        $conditions = $request->get('conditions');
        foreach ($conditions as $condition) {
            $field = $condition['field'];
            $operator = $condition['operator'];
            $type = $condition['type'];
            if ($operator == 'LIKE') {
                $value = '%' . $condition['value'] . '%';
                $query->whereRaw("$field $operator ?", [$value]);
            } else if ($operator == 'in') {
                $value = $condition['value'];
                if ($type >= 5)
                    foreach ($value as &$v) {
                        $v = hashid_decode($v);
                    }
                unset($v);
                $query->whereIn($field, $value);
            } else {
                $value = $condition['value'];
                $query->whereRaw("$field $operator ?", [$value]);
            }

        }
        // 这句用来检查绑定的参数
        $sql_with_bindings = str_replace_array('?', $query->getBindings(), $query->toSql());

        $clients = $query->orderBy('created_at', 'desc')->paginate($pageSize);

        return $this->response->paginator($clients, new ClientTransformer(!$all));
    }
}
