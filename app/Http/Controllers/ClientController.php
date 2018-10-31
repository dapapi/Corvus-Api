<?php

namespace App\Http\Controllers;

use App\Http\Requests\ClientStoreRequest;
use App\Http\Transformers\ClientTransformer;
use App\Models\Client;
use App\Models\Contact;
use App\Models\Industry;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ClientController extends Controller
{
    // todo 列表显示可能涉及数据权限
    public function index(Request $request)
    {
        $clients = Client::all();
        return $this->response->collection($clients, new ClientTransformer());
    }

    // todo 根据组织架构区分客户类型
    public function store(ClientStoreRequest $request)
    {
        $payload = $request->all();

        if ($request->has('region_id'))
                $payload['region_id'] = hashid_decode($payload['region_id']);

        $payload['industry_id'] = hashid_decode($payload['industry_id']);
        $payload['principal_id'] = hashid_decode($payload['principal_id']);

        $payload['industry'] = Industry::find($payload['industry_id'])->name;

        $user = Auth::guard('api')->user();
        $payload['creator_id'] = $user->id;

        DB::beginTransaction();
        try {

            $client = Client::create($payload);

            if ($request->has('contact')) {
                $contact = Contact::create([
                    'name' => $payload['contact']['name'],
                    'phone' => $payload['contact']['phone'],
                    'position' => $payload['contact']['position'],
                    'client_id' => $client->id
                ]);
            }
        } catch (\Exception $exception) {
            Log::error($exception);
            DB::rollBack();
            return $this->response->error('创建失败', 500);
        }
        DB::commit();

        return $this->response->created();
    }

    public function edit(Request $request, Client $client)
    {
        $payload = $request->all();

        try {
            foreach ($payload as $key => $val) {
                // todo 部分字段可能没权限修改
                $client[$key] = $val;
            }
            $client->save();
        } catch (\Exception $exception) {
            Log::error($exception);
            return $this->response->error();
        }

        return $this->response->accepted();
    }

    public function delete(Request $request)
    {

    }

    public function recover(Request $request)
    {

    }

    public function detail(Request $request)
    {

    }
}
