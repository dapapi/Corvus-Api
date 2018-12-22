<?php

namespace App\Http\Controllers;

use App\Events\CommentLogEvent;
use App\Http\Requests\CommentLogFollowUpRequest;
use App\Http\Transformers\CommentLogTransformer;
use App\Models\CommentEntity;
use App\Models\repository;
use App\CommentLogMethod;
use App\ModuleableType;
use App\Models\CommentLog;
use Illuminate\Support\Facades\Auth;
use App\Repositories\OperateLogRepository;
use App\Repositories\CommentLogRepository;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class CommentLogController extends Controller
{

    protected $commentLogRepository;

    public function __construct(CommentLogRepository $commentLogRepository)
    {
        $this->commentLogRepository = $commentLogRepository;
    }

    public function index(Request $request,Repository $repository)
    {
        $payload = $request->all();
        $pageSize = $request->get('page_size', config('app.page_size'));
        $status = $request->get('status', 1);
       if ($repository && $repository->id) {
            $query = $repository->CommentLogs();
        }
        //TODO 其他模块

        switch ($status) {
            case 2://不包含跟进
                $query->where('method', '!=', CommentLogMethod::SHOW_COMMENT);
                break;
            case 3://只有跟进
                $query->where('method', '=', CommentLogMethod::SHOW_COMMENT);
                break;
            case 1://全部
            default:
                break;
        }

        $commentLogs = $query->where('parent_id', 0)->createDesc()->paginate($pageSize);
        foreach ($commentLogs as $commentLog) {
            if ($commentLog->method == CommentLogMethod::UPDATE_PRIVACY) {
                $commentLog->content = '!!!!!!!';
                //TODO 隐私字段裁切处理
            }
        }
        return $this->response->paginator($commentLogs, new CommentLogTransformer());
    }
    public function addaddComment(CommentLogFollowUpRequest $request, $model,CommentLog $commentlog)
    {

        $payload = $request->all();
        $content = $payload['content'];

        try {
            $array = [
                'title' => null,
                'start' => $content,
                'end' => null,
                'method' => CommentLogMethod::ADD_COMMENT,
            ];
            $array['obj'] = $this->commentLogRepository->getObject($model);
            $operate = new CommentEntity($array);
            $user = Auth::guard('api')->user();
            if (!$user) {
                abort(401);
            }
            $id = $operate->obj->id;
            if($operate->obj instanceof Repository){
                $type = ModuleableType::REPOSITORY;
                $typeName = '知识库';
            }
//            $title = $operate->title;
//            $start = $operate->start;
//            $end = $operate->end;
            $level = 0;

            CommentLog::create([
                'user_id' => $user->id,
                'parent_id' => $commentlog->id,
                'Pipe' => empty($commentlog->Pipe)?$commentlog->id:$commentlog->Pipe.'-'.$commentlog->id,
                'logable_id' => $id,
                'logable_type' => $type,
                'content' => $content,
                'method' => $operate->method,
                'level' => $level,
                'status' => 1,
            ]);
//            event(new CommentLogEvent([
//                $operate,
//            ]));
        } catch (Exception $e) {
            Log::error($e);
            return $this->response->errorInternal('评论失败');
        }

        return $this->response->created();
    }
    public function addComment(CommentLogFollowUpRequest $request, $model)
    {
        $payload = $request->all();
        $content = $payload['content'];

        try {
            $array = [
                'title' => null,
                'start' => $content,
                'end' => null,
                'method' => CommentLogMethod::ADD_COMMENT,
            ];
            $array['obj'] = $this->commentLogRepository->getObject($model);
            $operate = new CommentEntity($array);
            $user = Auth::guard('api')->user();
            if (!$user) {
                abort(401);
            }
            $id = $operate->obj->id;
            if($operate->obj instanceof Repository){
                $type = ModuleableType::REPOSITORY;
                $typeName = '知识库';
            }
//            $title = $operate->title;
//            $start = $operate->start;
//            $end = $operate->end;
            $level = 0;
            CommentLog::create([
                'user_id' => $user->id,
                'logable_id' => $id,
                'logable_type' => $type,
                'content' => $content,
                'method' => $operate->method,
                'level' => $level,
                'status' => 1,
            ]);
//            event(new CommentLogEvent([
//                $operate,
//            ]));
        } catch (Exception $e) {
            Log::error($e);
            return $this->response->errorInternal('评论失败');
        }

        return $this->response->created();
    }
}
