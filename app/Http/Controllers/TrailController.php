<?php

namespace App\Http\Controllers;

use App\Events\OperateLogEvent;
use App\Exports\TrailsExport;
use App\Http\Requests\Filter\TrailFilterRequest;
use App\Http\Requests\Trail\EditTrailRequest;
use App\Http\Requests\Trail\FilterTrailRequest;
use App\Http\Requests\Trail\RefuseTrailReuqest;
use App\Http\Requests\Trail\SearchTrailRequest;
use App\Http\Requests\Trail\StoreTrailRequest;
use App\Http\Requests\Trail\TypeTrailReuqest;
use App\Http\Transformers\TrailTransformer;
use App\Models\Blogger;
use App\Models\FilterJoin;
use App\Models\OperateEntity;
use App\Models\Star;
use App\Models\Client;
use App\Models\Contact;
use App\Models\Trail;
use App\Models\TrailStar;
use App\ModuleableType;
use App\OperateLogMethod;
use App\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Maatwebsite\Excel\Excel;

class TrailController extends Controller
{
    public function index(FilterTrailRequest $request)
    {
        $payload = $request->all();

        $pageSize = $request->get('page_size', config('app.page_size'));

        $trails = Trail::where(function ($query) use ($request, $payload) {
            if ($request->has('keyword') && $payload['keyword'])
                $query->where('title', 'LIKE', '%' . $payload['keyword'] . '%');
            if ($request->has('status') && !is_null($payload['status']))
                $query->where('progress_status', $payload['status']);
            if ($request->has('principal_ids') && $payload['principal_ids']) {
                $payload['principal_ids'] = explode(',', $payload['principal_ids']);
                foreach ($payload['principal_ids'] as &$id) {
                    $id = hashid_decode((int)$id);
                }
                unset($id);
                $query->whereIn('principal_id', $payload['principal_ids']);
            }
        })->orderBy('created_at', 'desc')->paginate($pageSize);

        return $this->response->paginator($trails, new TrailTransformer());
    }

    public function all(Request $request)
    {
        $clients = Trail::orderBy('created_at', 'desc')->get();
        return $this->response->collection($clients, new TrailTransformer());
    }

