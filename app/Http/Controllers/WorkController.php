<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Work;
use App\Models\Star;
use App\Http\Transformers\WorkTransformer;
use App\Http\Requests\WorkRequest;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use App\Events\OperateLogEvent;
use App\Models\OperateEntity;
use App\OperateLogMethod;

class WorkController extends Controller
{

  public function index(Request $request,Star $star){
    $payload = $request->all();
    $pageSize = $request->get('page_size', config('app.page_size'));
    $works = Work::createDesc()
        ->where('star_id',$star->id)
        ->paginate($pageSize);
    return $this->response->paginator($works, new WorkTransformer());
  }

  public function store(WorkRequest $workrequest, Star $star){
    $payload = $workrequest->all();
    $user = Auth::guard('api')->user();
    $payload['creator_id']  = $user->id;
    $payload['star_id'] = $star->id;
    DB::beginTransaction();
    try {
        $work = Work::create($payload);//生成作品对象,并插入数据库
        // 操作日志
        $operate = new OperateEntity([
            'obj' => $work,
            'title' => null,
            'start' => null,
            'end' => null,
            'method' => OperateLogMethod::CREATE,
        ]);
        event(new OperateLogEvent([
            $operate,
        ]));
      }catch (Exception $e) {
            DB::rollBack();
            Log::error($e);
            return $this->response->errorInternal('创建失败');
      }
      DB::commit();
      return $this->response->item(Work::find($work->id), new WorkTransformer());
  }
}
