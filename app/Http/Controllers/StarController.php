<?php

namespace App\Http\Controllers;

use App\Events\OperateLogEvent;
use App\Http\Requests\StarRequest;
use App\Http\Transformers\StarTransformer;
use App\Models\OperateEntity;
use App\Models\Star;
use App\OperateLogMethod;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class StarController extends Controller
{
    public function index(Request $request)
    {
        $payload = $request->all();
        $pageSize = $request->get('page_size', config('app.page_size'));

        $stars = Star::createDesc()->paginate($pageSize);

        return $this->response->paginator($stars, new StarTransformer());
    }

    public function all(Request $request)
    {
        $stars = Star::orderBy('name')->get();

        return $this->response->collection($stars, new StarTransformer());
    }

    public function store(StarRequest $request)
    {
        $payload = $request->all();
        $user = Auth::guard('api')->user();

        unset($payload['status']);
        unset($payload['type']);

        $payload['creator_id'] = $user->id;

        DB::beginTransaction();

        try {
            $star = Star::create($payload);
            // 操作日志
            $operate = new OperateEntity([
                'obj' => $star,
                'title' => null,
                'start' => null,
                'end' => null,
                'method' => OperateLogMethod::CREATE,
            ]);
            event(new OperateLogEvent([
                $operate,
            ]));
        } catch (Exception $e) {
            DB::rollBack();
            Log::error($e);
            return $this->response->errorInternal('创建失败');
        }
        DB::commit();

        return $this->response->item(Star::find($star->id), new StarTransformer());
    }
}
