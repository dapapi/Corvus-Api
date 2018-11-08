<?php

namespace App\Http\Controllers;

use App\Http\Requests\Contact\EditContactRequest;
use App\Http\Requests\Contact\StoreContactRequest;
use App\Http\Transformers\ContactTransformer;
use App\Models\Client;
use App\Models\Contact;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class ContactController extends Controller
{
    public function index(Request $request, Client $client)
    {
        if ($request->has('page_size')) {
            $pageSize = $request->get('page_size');
        } else {
            $pageSize = config('page_size');
        }

        $contacts = $client->contacts()->paginate($pageSize);

        return $this->response->paginator($contacts, new ContactTransformer());
    }

    public function all(Request $request, Client $client)
    {
        $contacts = $client->contacts()->get();

        return $this->response->collection($contacts, new ContactTransformer());
    }

    public function detail(Request $request, Client $client, Contact $contact)
    {
        try {
            $client->contacts()->findOrFail($contact->id);
        } catch (Exception $exception) {
            return $this->response->errorNotFound('客户下未找到此联系人');
        }

        return $this->response->item($contact, new ContactTransformer());
    }

    public function store(StoreContactRequest $request, Client $client)
    {
        $payload = $request->all();

        $payload['client_id'] = $client->id;

        try {
            $contact = Contact::create($payload);
        } catch (\Exception $exception) {
            Log::error($exception);
            return $this->response->error('创建联系人失败', 500);
        }

        return $this->response->item($contact);
    }

    public function edit(EditContactRequest $request, Client $client, Contact $contact)
    {
        $payload = $request->all();

        try {
            $contact->name = $request->has('name') ? $payload['name'] : $contact->name;
            $contact->phone = $request->has('phone') ? $payload['phone'] : $contact->phone;
            $contact->position = $request->has('position') ? $payload['position'] : $contact->position;

            if ($request->has('client_id') && hashid_decode($payload['client_id']) !== $client->id)
                $contact->client_id = $payload['client_id'];

            $contact->save();
        } catch (\Exception $exception) {
            Log::error($exception);
            return $this->response->error('修改联系人失败', 500);
        }

        return $this->response->accepted();
    }

    // todo 是否只能从客户访问
    public function delete(Request $request, Client $client, Contact $contact)
    {
        try {
            $contact->status = Contact::STATUS_FROZEN;
            $contact->save();
            $contact->delete();
        } catch (Exception $exception) {
            Log::error($exception);
            return $this->response->error('删除失败', 500);
        }

        return $this->response->noContent();
    }

    public function recover(Request $request, Client $client, Contact $contact)
    {
        $contact->restore();
        $contact->status = Contact::STATUS_NORMAL;
        $contact->save();

        return $this->response->item($contact, new ContactTransformer());
    }
}
