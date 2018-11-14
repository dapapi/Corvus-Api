<?php

namespace App\Http\Transformers;

use App\Models\Client;
use League\Fractal\TransformerAbstract;

class ClientTransformer extends TransformerAbstract
{
    protected $availableIncludes = ['principal', 'creator'];

    public function transform(Client $client)
    {
        $array = [
            'id' => hashid_encode($client->id),
            'company' => $client->company,
            'grade' => $client->grade,
            'type' => $client->type,
            'address' => $client->address,
            'size' => $client->size,
            'keyman' => $client->keyman,
            'desc' => $client->desc,
        ];

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
}