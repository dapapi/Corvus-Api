<?php

namespace App\Http\Controllers;

use App\Events\OperateLogEvent;
use App\Http\Requests\ModuleUserRequest;
use App\Models\Blogger;
use App\Models\Client;
use App\Models\ModuleUser;
use App\Models\OperateEntity;
use App\Models\Project;
use App\Models\Star;
use App\Models\Task;
use App\Models\Trail;
use App\ModuleableType;
use App\ModuleUserType;
use App\OperateLogMethod;
use App\Repositories\ModuleUserRepository;
use App\Repositories\OperateLogRepository;
use App\User;
use Exception;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ModuleUserController extends Controller
{

    protected $moduleUserRepository;
    protected $operateLogRepository;

    public function __construct(ModuleUserRepository $moduleUserRepository, OperateLogRepository $operateLogRepository)
    {
        $this->moduleUserRepository = $moduleUserRepository;
        $this->operateLogRepository = $operateLogRepository;
    }

    public function add(ModuleUserRequest $request, Task $task, Project $project, Star $star, Client $client, Trail $trail, Blogger $blogger, $type)
    {
        $payload = $request->all();

        if (!$request->has('person_ids') && !$request->has('del_person_ids'))
            return $this->response->noContent();

        $participantIds = $request->get('person_ids', []);//参与人或宣传人ID数组
        $participantDeleteIds = $request->get('del_person_ids', []);//参与人或宣传人删除ID数组

        DB::beginTransaction();
        try {
            $result = $this->moduleUserRepository->addModuleUser($participantIds, $participantDeleteIds, $task, $project, $star, $type);
            $participantIds = $result[0];
            $participantDeleteIds = $result[1];

            // 操作日志 $type类型参与人或者宣传人
            $title = $this->moduleUserRepository->getTypeName($type);
            if (count($participantIds)) {
                $start = '';
                foreach ($participantIds as $key => $participantId) {
                    try {
                        $participantUser = User::findOrFail($participantId);
                        $start .= $participantUser->name . ' ';
                    } catch (Exception $e) {
                    }
                }
                $start = substr($start, 0, strlen($start) - 1);

                $array = [
                    'title' => $title,
                    'start' => $start,
                    'end' => null,
                    'method' => OperateLogMethod::ADD_PERSON,
                ];
                $array['obj'] = $this->operateLogRepository->getObject($task, $project, $star, $client, $trail, $blogger);
                $operate = new OperateEntity($array);
                event(new OperateLogEvent([
                    $operate,
                ]));
            }
            //要求一个接口可以完成添加人和删除人,已经存在的删除
            if (count($participantDeleteIds)) {
                // 操作日志
                $start = '';
                foreach ($participantDeleteIds as $key => $participantId) {
                    try {
                        $participantUser = User::findOrFail($participantId);
                        $start .= $participantUser->name . ' ';
                    } catch (Exception $e) {
                    }
                }
                $start = substr($start, 0, strlen($start) - 1);
                $array = [
                    'title' => $title,
                    'start' => $start,
                    'end' => null,
                    'method' => OperateLogMethod::DEL_PERSON,
                ];
                $array['obj'] = $this->operateLogRepository->getObject($task, $project, $star, $client, $trail, $blogger);
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
        return $this->response->accepted();
    }

    /**
     * 参与人
     *
     * @param ModuleUserRequest $request
     * @param Task $task
     * @param Project $project
     * @param Star $star
     * @return \Dingo\Api\Http\Response|void
     */
    public function addModuleUserParticipant(ModuleUserRequest $request, Task $task, Project $project, Star $star, Client $client, Trail $trail, Blogger $blogger)
    {
        return $this->add($request, $task, $project, $star, $client, $trail, $blogger, ModuleUserType::PARTICIPANT);
    }

    /**
     * 宣传人
     *
     * @param ModuleUserRequest $request
     * @param Task $task
     * @param Project $project
     * @param Star $star
     * @return \Dingo\Api\Http\Response|void
     */
    public function addModuleUserPublicity(ModuleUserRequest $request, Task $task, Project $project, Star $star, Client $client, Trail $trail, Blogger $blogger)
    {
        return $this->add($request, $task, $project, $star, $client, $trail, $blogger, ModuleUserType::PUBLICITY);
    }

    /**
     * 分配经纪人
     *
     * @param ModuleUserRequest $request
     * @param Task $task
     * @param Project $project
     * @param Star $star
     * @return \Dingo\Api\Http\Response|void
     */
    public function addModuleUserBroker(ModuleUserRequest $request, Task $task, Project $project, Star $star, Client $client, Trail $trail, Blogger $blogger)
    {
        return $this->add($request, $task, $project, $star, $client, $trail, $blogger, ModuleUserType::BROKER);
    }

    public function remove(ModuleUserRequest $request, Task $task, Project $project, Star $star, Client $client, Trail $trail, Blogger $blogger)
    {
        $payload = $request->all();
        $participantIds = $payload['person_ids'];
        DB::beginTransaction();
        try {

            $start = '';
            $type = ModuleUserType::PARTICIPANT;
            {//删除
                $participantIds = array_unique($participantIds);
                foreach ($participantIds as $key => &$participantId) {
                    try {
                        $participantId = hashid_decode($participantId);
                        $participantUser = User::findOrFail($participantId);
                        $start .= $participantUser->name . ' ';
                    } catch (Exception $e) {
                        array_splice($participantIds, $key, 1);
                    }
                    if ($participantUser) {
                        $moduleableId = 0;
                        $moduleableType = null;
                        if ($task && $task->id) {
                            $moduleableId = $task->id;
                            $moduleableType = ModuleableType::TASK;
                        } else if ($project && $project->id) {
                            $moduleableId = $project->id;
                            $moduleableType = ModuleableType::PROJECT;
                        } else if ($star && $star->id) {
                            $moduleableId = $star->id;
                            $moduleableType = ModuleableType::STAR;
                        }
                        // TODO 还有其他类型

                        $moduleUser = ModuleUser::where('moduleable_type', $moduleableType)->where('moduleable_id', $moduleableId)->where('user_id', $participantUser->id)->first();
                        if ($moduleUser) {
                            $type = $moduleUser->type;
                            $moduleUser->delete();
                        } else {
                            array_splice($participantIds, $key, 1);
                        }
                    }
                }
            }

            if (count($participantIds)) {
                // 操作日志
                $title = $this->moduleUserRepository->getTypeName($type);

                $start = substr($start, 0, strlen($start) - 1);

                $array = [
                    'title' => $title,
                    'start' => $start,
                    'end' => null,
                    'method' => OperateLogMethod::DEL_PERSON,
                ];
                $array['obj'] = $this->operateLogRepository->getObject($task, $project, $star, client, $trail, $blogger);
                $operate = new OperateEntity($array);
                event(new OperateLogEvent([
                    $operate,
                ]));
            }
        } catch (Exception $e) {
            DB::rollBack();
            Log::error($e);
            return $this->response->errorInternal('移除错误');
        }
        DB::commit();
        return $this->response->accepted();
    }
}
