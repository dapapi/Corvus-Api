<?php

namespace App\Http\Controllers;

use App\Events\OperateLogEvent;
use App\Http\Requests\Cilent\FilterClientRequest;
use App\Http\Requests\Client\EditClientRequest;
use App\Http\Requests\Client\StoreClientRequest;
use App\Http\Requests\Trail\RefuseTrailReuqest;
use App\Http\Transformers\ClientTransformer;
use App\Models\Client;
use App\Models\Contact;
use App\Models\OperateEntity;
use App\OperateLogMethod;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ClientController extends Controller
{
    // todo 加日志
    public function index(Request $request)
    {
        $pageSize = $request->get('page_size', config('app.page_size'));

        $clients = Client::orderBy('created_at', 'desc')->paginate($pageSize);
        return $this->response->paginator($clients, new ClientTransformer());
    }

    public function all(Request $request)
    {
        $isAll = $request->get('all', false);
        $clients = Client::orderBy('created_at', 'desc')->get();

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
                    'client_id' => $client->id
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
        $payload = $request->all();

        if ($request->has('principal_id'))
            $payload['principal_id'] = hashid_decode($payload['principal_id']);

        try {
            foreach ($payload as $key => $value) {
                $lastValue = $client[$key];
                $operate = new OperateEntity([
                    'obj' => $client,
                    'title' => '该用户',
                    'start' => '联系人',
                    'end' => null,
                    'method' => OperateLogMethod::UPDATE,
                ]);
                event(new OperateLogEvent([
                    $operate,
                ]));
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
        return $this->response->item($client, new ClientTransformer());
    }

    public function filter(FilterClientRequest $request)
    {
        $payload = $request->all();

        $pageSize = $request->get('page_size', config('app.page_size'));

        $clients = Client::where(function($query) use ($request, $payload) {
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
            }
        })->orderBy('created_at', 'desc')->paginate($pageSize);

        return $this->response->paginator($clients, new ClientTransformer());
    }
}
