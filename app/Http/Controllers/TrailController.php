<?php

namespace App\Http\Controllers;

use App\Http\Requests\Trail\EditTrailRequest;
use App\Http\Requests\Trail\StoreTrailRequest;
use App\Http\Transformers\TrailTransformer;
use App\Models\Star;
use App\Models\Client;
use App\Models\Contact;
use App\Models\Trail;
use App\Models\TrailStar;
use App\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class TrailController extends Controller
{
    public function index(Request $request)
    {
        if ($request->has('page_size')) {
            $pageSize = $request->get('page_size');
        } else {
            $pageSize = config('page_size');
        }

        $clients = Trail::orderBy('created_at', 'desc')->paginate($pageSize);
        return $this->response->paginator($clients, new TrailTransformer());
    }

    // todo 根据所属公司存不同类型 去完善 /users/my
    public function store(StoreTrailRequest $request)
    {
        $payload = $request->all();

        $user = Auth::guard('api')->user();
        $payload['creator_id'] = $user->id;

        //金额化整
        $payload['fee'] = 100 * $payload['fee'];

        $payload['principal_id'] = $request->has('principal_id') ? hashid_decode($payload['principal_id']) : null;
        // 改为直接新建
        $payload['contact_id'] = $request->has('contact_id') ? hashid_decode($payload['contact_id']) : null;
        $payload['artist_id'] = $request->has('artist_id') ? hashid_decode($payload['artist_id']) : null;

        if (is_numeric($payload['resource'])) {
            $payload['resource'] = hashid_decode($payload['resource']);
        }

        if (array_key_exists('id', $payload['contact'])) {
            $contact = Contact::find(hashid_decode($payload['contact']['id']));
            if (!$contact)
                return $this->response->errorBadRequest('联系人不存在');
        } else {
            $contact = null;
        }

        if (array_key_exists('id', $payload['client'])) {
            $client = Contact::find(hashid_decode($payload['client']['id']));
            if (!$client)
                return $this->response->errorBadRequest('联系人与客户不匹配');
        } else {
            $client = null;
        }


        $artist = Star::find($payload['artist_id']);
        if (!$artist)
            return $this->response->errorBadRequest('艺人不存在');

        $user = User::find($payload['principal_id']);
        if (!$user)
            return $this->response->errorBadRequest('用户不存在');

        DB::beginTransaction();

        try {
            if (!array_key_exists('id', $payload['client'])) {
                $client = Client::create([
                    'company' => $payload['client']['company'],
                    'grade' => $payload['client']['grade'],
                    'principal_id' => $payload['principal_id'],
                    'creator_id' => $user->id,
                ]);
            }

            if (!array_key_exists('id', $payload['contact'])) {
                $contact = Contact::create([
                    'client_id' => $client->id,
                    'name' => $payload['contact']['name'],
                    'phone' => $payload['contact']['phone'],
                ]);
            }

            $payload['contact_id'] = $contact->id;
            $payload['client_id'] = $client->id;

            $trail = Trail::create($payload);


            if ($request->has('expectations')) {
                TrailStar::where('trail_id', $trail->id)->delete();
                foreach ($payload['expectations'] as $expectation) {
                    $starId = hashid_decode($expectation);

                    if (Star::find($starId))
                        TrailStar::create([
                            'trail_id' => $trail->id,
                            'star_id' => $starId,
                            'type' => TrailStar::EXPECTATION,
                        ]);
                }
            }

            if ($request->has('recommendations')) {
                foreach ($payload['recommendations'] as $recommendation) {
                    $artistId = hashid_decode($recommendation);

                    if (Star::find($artistId))
                        TrailStar::create([
                            'trail_id' => $trail->id,
                            'artist_id' => $artistId,
                            'type' => TrailStar::RECOMMENDATION,
                        ]);
                }
            }
        } catch (\Exception $exception) {
            Log::error($exception);
            DB::rollBack();
            return $this->response->error('创建线索失败', 500);
        }

        DB::commit();

        return $this->response->created();
    }

    public function edit(EditTrailRequest $request, Trail $trail)
    {
        $payload = $request->all();

        DB::beginTransaction();
        try {
            if (!array_key_exists('id', $payload['client'])) {
                $client = Client::create([
                    'company' => $payload['client']['company'],
                    'grade' => $payload['client']['grade'],
                ]);
            } else {
                $client = Client::find(hashid_decode($payload['client']['id']));
            }
            $payload['client_id'] = $client->id;

            if (!array_key_exists('id', $payload['contact'])) {
                $contact = Contact::create([
                    'client_id' => $client->id,
                    'name' => $payload['contact']['name'],
                    'phone' => $payload['contact']['phone'],
                ]);
            } else {
                $contact = Contact::find(hashid_decode($payload['contact']['id']));
            }
            $payload['contact_id'] = $contact->id;

            foreach ($payload as $key => $val) {
                $trail[$key] = $val;
            }
            if ($request->has('lock') && $payload['lock'])
                $trail['lock_status'] = 1;

            $trail->save();

            if ($request->has('expectations')) {
                TrailStar::where('trail_id', $trail->id)->delete();
                foreach ($payload['expectations'] as $expectation) {
                    $starId = hashid_decode($expectation);

                    if (Star::find($starId))
                        TrailStar::create([
                            'trail_id' => $trail->id,
                            'star_id' => $starId,
                            'type' => TrailStar::EXPECTATION,
                        ]);
                }
            }

            if ($request->has('recommendations')) {
                TrailStar::where('trail_id', $trail->id)->delete();
                foreach ($payload['recommendations'] as $recommendation) {
                    $artistId = hashid_decode($recommendation);

                    if (Star::find($artistId))
                        TrailStar::create([
                            'trail_id' => $trail->id,
                            'star_id' => $artistId,
                            'type' => TrailStar::RECOMMENDATION,
                        ]);
                }
            }

        } catch (\Exception $exception) {
            Log::error($exception);
            DB::rollBack();
            return $this->response->error('修改销售线索失败', 500);
        }
        DB::commit();

        return $this->response->accepted();

    }

    public function delete(Request $request, Trail $trail)
    {
        $trail->status = Trail::STATUS_FROZEN;
        $trail->save();
        $trail->delete();

        return $this->response->noContent();
    }

    public function recover(Request $request, Trail $trail)
    {
        $trail->restore();
        $trail->status = Trail::STATUS_NORMAL;
        $trail->save();

        $this->response->item($trail, new TrailTransformer());
    }

    public function detail(Request $request, Trail $trail)
    {
        return $this->response->item($trail, new TrailTransformer());
    }

    public function forceDelete(Request $request, $trail)
    {
        $trail->forceDelete();

        return $this->response->noContent();
    }
}
