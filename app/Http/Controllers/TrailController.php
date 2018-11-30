<?php

namespace App\Http\Controllers;

use App\Events\OperateLogEvent;
use App\Http\Requests\Trail\EditTrailRequest;
use App\Http\Requests\Trail\FilterTrailRequest;
use App\Http\Requests\Trail\RefuseTrailReuqest;
use App\Http\Requests\Trail\SearchTrailRequest;
use App\Http\Requests\Trail\StoreTrailRequest;
use App\Http\Requests\Trail\TypeTrailReuqest;
use App\Http\Transformers\TrailTransformer;
use App\Models\OperateEntity;
use App\Models\Star;
use App\Models\Client;
use App\Models\Contact;
use App\Models\Trail;
use App\Models\TrailStar;
use App\OperateLogMethod;
use App\User;
use function foo\func;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class TrailController extends Controller
{
    public function index(Request $request)
    {
        $pageSize = $request->get('page_size', config('app.page_size'));

        $trails = Trail::orderBy('created_at', 'desc')->paginate($pageSize);

        return $this->response->paginator($trails , new TrailTransformer());
    }

    public function all(Request $request)
    {
        $clients = Trail::orderBy('created_at', 'desc')->get();
        return $this->response->collection($clients, new TrailTransformer());
    }

    // todo 根据所属公司存不同类型 去完善 /users/my
    public function store(StoreTrailRequest $request)
    {
        $payload = $request->all();

        $user = Auth::guard('api')->user();
        $payload['creator_id'] = $user->id;

        if ($request->has('lock') && $payload['lock'])
            $payload['lock_status'] = 1;

        $payload['principal_id'] = $request->has('principal_id') ? hashid_decode($payload['principal_id']) : null;
        // 改为直接新建
        $payload['contact_id'] = $request->has('contact_id') ? hashid_decode($payload['contact_id']) : null;
        $payload['industry_id'] = hashid_decode($payload['industry_id']);

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
            $client = Client::find(hashid_decode($payload['client']['id']));
            if (!$client)
                return $this->response->errorBadRequest('客户不存在');
        } elseif(array_key_exists('id', $payload['contact'])) {
            return $this->response->errorBadRequest('新建客户不应选现有联系人');
        } else {
            $client = null;
        }

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
                // 操作日志
                $operate = new OperateEntity([
                    'obj' => $client,
                    'title' => null,
                    'start' => null,
                    'end' => null,
                    'method' => OperateLogMethod::CREATE,
                ]);
                event(new OperateLogEvent([
                    $operate,
                ]));
            }

            if (!array_key_exists('id', $payload['contact'])) {
                $contact = Contact::create([
                    'client_id' => $client->id,
                    'name' => $payload['contact']['name'],
                    'phone' => $payload['contact']['phone'],
                ]);
                // 操作日志
                $operate = new OperateEntity([
                    'obj' => $contact,
                    'title' => null,
                    'start' => null,
                    'end' => null,
                    'method' => OperateLogMethod::CREATE,
                ]);
                event(new OperateLogEvent([
                    $operate,
                ]));
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
                    $starId = hashid_decode($recommendation);

                    if (Star::find($starId))
                        TrailStar::create([
                            'trail_id' => $trail->id,
                            'star_id' => $starId,
                            'type' => TrailStar::RECOMMENDATION,
                        ]);
                }
            }

            // 操作日志
            $operate = new OperateEntity([
                'obj' => $trail,
                'title' => null,
                'start' => null,
                'end' => null,
                'method' => OperateLogMethod::CREATE,
            ]);
            event(new OperateLogEvent([
                $operate,
            ]));
        } catch (\Exception $exception) {
            Log::error($exception);
            DB::rollBack();
            return $this->response->errorInternal('创建线索失败');
        }

        DB::commit();

        return $this->response->item($trail, new TrailTransformer());
    }

    //todo 操作日志怎么记
    public function edit(EditTrailRequest $request, Trail $trail)
    {
        $payload = $request->all();

        if ($request->has('principal_id') && !is_null($payload['principal_id']))
            $payload['principal_id'] = hashid_decode($payload['principal_id']);

        if ($request->has('industry_id') && !is_null($payload['industry_id']))
            $payload['industry_id'] = hashid_decode($payload['industry_id']);

        DB::beginTransaction();
        try {
            if ($request->has('lock') && $payload['lock'])
                $payload['lock_status'] = 1;

            $trail->update($payload);

            if ($request->has('client')) {
                $client = $trail->client;
                $client->update($payload['client']);
            }


            if ($request->has('contact')) {
                $contact = $trail->contact;
                $contact->update($payload['contact']);
            }

            if ($request->has('expectations')) {
                TrailStar::where('trail_id', $trail->id)->where('type', TrailStar::EXPECTATION)->delete();
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
                TrailStar::where('trail_id', $trail->id)->where('type', TrailStar::RECOMMENDATION)->delete();
                foreach ($payload['recommendations'] as $recommendation) {
                    $starId = hashid_decode($recommendation);

                    if (Star::find($starId))
                        TrailStar::create([
                            'trail_id' => $trail->id,
                            'star_id' => $starId,
                            'type' => TrailStar::RECOMMENDATION,
                        ]);
                }
            }

        } catch (\Exception $exception) {
            Log::error($exception);
            DB::rollBack();
            return $this->response->errorInternal('修改销售线索失败');
        }
        DB::commit();

        return $this->response->accepted();

    }

    public function delete(Request $request, Trail $trail)
    {

        $trail->progress_status = Trail::STATUS_DELETE;
        $trail->save();
        $trail->delete();

        return $this->response->noContent();
    }

    public function recover(Request $request, Trail $trail)
    {
        $trail->restore();
        $trail->progress_status = Trail::STATUS_UNCONFIRMED;
        $trail->save();

        $this->response->item($trail, new TrailTransformer());
    }

    public function detail(Request $request, Trail $trail)
    {
        // 操作日志
        $operate = new OperateEntity([
            'obj' => $trail,
            'title' => null,
            'start' => null,
            'end' => null,
            'method' => OperateLogMethod::LOOK,
        ]);
        event(new OperateLogEvent([
            $operate,
        ]));
        return $this->response->item($trail, new TrailTransformer());
    }

    public function forceDelete(Request $request, $trail)
    {
        $trail->forceDelete();

        return $this->response->noContent();
    }

    public function search(SearchTrailRequest $request)
    {
        $type = $request->get('type');
        $id = hashid_decode($request->get('id'));

        $pageSize = $request->get('page_size', config('app.page_size'));

        switch ($type) {
            case 'clients':
                $trails = Trail::where('client_id', $id)->paginate($pageSize);
                break;
            default:
                return $this->response->noContent();
                break;
        }

        return $this->response->paginator($trails, new TrailTransformer());
    }

    public function type(TypeTrailReuqest $reuqest)
    {
        $type = $reuqest->get('type');

        $trails = Trail::where('type', $type)->get();

        return $this->response->collection($trails, new TrailTransformer());
    }

    public function refuse(RefuseTrailReuqest $request, Trail $trail)
    {
        $type = $request->get('type');
        $reason = $request->get('reason');

        DB::beginTransaction();
        try {
            $operate = new OperateEntity([
                'obj' => $trail,
                'title' => null,
                'start' => $type .'，' . $reason,
                'end' => null,
                'method' => OperateLogMethod::REFUSE,
            ]);
            event(new OperateLogEvent([
                $operate,
            ]));

            if ($type == '我方拒绝') {
                $status = 2;
            } elseif ($type == '客户拒绝') {
                $status = 3;
            } else {
                throw new \Exception('拒绝类型错误');
            }
            $trail->update([
                'progress_status' => Trail::STATUS_REFUSE,
                'status' => $status
            ]);

        } catch (\Exception $exception) {
            Log::error($exception);
            Db::rollBack();
            return $this->response->errorInternal($exception->getMessage());
        }
        DB::commit();

        return $this->response->accepted(null, '线索已拒绝');
    }

    public function filter(FilterTrailRequest $request)
    {
        $payload = $request->all();

        $pageSize = $request->get('page_size', config('app.page_size'));

        $trails = Trail::where(function($query) use ($request, $payload) {
            if ($request->has('keyword'))
                $query->where('title', 'LIKE', '%' . $payload['keyword'] . '%');
            if ($request->has('status'))
                $query->where('progress_status', $payload['status']);
            if ($request->has('principal_id') && $payload['principal_id'])
                $query->where('principal_id', hashid_decode((int)$payload['principal_id']));
        })->orderBy('created_at', 'desc')->paginate($pageSize);

        return $this->response->paginator($trails, new TrailTransformer());
    }
}
