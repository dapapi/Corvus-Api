<?php

namespace App\Http\Controllers;

use App\Http\Requests\TaskParticipantRequest;
use App\Models\Project;
use App\Models\Task;
use App\ModuleUserType;
use App\Repositories\ModuleUserRepository;
use Exception;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ModuleUserController extends Controller
{

    protected $moduleUserRepository;

    public function __construct(ModuleUserRepository $moduleUserRepository)
    {
        $this->moduleUserRepository = $moduleUserRepository;
    }

    public function addModuleUser(TaskParticipantRequest $request, Task $task, Project $project, $type)
    {
        $payload = $request->all();
        $participantIds = $payload['participant_ids'];

        DB::beginTransaction();
        try {
            $moduleUser = $this->moduleUserRepository->addModuleUser($participantIds, $task, $project, $type);
            if ($moduleUser) {
                //TODO 操作日志
            }
        } catch (Exception $e) {
            DB::rollBack();
            Log::error($e);
            return $this->response->errorInternal();
        }
        DB::commit();
        return $this->response->created();
    }

    public function addModuleUserParticipant(TaskParticipantRequest $request, Task $task, Project $project)
    {
        return $this->addModuleUser($request, $task, $project, ModuleUserType::PARTICIPANT);
    }

    public function removeModuleUser(TaskParticipantRequest $request, Task $task, Project $project)
    {
        $payload = $request->all();
        $participantIds = $payload['participant_ids'];
        DB::beginTransaction();
        try {
            $result = $this->moduleUserRepository->delModuleUser($participantIds, $task, $project);
            if ($result) {
                //TODO 操作日志
            }
        } catch (Exception $e) {
            DB::rollBack();
            Log::error($e);
            return $this->response->errorInternal();
        }
        DB::commit();
        return $this->response->accepted();
    }
}
