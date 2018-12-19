<?php

namespace App\Http\Controllers;

use App\Http\Requests\ApprovalFlow\ApprovalRequest;
use App\Http\Requests\ApprovalFlow\GetChainsRequest;
use App\Http\Requests\ApprovalFlow\StoreFreeChainsRequest;
use App\Http\Transformers\ChainTransformer;
use App\Models\ApprovalFlow\ChainFixed;
use App\Models\ApprovalFlow\ChainFree;
use App\Models\ApprovalFlow\Change;
use App\Models\ApprovalFlow\Condition;
use App\Models\ApprovalFlow\Execute;
use App\Models\ApprovalForm\ApprovalForm;
use App\Models\ApprovalForm\Business;
use App\Models\ApprovalForm\Control;
use App\Models\ApprovalForm\Instance;
use App\Models\ApprovalForm\InstanceValue;
use App\Models\Department;
use App\Models\DepartmentUser;
use App\User;
use Carbon\Carbon;
use Exception;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class ApprovalFlowController extends Controller
{
    protected $num;

    // 拉起表单时显示的审批流程
    public function getChains(GetChainsRequest $request, ApprovalForm $approval)
    {
        $formId = $approval->form_id;

        $controlId = $request->get('control_id', null);
        $changeType = $request->get('change_type', null);
        $value = $request->get('value', null);

        $conditionId = null;

        if ($changeType === 224 && $controlId && $value)
            $conditionId = $this->getCondition($formId, $controlId, $value);

        $chains = ChainFixed::where('form_id', $formId)
            ->where('condition_id', $conditionId)
            ->where('next_id', '!=', 0)
            ->orderBy('sort_number')
            ->get();


        return $this->response->collection($chains, new ChainTransformer());

    }

    public function storeFreeChains(StoreFreeChainsRequest $request, $formNumber)
    {
        $chains = $request->get('chains');

        $user = Auth::guard('api')->user();

        DB::beginTransaction();
        try {
            foreach ($chains as $key => &$chain) {
                $chain['id'] = hashid_decode($chain['id']);
                if ($key)
                    $preId = $chains[$key - 1]['id'];
                else
                    $preId = 0;

                ChainFree::create([
                    'form_number' => $formNumber,
                    'pre_id' => $preId,
                    'next_id' => $chain['id'],
                    'sort_number' => $key + 1
                ]);
            }
            ChainFree::create([
                'form_number' => $formNumber,
                'pre_id' => $chains[count($chains) - 1]['id'],
                'next_id' => 0,
                'sort_number' => count($chains) + 1,
            ]);
            $now = Carbon::now();

            $this->storeRecord($formNumber, $user->id, $now, 237);
            $this->storeRecord($formNumber, $chains[0]['id'], $now, 238);

            $this->createOrUpdateHandler($formNumber, $chains[0]['id'], 231);
        } catch (Exception $exception) {
            DB::rollBack();
            throw $exception;
        }
        DB::commit();

        return;
    }

    // 1. 提交后 修改execute中的人和状态；
    // 2. 在记录表中添加一条记录
    // 3. 查对应chain表，将下一个审批人加入execute表
    public function agree(ApprovalRequest $request)
    {
        $num = $request->get('number');

        $user = Auth::guard('api')->user();
        $userId = $user->id;

        $now = Carbon::now();

        DB::beginTransaction();
        try {
            $nextId = $this->getChainNext($this->getInstance($num), $userId);
            $this->storeRecord($num, $userId, $now, 239);

            if ($nextId)
                $this->createOrUpdateHandler($num, $nextId);
            else
                $this->createOrUpdateHandler($num, $userId, 232);

        } catch (Exception $exception) {
            DB::rollBack();
            return $this->response->errorInternal('审批失败');
        }
        DB::commit();
        return $this->response->created();
    }

    public function reject(ApprovalRequest $request)
    {
        $num = $request->get('number');

        $user = Auth::guard('api')->user();
        $userId = $user->id;

        $now = Carbon::now();

        DB::beginTransaction();
        try {
            $this->storeRecord($num, $userId, $now, 240);

            $this->createOrUpdateHandler($num, $userId, 233);
        } catch (Exception $exception) {
            DB::rollBack();
            return $this->response->errorInternal('审批失败');
        }
        DB::commit();
        return $this->response->created();
    }

    public function transfer(ApprovalRequest $request)
    {
        $num = $request->get('number');

        $nextId = $request->get('next_id', null);
        if (!is_numeric($nextId))
            return $this->response->errorBadRequest('未填写转交人');

        $nextId = hashid_decode($nextId);
        if (is_null(User::find($nextId)))
            return $this->response->errorBadRequest('转交人不存在');

        $user = Auth::guard('api')->user();
        $userId = $user->id;

        $now = Carbon::now();

        DB::beginTransaction();
        try {
            $this->storeRecord($num, $userId, $now, 241);

            $this->createOrUpdateHandler($num, $nextId);
        } catch (Exception $exception) {
            DB::rollBack();
            return $this->response->errorInternal('审批失败');
        }
        DB::commit();
        return $this->response->created();
    }

    public function cancel(ApprovalRequest $request)
    {
        $num = $request->get('number');

        $user = Auth::guard('api')->user();
        $userId = $user->id;

        $instance = $this->getInstance($num);

        $nextId = $this->getChainNext($instance, 0);

        $currentStatus = Execute::where('form_instance_number', $num)->first();
        if ($currentStatus->flow_type_id != 231 || $currentStatus->current_handler_id != $nextId) {
            return $this->response->errorForbidden('审批流程已开始，已无法取消');
        }

        $now = Carbon::now();

        DB::beginTransaction();
        try {
            $this->storeRecord($num, $userId, $now, 234);

            $this->createOrUpdateHandler($num, $userId, 242);

        } catch (Exception $exception) {
            DB::rollBack();
            return $this->response->errorInternal('审批失败');
        }
        DB::commit();
        return $this->response->created();
    }

    public function discard(ApprovalRequest $request)
    {
        $num = $request->get('number');

        $user = Auth::guard('api')->user();
        $userId = $user->id;

        $currentStatus = Execute::where('form_instance_number', $num)->first();
        if ($currentStatus->flow_type_id == 231 or $currentStatus->flow_type_id == 235) {
            return $this->response->errorForbidden('审批流程未开始，不可作废');
        }

        $now = Carbon::now();

        DB::beginTransaction();
        try {
            $this->storeRecord($num, $userId, $now, 235);

            $this->createOrUpdateHandler($num, $userId, 235);
        } catch (Exception $exception) {
            DB::rollBack();
            return $this->response->errorInternal('审批失败');
        }
        DB::commit();
        return $this->response->created();
    }

    private function getInstance($num)
    {
        $instance = Instance::where('form_instance_number', $num)->first();
        if (!$instance)
            $instance = Business::where('form_instance_number', $num)->first();

        if (!$instance)
            throw new Exception('该number未匹配到审批');

        return $instance;
    }

    private function getChainNext($instance, $preId)
    {
        $form = ApprovalForm::where('form_id', $instance->form_id)->first();
        if (!$form)
            throw new Exception('form不存在');

        $formId = $form->form_id;
        $num = $instance->form_instance_number;
        $changeType = $form->change_type;

        if ($changeType == 222) {
            // 固定流程
            $chain = ChainFixed::where('form_id', $formId)->where('pre_id', $preId)->first();
        } else if ($changeType == 223) {
            // 自由流程
            $chain = ChainFree::where('form_number', $num)->where('pre_id', $preId)->first();
        } else if ($changeType === 224) {
            // 分支流程
            $formControlId = Condition::where('form_id', $formId)->first()->form_control_id;
            $value = InstanceValue::where('form_instance_number', $num)->where('form_control_id', $formControlId)->select('form_control_value')->first()->form_control_value;
            $conditionId = $this->getCondition($instance->form_id, $value);
            $chain = ChainFixed::where('form_id', $instance->form_id)->where('pre_id', $preId)->where('condition_id', $conditionId)->first();
        } else {
            throw new Exception('审批流转不存在');
        }

        if (is_null($chain)) {
            $now = Carbon::now();

            $this->getTransferNextChain($instance, $now);
        }

        if ($chain->approver_type == 245) {
            $user = Auth::guard('api')->user();
            $department = $user->department()->first();
            $departmentHead = DepartmentUser::where('department_id', $department->id)->where('type', 1)->first();

            $nextId = $departmentHead->id;
        } else {
            $nextId = $chain->next->id;
        }

        return $nextId;
    }

    private function getTransferNextChain($instance, $dateTime)
    {
        $lastRecord = Change::where('form_instance_number', $instance->form_instance_number)->where('change_at', '<', $dateTime)->orderBy('change_at', 'desc')->first();
        $nextId = $this->getChainNext($instance, $lastRecord->change_id);

        return $nextId;
    }

    /**
     * @param $formId
     * @param $value
     * @return $conditionId
     * @throws Exception
     */
    private function getCondition($formId, $value)
    {
        $formControl = Control::where('form_id', $formId)->first();
        $arr = [
            82,
            84
        ];
        if (in_array($formControl->control_id, $arr)) {
            $condition = Condition::where('form_id', $formId)->where('form_control_id', $formControl->control_id)->where('condition', $value)->first();
        } else if ($formControl->control_id === 83) {
            $condition = null;
            foreach (Condition::where('form_id', $formId)->where('form_control_id', $formControl->form_control_id)->orderBy('condition', 'asc')->cursor() as $item) {
                if ($value > $item->condition)
                    continue;
                else {
                    $condition = $item;
                    break;
                }
            }

            if (is_null($condition))
                $condition = Condition::where('form_id', $formId)->where('form_control_id', $formControl->form_control_id)->orderBy('condition', 'desc')->first();
        } else {
            throw new Exception('该字段类型不在可配置分支条件中');
        }

        return $condition->flow_condition_id;

    }

    private function createOrUpdateHandler($num, $userId, $status = 231)
    {
        try {
            $execute = Execute::where('form_instance_number', $num)->first();
            if ($execute)
                $execute->update([
                    'current_handler_id' => $userId,
                    'flow_type_id' => $status,
                ]);
            else
                Execute::create([
                    'form_instance_number' => $num,
                    'current_handler_id' => $userId,
                    'flow_type_id' => $status,
                ]);

            // 审批流程:4.结束后改实例最终状态
            if ($status != 231) {
                $instance = $this->getInstance($num);
                $instance->form_status = $status;
                $status->save();
            }
        } catch (Exception $exception) {
            throw $exception;
        }
    }

    private function storeRecord($num, $userId, $dateTime, $status)
    {
        try {
            $record = Change::create([
                'form_instance_number' => $num,
                'change_id' => $userId,
                'change_at' => $dateTime,
                'change_status' => $status,
            ]);
        } catch (Exception $exception) {
            throw $exception;
        }

        return $record;
    }
}
