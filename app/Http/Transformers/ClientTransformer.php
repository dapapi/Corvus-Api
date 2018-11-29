<?php

namespace App\Http\Transformers;

use App\Models\Client;
use League\Fractal\TransformerAbstract;

class ClientTransformer extends TransformerAbstract
{
    protected $availableIncludes = ['principal', 'creator'];

    private  $isAll = true;

    public function __construct($isAll = true)
    {
        $this->isAll = $isAll;
    }

    public function transform(Client $client)
    {
        if ($this->isAll) {
            $array = [
                'id' => hashid_encode($client->id),
                'company' => $client->company,
                'grade' => $client->grade,
                'type' => $client->type,
                'address' => $client->address,
                'size' => $client->size,
                'keyman' => $client->keyman,
                'desc' => $client->desc,
                'created_at' => $client->created_at,
                'updated_at' => $client->updated_at,
            ];
        } else {
            $array = [
                'id' => hashid_encode($client->id),
                'company' => $client->company,
                'grade' => $client->grade,
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
}