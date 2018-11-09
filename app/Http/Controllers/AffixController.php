<?php

namespace App\Http\Controllers;

use App\Events\OperateLogEvent;
use App\Http\Requests\AffixRequest;
use App\Http\Transformers\AffixTransformer;
use App\Models\Affix;
use App\Models\OperateEntity;
use App\Models\Project;
use App\Models\Star;
use App\Models\Task;
use App\OperateLogMethod;
use App\Repositories\AffixRepository;
use App\Repositories\OperateLogRepository;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class AffixController extends Controller
{
    protected $affixRepository;
    protected $operateLogRepository;

    public function __construct(AffixRepository $affixRepository, OperateLogRepository $operateLogRepository)
    {
        $this->affixRepository = $affixRepository;
        $this->operateLogRepository = $operateLogRepository;
    }

    public function index(Request $request, Task $task, Project $project, Star $star)
    {
        $payload = $request->all();
        $pageSize = $request->get('page_size', config('app.page_size'));

        if ($task && $task->id) {
            $affixes = $task->affixes()->createDesc()->paginate($pageSize);
        } else if ($project && $project->id) {
            $affixes = $project->affixes()->createDesc()->paginate($pageSize);
        } else if ($star && $star->id) {
            $affixes = $project->affixes()->createDesc()->paginate($pageSize);
        }
        //TODO 其他模块

        return $this->response->paginator($affixes, new AffixTransformer());

    }

    public function recycleBin(Request $request, Task $task, Project $project, Star $star)
    {
        $payload = $request->all();
        $user = Auth::guard('api')->user();
        $pageSize = $request->get('page_size', config('app.page_size'));

        if ($task && $task->id) {
            $affixes = $task->affixes()->onlyTrashed()->createDesc()->paginate($pageSize);
        } else if ($project && $project->id) {
            $affixes = $project->affixes()->onlyTrashed()->createDesc()->paginate($pageSize);
        } else if ($star && $star->id) {
            $affixes = $star->affixes()->onlyTrashed()->createDesc()->paginate($pageSize);
        }
        //TODO 其他模块

        return $this->response->paginator($affixes, new AffixTransformer());
    }

    public function add(AffixRequest $request, Task $task, Project $project, Star $star)
    {
        $payload = $request->all();
        $user = Auth::guard('api')->user();

        DB::beginTransaction();
        try {
            $affix = $this->affixRepository->addAffix($user, $task, $project, $payload['title'], $payload['url'], $payload['size'], 1);
            if ($affix) {
                // 操作日志
                $array = [
                    'title' => null,
                    'start' => null,
                    'end' => null,
                    'method' => OperateLogMethod::UPLOAD_AFFIX,
                ];
                $array['obj'] = $this->operateLogRepository->getObject($task, $project, $star);
                $operate = new OperateEntity($array);
                event(new OperateLogEvent([
                    $operate,
                ]));
            }
        } catch (Exception $e) {
            DB::rollBack();
            Log::error($e);
            return $this->response->errorInternal();
        }
        DB::commit();
        return $this->response->created();
    }

    public function download(Request $request, Task $task, Project $project, Star $star, Affix $affix)
    {
        try {
            // 操作日志
            $array = [
                'title' => null,
                'start' => null,
                'end' => null,
                'method' => OperateLogMethod::DOWNLOAD_AFFIX,
            ];
            $array['obj'] = $this->operateLogRepository->getObject($task, $project, $star);
            $operate = new OperateEntity($array);
            event(new OperateLogEvent([
                $operate,
            ]));
        } catch (Exception $e) {
            Log::error($e);
            return $this->response->errorInternal();
        }
        return $this->response->created();
    }

    public function remove(Request $request, Task $task, Project $project, Star $star, Affix $affix)
    {
        $payload = $request->all();
        DB::beginTransaction();
        try {
            $affix->delete();
            // 操作日志
            $array = [
                'title' => '附件',
                'start' => null,
                'end' => null,
                'method' => OperateLogMethod::DELETE_OTHER,
            ];
            $array['obj'] = $this->operateLogRepository->getObject($task, $project, $star);
            $operate = new OperateEntity($array);
            event(new OperateLogEvent([
                $operate,
            ]));
        } catch (Exception $e) {
            DB::rollBack();
            Log::error($e);
            return $this->response->errorInternal();
        }
        DB::commit();
        return $this->response->accepted();
    }

    public function recoverRemove(Request $request, Task $task, Project $project, Star $star, Affix $affix)
    {
        $payload = $request->all();
        DB::beginTransaction();
        try {
            $affix->restore();
            // 操作日志
            $array = [
                'title' => '附件',
                'start' => null,
                'end' => null,
                'method' => OperateLogMethod::RECOVER_OTHER,
            ];
            $array['obj'] = $this->operateLogRepository->getObject($task, $project, $star);
            $operate = new OperateEntity($array);
            event(new OperateLogEvent([
                $operate,
            ]));
        } catch (Exception $e) {
            DB::rollBack();
            Log::error($e);
            return $this->response->errorInternal('恢复附件失败');
        }
        DB::commit();
        return $this->response->noContent();
    }
}
