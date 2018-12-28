<?php

namespace App\Http\Controllers;

use App\Events\OperateLogEvent;
use App\Http\Requests\ModuleUserRequest;
use App\Models\Blogger;
use App\Models\Client;
use App\Models\ModuleUser;
use App\Models\OperateEntity;
use App\Models\Project;
use App\Models\Calendar;
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
use Illuminate\Http\Request;
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

    public function add(ModuleUserRequest $request, $model, $type)
    {
        $payload = $request->all();
        if (!$request->has('person_ids') && !$request->has('del_person_ids'))
            return $this->response->noContent();

        $participantIds = $request->get('person_ids', []);//参与人或宣传人ID数组
        $participantDeleteIds = $request->get('del_person_ids', []);//参与人或宣传人删除ID数组

        DB::beginTransaction();
        try {
            $result = $this->moduleUserRepository->addModuleUser($participantIds, $participantDeleteIds, $model, $type);
            $participantIds = $result[0];
            $participantDeleteIds = $result[1];

            // 操作日志 $type类型参与人或者宣传人
            $title = $this->moduleUserRepository->getTypeName($type);
            if (count($participantIds)) {
                $start = '';
                foreach ($participantIds as $key => $participantId) {
                    try {
                        $participantUser = User::findOrFail($participantId);//查询出所有经纪人或者宣传人
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
                $array['obj'] = $this->operateLogRepository->getObject($model);
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
                $array['obj'] = $this->operateLogRepository->getObject($model);
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
    public function addModuleUserParticipant(ModuleUserRequest $request, $model)
    {
        return $this->add($request, $model, ModuleUserType::PARTICIPANT);
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
    public function addModuleUserPublicity(ModuleUserRequest $request, $model)
    {
        return $this->add($request, $model, ModuleUserType::PUBLICITY);
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
    public function addModuleUserBroker(ModuleUserRequest $request, $model)
    {
        return $this->add($request, $model, ModuleUserType::BROKER);
    }

    public function remove(ModuleUserRequest $request, $model)
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
                        if ($model instanceof Task && $model->id) {
                            $moduleableId = $model->id;
                            $moduleableType = ModuleableType::TASK;
                        } else if ($model instanceof Project && $model->id) {
                            $moduleableId = $model->id;
                            $moduleableType = ModuleableType::PROJECT;
                        } else if ($model instanceof Star && $model->id) {
                            $moduleableId = $model->id;
                            $moduleableType = ModuleableType::STAR;
                        }else if ($model instanceof Calendar && $model->id) {
                            $moduleableId = $model->id;
                            $moduleableType = ModuleableType::CALENDAR;
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
                $array['obj'] = $this->operateLogRepository->getObject($model);
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
    //为多个多个博主或艺人添加多个经纪人或宣传人或制作人
    public function addMore(Request $request)
    {
//        $person_ids, $del_person_ids, $moduleable_ids,$moduleable_type, $type
        $person_ids = $request->get("person_ids",[]);
        $del_person_ids = $request->get('del_person_ids',[]);
        $moduleable_ids = $moduleable_ids = $request->get('moduleable_ids',[]);
        $moduleable_type = $request->get('moduleable_type',null);
        $type = $request->get('type',null);
        DB::beginTransaction();
        try{
            // 操作日志 $type类型参与人或者宣传人
            $title = $this->moduleUserRepository->getTypeName($type);
            $result = $this->moduleUserRepository->addModuleUsers($person_ids,$del_person_ids,$moduleable_ids,$moduleable_type,$type);
            $add_persons = $result[0];
            $del_persons = $result[1];
            foreach ($add_persons as $module_id=>$person_ids){
                $model = $this->moduleUserRepository->getModule($module_id,$moduleable_type);
                $start = "";
                foreach ($person_ids as $person_id){
                    try {
                        $person = User::findOrFail($person_id);
                        $start .= $person->name . ' ';
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
                $array['obj'] = $this->operateLogRepository->getObject($model);
                $operate = new OperateEntity($array);
                event(new OperateLogEvent([
                    $operate,
                ]));

            }
            foreach ($del_persons as $module_id=>$del_person_ids){
                $model = $this->moduleUserRepository->getModule($module_id,$moduleable_type);
                // 操作日志
                $start = '';
                foreach ($del_person_ids as $moduleable_id => $del_person_id) {
                    try {
                        $del_person  = User::findOrFail($del_person_id);
                        $start .= $del_person->name . ' ';
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
                $array['obj'] = $this->operateLogRepository->getObject($model);
                $operate = new OperateEntity($array);
                event(new OperateLogEvent([
                    $operate,
                ]));
            }
        }catch (Exception $e){
            DB::rollBack();
            Log::error($e);
            return $this->response->errorInternal();
        }
        DB::commit();
        return $this->response->accepted();

    }
}
