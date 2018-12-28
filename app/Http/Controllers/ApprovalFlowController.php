<?php

namespace App\Http\Controllers;

use App\Exceptions\ApprovalConditionMissException;
use App\Exceptions\ApprovalVerifyException;
use App\Http\Requests\ApprovalFlow\ApprovalTransferRequest;
use App\Http\Requests\ApprovalFlow\GetChainsRequest;
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
use App\Models\Blogger;
use App\Models\Contract;
use App\Models\DepartmentPrincipal;
use App\Models\DepartmentUser;
use App\Models\Project;
use App\Models\Star;
use App\SignContractStatus;
use App\User;
use Carbon\Carbon;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ApprovalFlowController extends Controller
{
    protected $num;

    // 拉起表单时显示的审批流程
    public function getChains(GetChainsRequest $request)
    {
        $formId = $request->get('form_id');
        if ($formId > 1000)
            $formId = hashid_decode($formId);

        $changeType = $request->get('change_type', null);
        $value = $request->get('value', null);

        $conditionId = null;

        try {
            if ($changeType == 224 && $value) {
                // 数值控件做条件的处理
                $formControlId = Condition::where('form_id', $formId)->value('form_control_id');
                $controlId = Control::where('form_control_id', $formControlId)->first()->control_id;
                if ($controlId == 83)
                    $value = $this->numberForCondition($formId, $value);

                $conditionId = $this->getCondition($formId, $value);
            }

            $chains = ChainFixed::where('form_id', $formId)
                ->where('condition_id', $conditionId)
                ->where('next_id', '!=', 0)
                ->orderBy('sort_number')
                ->get();
        } catch (ApprovalConditionMissException $exception) {
            Log::error($exception);
            return $this->response->errorBadRequest($exception->getMessage());
        } catch (Exception $exception) {
            Log::error($exception);
            return $this->response->errorInternal($exception);
        }

        return $this->response->collection($chains, new ChainTransformer());
    }

    public function storeFreeChains($chains, $formNumber)
    {
        $user = Auth::guard('api')->user();

        DB::beginTransaction();
        try {
            foreach ($chains as $key => &$chain) {
                $chain = hashid_decode($chain);
                if ($key)
                    $preId = $chains[$key - 1];
                else
                    $preId = 0;

                ChainFree::create([
                    'form_number' => $formNumber,
                    'pre_id' => $preId,
                    'next_id' => $chain,
                    'sort_number' => $key + 1
                ]);
            }
            ChainFree::create([
                'form_number' => $formNumber,
                'pre_id' => $chains[count($chains) - 1],
                'next_id' => 0,
                'sort_number' => count($chains) + 1,
            ]);
            $now = Carbon::now();

            $this->storeRecord($formNumber, $user->id, $now, 237);

            $this->createOrUpdateHandler($formNumber, $chains[0], 245, 231);
        } catch (Exception $exception) {
            DB::rollBack();
            throw $exception;
        }
        DB::commit();

        return;
    }

    // 展示整个链 已完成 当前 未审批
    public function getMergeChains(Request $request, $instance)
    {
        $num = $instance->form_instance_number;

        $array = [];
        foreach (Change::where('form_instance_number', $num)->orderBy('change_at', 'asc')->cursor() as $item) {
            $array[] = [
                'id' => hashid_encode($item->user->id),
                'name' => $item->user->name,
                'icon_url' => $item->user->icon_url,
                'change_at' => $item->change_at,
                'comment' => $item->comment,
                'change_state_obj' => [
                    'changed_state' => $item->dictionary->name,
                    'changed_icon' => $item->dictionary->icon,
                ],
                'approval_stage' => 'done',
            ];
        }

        $now = Execute::where('form_instance_number', $num)->first();
        if ($now->flow_type_id != 231)
            return $this->response->array(['data' => $array]);
        else {
            $person = $now->person;
            // todo 把主管换成人
            if ($now->current_handler_type == 246) {
                $header = $this->departmentHeaderToUser($num);
                if ($header)
                    $person = $header;
            }
            $array[] = [
                'id' => hashid_encode($person->id),
                'name' => $person->name,
                'icon_url' => $person->icon_url,
                'change_state_obj' => [
                    'changed_state' => $now->dictionary->name,
                    'changed_icon' => $now->dictionary->icon,
                ],
                'approval_stage' => 'doing'
            ];
        }

        list($nextId, $type) = $this->getChainNext($instance, $now->current_handler_id);
        if ($nextId === 0)
            return $this->response->array(['data' => $array]);

        $form = $instance->form;
        $formId = $instance->form_id;
        $condition = null;
        if ($form->change_type === 224) {
            // todo 拼value
            $formControlId = Condition::where('form_id', $formId)->first()->form_control_id;
            $value = $this->getValuesForCondition($formControlId, $num);
            $condition = $this->getCondition($formId, $value);
        }

        $nextChain = ChainFixed::where('next_id', $nextId)->where('form_id', $formId)->first();
        $chains = ChainFixed::where('form_id', $formId)
            ->where('condition_id', $condition)
            ->where('next_id', '!=', 0)
            ->where('sort_number', '>=', $nextChain->sort_number)
            ->orderBy('sort_number')
            ->get();
        if ($form->change_type === 223) {
            $nextChain = ChainFree::where('next_id', $nextId)->where('form_id', $formId)->first();
            $chains = ChainFree::where('form_number', $num)
                ->where('next_id', '!=', 0)
                ->where('sort_number', '>=', $nextChain->sort_number)
                ->orderBy('sort_number')
                ->get();
        }
        foreach ($chains as $chain) {
            $array[] = [
                'id' => hashid_encode($chain->next->id),
                'name' => $chain->next->name,
                'icon_url' => $chain->next->icon_url,
                'change_state_obj' => [
                    'changed_state' => '待审批',
                    'changed_icon' => 'icon-tongguo|#e0e0e0',
                ],
                'approval_stage' => 'todo'
            ];
        }

        return $this->response->array(['data' => $array]);
    }

    /* 流转用接口 */
    /**
     * @param Request $request
     * @param $instance
     * @return \Dingo\Api\Http\Response|void
     *
     * 1. 提交后 修改execute中的人和状态；
     * 2. 在记录表中添加一条记录
     * 3. 查对应chain表，将下一个审批人加入execute表
     */
    public function agree(Request $request, $instance)
    {
        $num = $instance->form_instance_number;

        $comment = $request->get('comment', null);

        $user = Auth::guard('api')->user();
        $userId = $user->id;

        $now = Carbon::now();

        DB::beginTransaction();
        try {
            $currentHandlerId = $this->verifyHandler($num, $userId);
            list($nextId, $type) = $this->getChainNext($this->getInstance($num), $currentHandlerId);

            $this->storeRecord($num, $userId, $now, 239, $comment);

            if ($nextId)
                $this->createOrUpdateHandler($num, $nextId, $type);
            else
                $this->createOrUpdateHandler($num, $userId, $type, 232);

        } catch (ApprovalVerifyException $exception) {
            DB::rollBack();
            return $this->response->errorForbidden($exception->getMessage());
        } catch (Exception $exception) {
            DB::rollBack();
            Log::error($exception);
            return $this->response->errorInternal('审批失败');
        }
        DB::commit();
        return $this->response->created();
    }

    public function refuse(Request $request, $instance)
    {
        $num = $instance->form_instance_number;

        $comment = $request->get('comment', null);

        $user = Auth::guard('api')->user();
        $userId = $user->id;

        $now = Carbon::now();

        DB::beginTransaction();
        try {
            $this->verifyHandler($num, $userId);
            $this->storeRecord($num, $userId, $now, 240, $comment);

            $this->createOrUpdateHandler($num, $userId, 245, 233);
        } catch (ApprovalVerifyException $exception) {
            DB::rollBack();
            return $this->response->errorForbidden($exception->getMessage());
        } catch (Exception $exception) {
            DB::rollBack();
            Log::error($exception);
            return $this->response->errorInternal('审批失败');
        }
        DB::commit();
        return $this->response->created();
    }

    public function transfer(ApprovalTransferRequest $request, $instance)
    {
        $num = $instance->form_instance_number;

        $comment = $request->get('comment', null);

        $nextId = $request->get('next_id');

        $nextId = hashid_decode($nextId);
        if (is_null(User::find($nextId)))
            return $this->response->errorBadRequest('转交人不存在');

        $user = Auth::guard('api')->user();
        $userId = $user->id;

        $now = Carbon::now();

        DB::beginTransaction();
        try {
            $this->verifyHandler($num, $userId);
            $this->storeRecord($num, $userId, $now, 241, $comment);

            $this->createOrUpdateHandler($num, $nextId, 245);
        } catch (ApprovalVerifyException $exception) {
            DB::rollBack();
            return $this->response->errorForbidden($exception->getMessage());
        } catch (Exception $exception) {
            DB::rollBack();
            Log::error($exception);
            return $this->response->errorInternal('审批失败');
        }
        DB::commit();
        return $this->response->created();
    }

    public function cancel(Request $request, $instance)
    {
        $num = $instance->form_instance_number;

        $user = Auth::guard('api')->user();
        $userId = $user->id;

        $instance = $this->getInstance($num);

        list($nextId, $type) = $this->getChainNext($instance, 0);

        $currentStatus = Execute::where('form_instance_number', $num)->first();
        if ($currentStatus->flow_type_id != 231 || $currentStatus->current_handler_id != $nextId) {
            return $this->response->errorForbidden('审批流程已开始，已无法取消');
        }

        $comment = $request->get('comment', null);

        $now = Carbon::now();

        DB::beginTransaction();
        try {
            $this->storeRecord($num, $userId, $now, 242, $comment);

            $this->createOrUpdateHandler($num, $userId, 245, 234);

            $project = Project::where('project_number', $num)->first();
            if ($project)
                $project->delete();
        } catch (Exception $exception) {
            DB::rollBack();
            Log::error($exception);
            return $this->response->errorInternal('审批失败');
        }
        DB::commit();
        return $this->response->created();
    }

    public function discard(Request $request, $instance)
    {
        $num = $instance->form_instance_number;

        $user = Auth::guard('api')->user();
        $userId = $user->id;

        $currentStatus = Execute::where('form_instance_number', $num)->first();
        if ($currentStatus->flow_type_id == 231 or $currentStatus->flow_type_id == 235) {
            return $this->response->errorForbidden('审批流程未结束，不可作废');
        }

        $comment = $request->get('comment', null);

        $now = Carbon::now();

        DB::beginTransaction();
        try {
            $this->storeRecord($num, $userId, $now, 243, $comment);

            $this->createOrUpdateHandler($num, $userId, 245, 235);
        } catch (Exception $exception) {
            DB::rollBack();
            Log::error($exception);
            return $this->response->errorInternal('审批失败');
        }
        DB::commit();
        return $this->response->created();
    }

    /* 流转用辅助方法 */

    private function getInstance($num)
    {
        $instance = Instance::where('form_instance_number', $num)->first();
        if (!$instance)
            $instance = Business::where('form_instance_number', $num)->first();

        if (!$instance)
            throw new Exception('该number未匹配到审批');

        return $instance;
    }

    /**
     * @param $instance
     * @param $preId
     * @return array [int $nextId, int $type]
     * @throws Exception
     */
    private function getChainNext($instance, $preId)
    {
        $form = ApprovalForm::where('form_id', $instance->form_id)->first();

        // todo preId找不到时
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
            $formControlIds = Condition::where('form_id', $formId)->value('form_control_id');
            $value = $this->getValuesForCondition($formControlIds, $num);
            $conditionId = $this->getCondition($instance->form_id, $value);
            $chain = ChainFixed::where('form_id', $instance->form_id)->where('pre_id', $preId)->where('condition_id', $conditionId)->first();
        } else {
            throw new Exception('审批流转不存在');
        }
        if (is_null($chain)) {
            $now = Carbon::now();

            return $this->getTransferNextChain($instance, $now);
        }
        if ($chain->next_id === 0)
            return [0, 245];

        $next = $chain->next;

        $type = $chain->approver_type;
        if (is_null($type))
            $type = 245;

        return [$next->id, $type];
    }

    private function getTransferNextChain($instance, $dateTime)
    {
        $num = $instance->form_instance_number;
        $count = Change::where('form_instance_number', $num)->where('change_state', '!=', 241)->count('form_instance_number');

        $form = $instance->form;
        if ($form->change_type == 223) {
            $preId = ChainFree::where('form_number', $num)->where('sort_number', $count)->value('next_id');
        } else if ($form->change_type == 222) {
            $preId = ChainFixed::where('form_id', $form->form_id)->where('sort_number', $count)->value('next_id');
        } else if ($form->change_type == 224) {
            $formControlIds = Condition::where('form_id', $form->form_id)->value('form_control_id');
            $value = $this->getValuesForCondition($formControlIds, $num);
            $conditionId = $this->getCondition($instance->form_id, $value);
            $preId = ChainFixed::where('form_id', $form->form_id)->where('condition_id', $conditionId)->where('sort_number', $count)->value('next_id');
        }

        $arr = $this->getChainNext($instance, $preId);

        return $arr;
    }

    /**
     * @param $formId
     * @param $value
     * @return $conditionId
     * @throws Exception
     */
    private function getCondition($formId, $value)
    {
        $result = Condition::where('form_id', $formId)->where('condition', $value)->value('flow_condition_id');
        if (is_null($result))
            throw new ApprovalConditionMissException('未找到对应条件');

        return $result;

    }

    private function createOrUpdateHandler($num, $nextId, $type, $status = 231)
    {
        try {
            $execute = Execute::where('form_instance_number', $num)->first();
            if ($execute)
                $execute->update([
                    'current_handler_id' => $nextId,
                    'current_handler_type' => $type,
                    'flow_type_id' => $status,
                ]);
            else
                Execute::create([
                    'form_instance_number' => $num,
                    'current_handler_id' => $nextId,
                    'current_handler_type' => $type,
                    'flow_type_id' => $status,
                ]);

            // 审批流程:4.结束后改实例最终状态
            if ($status != 231) {
                $instance = $this->getInstance($num);
                $instance->form_status = $status;
                $instance->save();
                $this->changeRelateStatus($instance, $status);
            }
        } catch (Exception $exception) {
            throw $exception;
        }
    }

    private function storeRecord($num, $userId, $dateTime, $status, $comment = null)
    {
        try {
            $record = Change::create([
                'form_instance_number' => $num,
                'change_id' => $userId,
                'change_at' => $dateTime,
                'change_state' => $status,
                'comment' => $comment
            ]);
        } catch (Exception $exception) {
            throw $exception;
        }

        return $record;
    }

    private function verifyHandler($num, $userId)
    {
        $now = Execute::where('form_instance_number', $num)->first();
        if ($now->flow_type_id != 231)
            throw new ApprovalVerifyException('流程不正确');

        $now->cuttent_handler_type;
        if ($now->current_handler_id != $userId) {
            $user = User::find($userId);
            $role = $user->roles()->where('role_id', $now->current_handler_id)->first();
            if (is_null($role))
                throw new ApprovalVerifyException('当前用户没权限进行该操作');
        }

        return $now->current_handler_id;
    }

    private function getValuesForCondition($formControlIds, $num, $value = null)
    {
        $formControlIdArr = explode(',', $formControlIds);
        if (count($formControlIdArr) == 1) {
            $control = Control::where('form_control_id', $formControlIdArr[0])->first();
            $value = InstanceValue::where('form_instance_number', $num)->where('form_control_id', $control->form_control_id)->value('form_control_value');
            if ($control->form_control_id == 83)
                return $this->numberForCondition($control->form_id, $value);
        }

        $resultArr = [];
        foreach ($formControlIdArr as $control) {
            $resultArr[] = InstanceValue::where('form_instance_number', $num)->where('form_control_id', $control)->value('form_control_value');
        }
        return implode(',', $resultArr);
    }

    private function numberForCondition($formId, $value)
    {
        $result = 0;
        foreach (Condition::where('form_id', $formId)->orderBy('sort_number', 'desc')->cursor() as $item) {
            if ($value > $item->condition) {
                $result = $item->condition;
                break;
            } else {
                continue;
            }
        }
        return $result;
    }

    private function departmentHeaderToUser($num)
    {
        $creatorId = Change::where('form_instance_number', $num)->where('change_state', 237)->value('change_id');
        $departmentId = DepartmentUser::where('user_id', $creatorId)->value('department_id');
        $headerId = DepartmentPrincipal::where('department_id', $departmentId)->value('user_id');

        if ($headerId)
            return User::find($headerId);
        else
            return null;
    }

    private function changeRelateStatus($instance, $status)
    {
        $num = $instance->form_instance_number;
        $contract = Contract::where('form_instance_number', $num)->first();
        if (is_null($contract))
            return null;

        if ($contract->star_type && $status == 232) {
            $starArr = explode(',', $contract->stars);
            if (in_array($instance->form_id, [5, 7]))
                DB::table($contract->star_type)->whereIn('id', $starArr)->update(['sign_contract_status' => SignContractStatus::ALREADY_SIGN_CONTRACT]);

            if (in_array($instance->form_id, [6, 8]))
                DB::table($contract->star_type)->whereIn('id', $starArr)->update(['sign_contract_status' => SignContractStatus::ALREADY_TERMINATE_AGREEMENT]);
        }

        if ($contract->project_id) {
            if ($status != 232)
                $contract->project->delete();
        }
    }
}
