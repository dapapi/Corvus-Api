<?php

namespace App\Http\Transformers;

use App\Models\Contact;
use League\Fractal\TransformerAbstract;

class ContactTransformer extends TransformerAbstract
{
    private  $isAll = true;

    public function __construct($isAll = true)
    {
        $this->isAll = $isAll;
    }

    public function transform(Contact $contact)
    {
        if ($this->isAll) {
            $array = [
                'id' => hashid_encode($contact->id),
                'name' => $contact->name,
                'phone' => $contact->phone,
                'position' => $contact->position,
                'status' => $contact->status,
            ];
        } else {
            $array = [
                'id' => hashid_encode($contact->id),
                'name' => $contact->name,
            ];
        }
        return $array;
    }
}