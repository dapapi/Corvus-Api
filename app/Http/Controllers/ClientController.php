<?php

namespace App\Http\Controllers;

use App\Http\Requests\Client\EditClientRequest;
use App\Http\Requests\Client\StoreClientRequest;
use App\Http\Transformers\ClientTransformer;
use App\Models\Client;
use App\Models\Contact;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ClientController extends Controller
{
    public function index(Request $request)
    {
        if ($request->has('page_size')) {
            $pageSize = $request->get('page_size');
        } else {
            $pageSize = config('page_size');
        }

        $clients = Client::orderBy('company', 'asc')->paginate($pageSize);
        return $this->response->paginator($clients, new ClientTransformer());
    }

    public function all(Request $request)
    {
        $isAll = $request->get('all', false);
        $clients = Client::orderBy('company', 'asc')->get();

        return $this->response->collection($clients, new ClientTransformer($isAll));
    }

    public function store(StoreClientRequest $request)
    {
        $payload = $request->all();

        if ($request->has('region_id'))
                $payload['region_id'] = hashid_decode($payload['region_id']);

        $payload['principal_id'] = hashid_decode($payload['principal_id']);

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
            return $this->response->errorInternal('创建失败');
        }
        DB::commit();

        return $this->response->item($client, new ClientTransformer());
    }

    public function edit(EditClientRequest $request, Client $client)
    {
        $payload = $request->all();

        try {
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
}