    // todo 根据所属公司存不同类型 去完善 /users/my 目前为前端传type，之前去确认是否改
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
        } elseif (array_key_exists('id', $payload['contact'])) {
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
                    'type' => $payload['type'],
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
                    'obj' => $client,
                    'title' => '该用户',
                    'start' => '联系人',
                    'end' => null,
                    'method' => OperateLogMethod::ADD_PERSON,
                ]);
                event(new OperateLogEvent([
                    $operate,
                ]));
            }

            $payload['contact_id'] = $contact->id;
            $payload['client_id'] = $client->id;

            $trail = Trail::create($payload);

            if ($request->has('expectations') && is_array($payload['expectations'])) {
                if ($trail->type == Trail::TYPE_PAPI) {
                    $starableType = ModuleableType::BLOGGER;
                } else {
                    $starableType = ModuleableType::STAR;
                }
                foreach ($payload['expectations'] as $expectation) {
                    $starId = hashid_decode($expectation);

                    if ($starableType == ModuleableType::BLOGGER) {
                        if (Blogger::find($starId))
                            TrailStar::create([
                                'trail_id' => $trail->id,
                                'starable_id' => $starId,
                                'starable_type' => $starableType,
                                'type' => TrailStar::EXPECTATION,
                            ]);
                    } else {
                        if (Star::find($starId))
                            TrailStar::create([
                                'trail_id' => $trail->id,
                                'starable_id' => $starId,
                                'starable_type' => $starableType,
                                'type' => TrailStar::EXPECTATION,
                            ]);
                    }
                }
            }

            if ($request->has('recommendations') && is_array($payload['recommendations'])) {
                if ($trail->type == Trail::TYPE_PAPI) {
                    $starableType = ModuleableType::BLOGGER;
                } else {
                    $starableType = ModuleableType::STAR;
                }
                foreach ($payload['recommendations'] as $recommendation) {
                    $starId = hashid_decode($recommendation);
                    if ($starableType == ModuleableType::BLOGGER) {
                        if (Blogger::find($starId))
                            TrailStar::create([
                                'trail_id' => $trail->id,
                                'starable_id' => $starId,
                                'starable_type' => $starableType,
                                'type' => TrailStar::RECOMMENDATION,
                            ]);
                    } else {
                        if (Star::find($starId))
                            TrailStar::create([
                                'trail_id' => $trail->id,
                                'starable_id' => $starId,
                                'starable_type' => $starableType,
                                'type' => TrailStar::RECOMMENDATION,
                            ]);
                    }
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

            if ($request->has('expectations') && is_array($payload['expectations'])) {
                if ($trail->type == Trail::TYPE_PAPI) {
                    $starableType = ModuleableType::BLOGGER;
                } else {
                    $starableType = ModuleableType::STAR;
                }
                TrailStar::where('trail_id', $trail->id)->where('starable_type', $starableType)->where('type', TrailStar::EXPECTATION)->delete();
                foreach ($payload['expectations'] as $expectation) {
                    $starId = hashid_decode($expectation);
                    if ($starableType == ModuleableType::BLOGGER) {
                        if (Blogger::find($starId))
                            TrailStar::create([
                                'trail_id' => $trail->id,
                                'starable_id' => $starId,
                                'starable_type' => $starableType,
                                'type' => TrailStar::EXPECTATION,
                            ]);
                    } else {
                        if (Star::find($starId))
                            TrailStar::create([
                                'trail_id' => $trail->id,
                                'starable_id' => $starId,
                                'starable_type' => $starableType,
                                'type' => TrailStar::EXPECTATION,
                            ]);
                    }
                }
            }

            if ($request->has('recommendations') && is_array($payload['recommendations'])) {

                if ($trail->type == Trail::TYPE_PAPI) {
                    $starableType = ModuleableType::BLOGGER;
                } else {
                    $starableType = ModuleableType::STAR;
                }
                TrailStar::where('trail_id', $trail->id)->where('starable_type', $starableType)->where('type', TrailStar::RECOMMENDATION)->delete();
                foreach ($payload['recommendations'] as $recommendation) {
                    $starId = hashid_decode($recommendation);

                    if ($starableType == ModuleableType::BLOGGER) {
                        if (Blogger::find($starId))
                            TrailStar::create([
                                'trail_id' => $trail->id,
                                'starable_id' => $starId,
                                'starable_type' => $starableType,
                                'type' => TrailStar::RECOMMENDATION,
                            ]);
                    } else {
                        if (Star::find($starId))
                            TrailStar::create([
                                'trail_id' => $trail->id,
                                'starable_id' => $starId,
                                'starable_type' => $starableType,
                                'type' => TrailStar::RECOMMENDATION,
                            ]);
                    }
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
                'start' => $type . '，' . $reason,
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

        $trails = Trail::where(function ($query) use ($request, $payload) {
            if ($request->has('keyword') && $payload['keyword'])
                $query->where('title', 'LIKE', '%' . $payload['keyword'] . '%');
            if ($request->has('status') && !is_null($payload['status']))
                $query->where('progress_status', $payload['status']);
            if ($request->has('principal_ids') && $payload['principal_ids']) {
                $payload['principal_ids'] = explode(',', $payload['principal_ids']);
                foreach ($payload['principal_ids'] as &$id) {
                    $id = hashid_decode((int)$id);
                }
                unset($id);
                $query->whereIn('principal_id', $payload['principal_ids']);
            }
        })->orderBy('created_at', 'desc')->paginate($pageSize);

        return $this->response->paginator($trails, new TrailTransformer());
    }

    private function editLog($obj, $field, $old, $new)
    {
        $operate = new OperateEntity([
            'obj' => $obj,
            'title' => $field,
            'start' => $old,
            'end' => $new,
            'method' => OperateLogMethod::UPDATE,
        ]);
        event(new OperateLogEvent([
            $operate,
        ]));
    }

    /**
     *  todo
     *  1. 定返回格式
     *  2. 根据返回拼sql
     *  3. sql返回带分页带eloquent模型
     * @param $request
     */
    public function getFilter(TrailFilterRequest $request)
    {
        $pageSize = $request->get('page_size', config('app.page_size'));

        $user = Auth::guard('api')->user();
        $company = $user->company->name;

        $joinSql = FilterJoin::where('company', $company)->where('table_name', 'trails')->first()->join_sql;

        $query = DB::table('trails')->selectRaw('DISTINCT(trails.id) as ids')->from(DB::raw($joinSql));

        $keyword = $request->get('keyword', '');
        if ($keyword !== '') {
            // todo 本表中字符型字段模糊查询; 本表中枚举使用的字段也需要加入
            $query->whereRaw('CONCAT(`trails`.`title`,`trails`.`brand`,`trails`.`desc`) LIKE "%?%"', [$keyword]);
        }

        $conditions = $request->get('conditions');
        foreach ($conditions as $condition) {
            $field = $condition['field'];
            $operator = $condition['operator'];
            $type = $condition['type'];
            if ($operator == 'LIKE') {
                $value = '%' . $condition['value'] . '%';
                $query->whereRaw("$field $operator ?", [$value]);
            } else if ($operator == 'in') {
                $value = $condition['value'];
                if ($type >= 5)
                    foreach ($value as &$v) {
                        $v = hashid_decode($v);
                    }
                unset($v);
                $query->whereIn($field, $value);
            } else {
                $value = $condition['value'];
                $query->whereRaw("$field $operator ?", [$value]);
            }

        }
        $sql_with_bindings = str_replace_array('?', $query->getBindings(), $query->toSql());
//        dd($sql_with_bindings);
        $result = $query->pluck('ids')->toArray();

        $trails = Trail::whereIn('id', $result)->orderBy('created_at', 'desc')->paginate($pageSize);

        return $this->response->paginator($trails, new TrailTransformer());
    }

    public function export(Request $request)
    {
        $file = '当前线索导出'. date('YmdHis', time()).'.xlsx';
        return (new TrailsExport())->download($file);
    }

    public function import()
    {

    }

}
