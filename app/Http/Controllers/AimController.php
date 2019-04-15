<?php

namespace App\Http\Controllers;

use App\Http\Requests\Aim\AimEditRequest;
use App\Http\Requests\Aim\AimStoreRequest;
use App\Http\Transformers\Aim\AimDetailTransformer;
use App\Http\Transformers\Aim\AimSimpleTransformer;
use App\Http\Transformers\AimTransformer;
use App\Models\Aim;
use App\Models\OperateEntity;
use App\OperateLogMethod;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class AimController extends Controller
{
    public function index(Request $request)
    {
        $pageSize = $request->get('page_size', 10);
        $range = $request->get('range', 1);
        $paginator = Aim::whereIn('range', [$range])->paginate($pageSize);
        return $this->response->paginator($paginator, new AimSimpleTransformer());
    }

    public function store(AimStoreRequest $request)
    {
        $payload = $request->all();
        if ($request->has('department_id')) {
            $payload['department_id'] = hashid_decode($payload['department_id']);
            $payload['department_name'] = DB::table('departments')->where('id', $payload['department_id'])->value('name');
        }

        $payload['principal_id'] = hashid_decode($payload['principal']['id']);
        $payload['principal_name'] = $payload['principal']['name'];

        $payload['period_id'] = hashid_decode($payload['period_id']);
        $payload['period_name'] = DB::table('aim_periods')->where('id', $payload['period_id'])->value('name');
        $payload['deadline'] = DB::table('aim_periods')->where('id', $payload['period_id'])->value('end_at');

        $creator = Auth::guard('api')->user();
        $payload['creator_id'] = $creator->id;
        $payload['creator_name'] = $creator->name;

        DB::beginTransaction();
        try {
            $aim = Aim::create($payload);
            if ($payload->has('parents_ids')) {
                foreach ($payload['parents_ids'] as $id) {
                    $id = hashid_decode($id);
                    $pAim = Aim::find($id);
                    if ($pAim)
                        $aim->parents()->create([
                            'p_aim_id' => $pAim->id,
                            'p_aim_name' => $pAim->title,
                            'p_aim_range' => $pAim->range,
                            'c_aim_id' => $aim->id,
                            'c_aim_name' => $aim->title,
                            'c_aim_range' => $aim->range,
                        ]);
                }
            }
            // 操作日志
            $operate = new OperateEntity([
                'obj' => $aim,
                'title' => null,
                'start' => null,
                'end' => null,
                'method' => OperateLogMethod::CREATE,
            ]);
        } catch (\Exception $exception) {
            Log::error($exception);
            DB::rollBack();
            return $this->response->error('创建失败');
        }
        DB::commit();

        return $this->response->item($aim, new AimDetailTransformer());

    }

    public function detail(Request $request, Aim $aim)
    {
        return $this->response->item($aim, new AimDetailTransformer());
    }

    public function edit(AimEditRequest $request)
    {

    }

    public function delete()
    {

    }
}
