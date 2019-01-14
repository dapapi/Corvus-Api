<?php

namespace App\Http\Controllers;

use App\Events\OperateLogEvent;
use App\Http\Requests\OperateLogFollowUpRequest;
use App\Http\Transformers\OperateLogTransformer;
use App\Models\ApprovalForm\Instance;
use App\Models\Client;
use App\Models\Contract;
use App\Models\OperateEntity;
use App\Models\Project;
use App\Models\Blogger;
use App\Models\Announcement;
use App\Models\Star;
use App\Models\Report;
use App\Models\Issues;
use App\Models\Calendar;
use App\Models\Task;
use App\Models\Trail;
use App\OperateLogMethod;
use Illuminate\Support\Facades\Auth;
use App\Repositories\OperateLogRepository;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class OperateLogController extends Controller
{

    protected $operateLogRepository;

    public function __construct(OperateLogRepository $operateLogRepository)
    {
        $this->operateLogRepository = $operateLogRepository;
    }

    public function index(Request $request, Task $task, Project $project, Star $star, Trail $trail, Blogger $blogger, Report $report,Client $client,Calendar $calendar,Issues $issues,Announcement $announcement,Contract $contract)
    {

        $payload = $request->all();
        $pageSize = $request->get('page_size', config('app.page_size'));
        $status = $request->get('status', 1);

        if ($task && $task->id) {
            $query = $task->operateLogs();
        } else if ($project && $project->id) {
            $query = $project->operateLogs();
        } else if ($star && $star->id) {
            $query = $star->operateLogs();
        } else if ($trail && $trail->id) {
            $query = $trail->operateLogs();
        }else if ($blogger && $blogger->id) {
            $query = $blogger->operateLogs();
        }else if ($report && $report->id) {
            $query = $report->operateLogs();
        }else if ($issues && $issues->id) {
            $query = $issues->operateLogs();
        }else if ($client && $client->id) {
            $query = $client->operateLogs();
        }else if ($calendar && $calendar->id) {
            $query = $calendar->operateLogs();
        }else if($contract && $contract->id){
            $query = $contract->operateLogs();
        }
        //TODO 其他模块

        switch ($status) {
            case 2://不包含跟进
                $query->where('method', '!=', OperateLogMethod::FOLLOW_UP);
                break;
            case 3://只有跟进
                $query->where('method', '=', OperateLogMethod::FOLLOW_UP);
                break;
            case 1://全部
            default:
                break;
        }
        $operateLogs = $query->createDesc()->paginate($pageSize);
        foreach ($operateLogs as $operateLog) {
            if ($operateLog->method == OperateLogMethod::UPDATE_PRIVACY) {
                $operateLog->content = '!!!!!!!';
                //TODO 隐私字段裁切处理
            }
        }
        return $this->response->paginator($operateLogs, new OperateLogTransformer());
    }
    public function myIndex(Request $request, Issues $issues)
    {
        $payload = $request->all();
        $pageSize = $request->get('page_size', config('app.page_size'));
        $status = $request->get('status', 1);
        $user = Auth::guard('api')->user();
        if($issues->user_id = $user->id){
            if ($issues && $issues->user_id) {
                $query = $issues->operateLogs();
            }
        }
        //TODO 其他模块

        switch ($status) {
            case 2://不包含跟进
                $query->where('method', '!=', OperateLogMethod::FOLLOW_UP);
                break;
            case 3://只有跟进
                $query->where('method', '=', OperateLogMethod::FOLLOW_UP);
                break;
            case 1://全部
            default:
                break;
        }
        $operateLogs = $query->createDesc()->paginate($pageSize);
        foreach ($operateLogs as $operateLog) {
            if ($operateLog->method == OperateLogMethod::UPDATE_PRIVACY) {
                $operateLog->content = '!!!!!!!';
                //TODO 隐私字段裁切处理
            }
        }
        return $this->response->paginator($operateLogs, new OperateLogTransformer());
    }
    public function addFollowUp(OperateLogFollowUpRequest $request, $model)
    {
        $payload = $request->all();
        $content = $payload['content'];

        try {
            $array = [
                'title' => null,
                'start' => $content,
                'end' => null,
                'method' => OperateLogMethod::FOLLOW_UP,
            ];
            $array['obj'] = $this->operateLogRepository->getObject($model);
            $operate = new OperateEntity($array);
            event(new OperateLogEvent([
                $operate,
            ]));
        } catch (Exception $e) {
            Log::error($e);
            return $this->response->errorInternal('跟进失败');
        }

        return $this->response->created();
    }
}
