<?php

namespace App\Http\Transformers;

use App\Models\Client;
use League\Fractal\TransformerAbstract;

class ClientTransformer extends TransformerAbstract
{
    public function transform(Client $client)
    {
        $array = [
            'id' => hashid_encode($client->id),
            'company' => $client->company,
            'grade' => $client->grade,
            'type' => $client->type,
            'address' => $client->address,
            'principal' => $client->principal ? $client->principal->name : null,
            'creator' => $client->creator->name,
            'size' => $client->size,
            'keyman' => $client->keyman,
            'desc' => $client->desc,
        ];

        return $array;
    }
}