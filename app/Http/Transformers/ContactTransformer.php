<?php

namespace App\Http\Transformers;

use App\Models\Contact;
use League\Fractal\TransformerAbstract;

class ContactTransformer extends TransformerAbstract
{
    public function transform(Contact $contact)
    {
        $array = [
            'id' => hashid_encode($contact->id),
            'name' => $contact->name,
            'phone' => $contact->phone,
            'position' => $contact->position,
            'status' => $contact->status,
        ];
        return $array;
    }
}