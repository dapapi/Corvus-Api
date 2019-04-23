<?php

namespace App\Http\Transformers;

use App\Models\Client;
use App\Models\Contract;
use App\TaskStatus;
use DemeterChain\C;
use League\Fractal\TransformerAbstract;

class ClientIndexTransformer extends TransformerAbstract
{
    protected $availableIncludes = ['principal', 'creator', 'tasks','contacts'];

    private  $isAll = true;

    public function __construct($isAll = true)
    {
        $this->isAll = $isAll;
    }

    public function transform(Client $client)
    {
        if ($this->isAll) {
            $array = [
                'company' => $client->company,
                'brand' => $client->brand,
                'grade' => $client->grade,
            ];
        } else {
            $array = [
                'id' => hashid_encode($client->id),
                'company' => $client->company,
                'grade' => $client->grade,
                'ketman' => $client->keyman,
            ];

        }


        return $array;
    }

    public function includePrincipal(Client $client)
    {
        $principal = $client->principal;
        if (!$principal)
            return null;

        return $this->item($principal, new UserTransformer());
    }

    public function includeCreator(Client $client)
    {
        $creator = $client->creator;
        if (!$creator)
            return null;

        return $this->item($creator, new UserTransformer());
    }
    public function includeContacts(Client $client){
        return $this->collection($client->contacts()->get(),new ContactTransformer());
    }

    public function includeTasks(Client $client)
    {
        $tasks = $client->tasks()->stopAsc()
            ->where('status',TaskStatus::NORMAL)
            ->limit(3)->get();
        return $this->collection($tasks, new TaskTransformer());
    }
}