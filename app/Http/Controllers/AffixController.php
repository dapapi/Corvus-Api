<?php

namespace App\Http\Controllers;

use App\Events\OperateLogEvent;
use App\Http\Requests\AffixQueryRequest;
use App\Http\Requests\AffixRequest;
use App\Http\Transformers\AffixTransformer;
use App\Models\Affix;
use App\Models\Blogger;
use App\Models\Client;
use App\Models\OperateEntity;
use App\Models\Project;
use App\Models\Star;
use App\Models\Task;
use App\Models\Trail;
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

    public function index(AffixQueryRequest $request, Task $task, Project $project, Star $star, Client $client, Trail $trail, Blogger $blogger)
    {
        $payload = $request->all();
        $pageSize = $request->get('page_size', config('app.page_size'));
        $type = $request->get('type');

        if ($task && $task->id) {
            $query = $task->affixes();
        } else if ($project && $project->id) {
            $query = $project->affixes();
        } else if ($star && $star->id) {
            $query = $project->affixes();
        } else if ($client && $client->id) {
            $query = $client->affixes();
        } else if ($trail && $trail->id) {
            $query = $client->affixes();
        } else if ($blogger && $blogger->id) {
            $query = $blogger->affixes();
        }
        //TODO 其他模块

        if ($type)
            $query->where('type', $type);

        $affixes = $query->createDesc()->paginate($pageSize);

        return $this->response->paginator($affixes, new AffixTransformer());

    }

    public function recycleBin(AffixQueryRequest $request, Task $task, Project $project, Star $star, Client $client, Trail $trail, Blogger $blogger)
    {
        $payload = $request->all();
        $pageSize = $request->get('page_size', config('app.page_size'));
        $type = $request->get('type');

        if ($task && $task->id) {
            $query = $task->affixes();
        } else if ($project && $project->id) {
            $query = $project->affixes();
        } else if ($star && $star->id) {
            $query = $project->affixes();
        } else if ($client && $client->id) {
            $query = $client->affixes();
        } else if ($trail && $trail->id) {
            $query = $client->affixes();
        } else if ($blogger && $blogger->id) {
            $query = $blogger->affixes();
        }
        //TODO 其他模块

        if ($type)
            $query->where('type', $type);

        $affixes = $query->onlyTrashed()->createDesc()->paginate($pageSize);

        return $this->response->paginator($affixes, new AffixTransformer());
    }

    public function add(AffixRequest $request, $model)
    {
        $payload = $request->all();
        $user = Auth::guard('api')->user();

        DB::beginTransaction();
        try {
            $affix = $this->affixRepository->addAffix($user, $model, $payload['title'], $payload['url'], $payload['size'], $payload['type']);
            if ($affix) {
                // 操作日志
                $array = [
                    'title' => null,
                    'start' => null,
                    'end' => null,
                    'method' => OperateLogMethod::UPLOAD_AFFIX,
                ];
                $array['obj'] = $this->operateLogRepository->getObject($model);
                $operate = new OperateEntity($array);
                event(new OperateLogEvent([
                    $operate,
                ]));
            }
        } catch (Exception $e) {
            dd($e);
            DB::rollBack();
            Log::error($e);
            return $this->response->errorInternal();
        }
        DB::commit();
//        return $this->response->created();
        return $this->response->item(Affix::find($affix->id), new AffixTransformer());
    }

    public function download(Request $request, $model, Affix $affix)
    {
        try {
            // 操作日志
            $array = [
                'title' => null,
                'start' => null,
                'end' => null,
                'method' => OperateLogMethod::DOWNLOAD_AFFIX,
            ];
            $array['obj'] = $this->operateLogRepository->getObject($model);
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

    public function remove(Request $request, $model, Affix $affix)
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
            $array['obj'] = $this->operateLogRepository->getObject($model);
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

    public function recoverRemove(Request $request, $model, Affix $affix)
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
            $array['obj'] = $this->operateLogRepository->getObject($task, $project, $star, $client, $trail, $blogger);
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
