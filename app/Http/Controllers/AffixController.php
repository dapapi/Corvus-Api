<?php

namespace App\Http\Controllers;

use App\Repositories\AffixRepository;
use Illuminate\Http\Request;

class AffixController extends Controller
{
    protected $affixRepository;

    public function __construct(AffixRepository $affixRepository)
    {
        $this->affixRepository = $affixRepository;
    }

    public function addTaskAffix(TaskParticipantRequest $request, Task $task)
    {
        $payload = $request->all();
        $participantIds = $payload['participant_ids'];

        DB::beginTransaction();
        try {
            $moduleUser = $this->affixRepository->addTaskAffix($participantIds, $task, ModuleUserType::PARTICIPANT);
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
}
