<?php

namespace App\Http\Transformers;

use App\Models\Client;
use App\Models\Contract;
use App\TaskStatus;
use DemeterChain\C;
use Illuminate\Support\Facades\DB;

use League\Fractal\TransformerAbstract;

class ClientTransformer extends TransformerAbstract
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
                'id' => hashid_encode($client->id),
                'company' => $client->company,
                'grade' => $client->grade,
                'brand' => $client->brand,
                'keyman' => $client->keyman,
                'type' => $client->type,
                'client_rating' => $client->client_rating,
                'province' => $client->province,
                'city' => $client->city,
                'district' => $client->district,
                'address' => $client->address,
                'size' => $client->size,
                'desc' => $client->desc,
                'created_at' => $client->created_at->toDateTimeString(),
                'updated_at' => $client->updated_at->toDateTimeString(),
                // 日志内容
                'up_time' => $client->last_follow_up_at,
                'last_updated_user' => $client->last_updated_user,
                'last_updated_at' => $client->last_updated_at,
                'power' =>  $client->power,//对客户编辑权限
                'powers'    =>  $client->powers,
            ];
        } else {
            $array = [
                'id' => hashid_encode($client->id),
                'company' => $client->company,
                'grade' => $client->grade,
                'ketman' => $client->keyman,
            ];

        }

        $clientUser = DB::table('users')//
        ->where('users.id', $client->principal_id)
            ->select('users.id','users.name')->first();
        if($clientUser){
           // $array['principal']['data']['id'] = hashid_encode($clientUser->id);
            //$array['principal']['data']['name'] = $clientUser->name;
            $array['name'] = $clientUser->name;

        }else{
            //$array['principal']['id'] = '';
            $array['name'] = '';
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