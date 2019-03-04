<?php

namespace App\Http\Controllers;

use App\Events\ApprovalMessageEvent;
use App\Events\BloggerMessageEvent;
use App\Events\ClientMessageEvent;
use App\Events\OperateLogEvent;
use App\Events\StarMessageEvent;
use App\Exceptions\ApprovalConditionMissException;
use App\Exceptions\ApprovalVerifyException;
use App\Http\Requests\ApprovalFlow\ApprovalTransferRequest;
use App\Http\Requests\ApprovalFlow\ChangeParticipantReuqest;
use App\Http\Requests\ApprovalFlow\GetChainsRequest;
use App\Http\Transformers\ApprovalParticipantTransformer;
use App\Http\Transformers\ChainTransformer;
use App\Models\ApprovalFlow\ChainFixed;
use App\Models\ApprovalFlow\ChainFree;
use App\Models\ApprovalFlow\Change;
use App\Models\ApprovalFlow\Condition;
use App\Models\ApprovalFlow\Execute;
use App\Models\ApprovalFlow\FixedParticipant;
use App\Models\ApprovalForm\ApprovalForm;
use App\Models\ApprovalForm\Business;
use App\Models\ApprovalForm\Control;
use App\Models\ApprovalForm\Instance;
use App\Models\ApprovalForm\InstanceValue;
use App\Models\ApprovalForm\Participant;
use App\Models\Blogger;
use App\Models\Client;
use App\Models\Contract;
use App\Models\Department;
use App\Models\DepartmentPrincipal;
use App\Models\DepartmentUser;
use App\Models\Message;
use App\Models\OperateEntity;
use App\Models\Project;
use App\Models\Star;
use App\Models\Trail;
use App\OperateLogMethod;
use App\Repositories\MessageRepository;
use App\SignContractStatus;
use App\TriggerPoint\ApprovalTriggerPoint;
use App\TriggerPoint\BloggerTriggerPoint;
use App\TriggerPoint\ClientTriggerPoint;
use App\TriggerPoint\StarTriggerPoint;
use App\User;
use Carbon\Carbon;
use DemeterChain\A;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\URL;
use League\Fractal;
use League\Fractal\Manager;
use League\Fractal\Serializer\DataArraySerializer;


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

        $result = $this->response->collection($chains, new ChainTransformer());

        $participants = FixedParticipant::where('form_id', $formId)->get();

        $resource = new Fractal\Resource\Collection($participants, new ApprovalParticipantTransformer());
        $manager = new Manager();
        $manager->setSerializer(new DataArraySerializer());
        $result->addMeta('notice', $manager->createData($resource)->toArray());

        return $result;
    }

    public function storeFreeChains($chains, $formNumber)
    {
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

//            $this->storeRecord($formNumber, $user->id, $now, 237);
//
//            $this->createOrUpdateHandler($formNumber, $chains[0]['id'], 245, 231);
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

        // 判断分支
        $form = $instance->form;
        $formId = $instance->form_id;
        $condition = null;
        if ($form->change_type == 224) {
            // todo 拼value
            $formControlId = Condition::where('form_id', $formId)->first()->form_control_id;
            $value = $this->getValuesForCondition($formControlId, $num);
            $condition = $this->getCondition($formId, $value);
        }


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

        try {
            $now = Execute::where('form_instance_number', $num)->first();
            if ($now->flow_type_id == 232)
                return $this->response->array(['data' => $array]);
            else if ($now->flow_type_id == 231) {
                $person = $now->person;
                // todo 把主管换成人
                if ($now->current_handler_type == 246) {
                    $header = $this->departmentHeaderToUser($num, $formId, $condition);
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
        } catch (ApprovalVerifyException $exception) {
            return $this->response->errorBadRequest($exception->getMessage());
        }

        list($nextId, $type, $principalLevel) = $this->getChainNext($instance, $now->current_handler_id, false, $now->principal_level);
        if ($nextId == 0)
            return $this->response->array(['data' => $array]);


        if ($form->change_type == 223) {
            $nextChain = ChainFree::where('next_id', $nextId)->where('form_number', $num)->first();
            $chains = ChainFree::where('form_number', $num)
                ->where('next_id', '!=', 0)
                ->where('sort_number', '>=', $nextChain->sort_number)
                ->orderBy('sort_number')
                ->get();
        } else {
            $nextChain = ChainFixed::where('next_id', $nextId)->where('form_id', $formId)->where('principal_level', $principalLevel)->where('condition_id', $condition)->first();
            $chains = ChainFixed::where('form_id', $formId)
                ->where('condition_id', $condition)
                ->where('next_id', '!=', 0)
                ->where('sort_number', '>=', $nextChain->sort_number)
                ->orderBy('sort_number')
                ->get();
        }

        foreach ($chains as $key => $chain) {
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

        // 判断分支
        $form = $instance->form;
        $formId = $instance->form_id;
        $condition = null;
        if ($form->change_type == 224) {
            // todo 拼value
            $formControlId = Condition::where('form_id', $formId)->first()->form_control_id;
            $value = $this->getValuesForCondition($formControlId, $num);
            $condition = $this->getCondition($formId, $value);
        }

        $comment = $request->get('comment', null);

        $user = Auth::guard('api')->user();
        $userId = $user->id;

        $now = Carbon::now();

        DB::beginTransaction();
        try {
            $currentHandlerId = $this->verifyHandler($num, $userId);
            //获取下一个审批人及审批人类型
            list($nextId, $type, $principalLevel) = $this->getChainNext($this->getInstance($num), $currentHandlerId);

            $this->storeRecord($num, $userId, $now, 239, $comment, $type, $nextId);

            if ($nextId)
                $this->createOrUpdateHandler($num, $nextId, $type, $principalLevel);
            else
                $this->createOrUpdateHandler($num, $userId, $type, $principalLevel, 232);

            // 操作日志
            $operate = new OperateEntity([
                'obj' => $instance,
                'title' => null,
                'start' => null,
                'end' => null,
                'method' => OperateLogMethod::APPROVAL_AGREE,
            ]);
            event(new OperateLogEvent([
                $operate
            ]));
        } catch (ApprovalVerifyException $exception) {
            DB::rollBack();
            return $this->response->errorForbidden($exception->getMessage());
        } catch (Exception $exception) {
            DB::rollBack();
            Log::error($exception);
            return $this->response->errorInternal('审批失败');
        }
        DB::commit();

        DB::beginTransaction();
        try {
            if ($type == 246) {
                $header = $this->departmentHeaderToUser($num, $formId, $condition);
                if ($userId == $header->id) {
                    list($nextId, $type, $principalLevel) = $this->getChainNext($this->getInstance($num), $currentHandlerId);
                    $this->storeRecord($num, $userId, $now, 239, $comment, $type, $nextId);
                    if ($nextId)
                        $this->createOrUpdateHandler($num, $nextId, $type, $principalLevel);
                    else
                        $this->createOrUpdateHandler($num, $userId, $type, $principalLevel, 232);
                }
            } elseif ($nextId == $userId) {
                list($nextId, $type, $principalLevel) = $this->getChainNext($this->getInstance($num), $currentHandlerId);
                $this->storeRecord($num, $userId, $now, 239, $comment, $type, $nextId);
                if ($nextId)
                    $this->createOrUpdateHandler($num, $nextId, $type, $principalLevel);
                else
                    $this->createOrUpdateHandler($num, $userId, $type, $principalLevel, 232);
            }
        } catch (Exception $exception) {
            DB::rollBack();
            Log::error($exception);
        }
        DB::commit();

        $authorization = $request->header()['authorization'][0];
        $excute = Execute::where("form_instance_number", $instance->form_instance_number)->first();
        if ($excute->flow_type_id == 232) {//审批通过

            $num = $instance->form_instance_number;
            $contract = Contract::where('form_instance_number', $num)->first();
            if ($contract) {//如果是合同
                $star_arr = explode(",", $contract->stars);
                $created_at = $contract->created_at;
                $meta = ["created" => $created_at];
                if (in_array($instance->form_id, [5, 7])) {//签约
                    if ($contract->star_type == "bloggers") {
                        event(new BloggerMessageEvent($star_arr, BloggerTriggerPoint::SIGNING, $authorization, $user, $meta));
                    }
                    if ($contract->star_type == "stars") {
                        event(new StarMessageEvent($star_arr, StarTriggerPoint::SIGNING, $authorization, $user, $meta));
                    }


                }
                if (in_array($instance->form_id, [6, 8])) {//解约
                    if ($contract->star_type == "bloggers") {
                        event(new BloggerMessageEvent($star_arr, StarTriggerPoint::RESCISSION, $authorization, $user, $meta));
                    }
                    if ($contract->star_type == "stars") {
                        event(new StarMessageEvent($star_arr, BloggerTriggerPoint::RESCISSION, $authorization, $user, $meta));
                    }
                }
            }
            //如果是项目
            if ($instance->business_type == "contracts") {
                //项目审批通过向,并且客户是直客，向papi商务组发送，直客成单消息
                $client = Client::join('contracts', 'clients.id', 'contracts.client_id')
                    ->where('contracts.form_instance_number', $instance->form_instance_number)
                    ->where('grade', Client::GRADE_NORMAL)//直客
                    ->first();
                if ($client) {
                    //直客成单保护期增加180天
                    $client->protected_client_time = Carbon::now()->addDay("180")->toDateTimeString();
                    $client->save();
                    $meta = ['contracts' => $instance];
                    event(new ClientMessageEvent($client, ClientTriggerPoint::GRADE_NORMAL_ORDER_FORM, $authorization, $user, $meta));
                }
            }

            event(new ApprovalMessageEvent($instance, ApprovalTriggerPoint::AGREE, $authorization, $user));
            //项目合同审批同意向M组发消息
            event(new ApprovalMessageEvent($instance, ApprovalTriggerPoint::PROJECT_CONTRACT_AGREE, $authorization, $user));
        } else {
            //向下一个审批人发消息
            event(new ApprovalMessageEvent($instance, ApprovalTriggerPoint::WAIT_ME, $authorization, $user, $nextId));
        }

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

            $this->createOrUpdateHandler($num, $userId, 245, null, 233);

            // 操作日志
            $operate = new OperateEntity([
                'obj' => $instance,
                'title' => null,
                'start' => null,
                'end' => null,
                'method' => OperateLogMethod::APPROVAL_REFUSE,
            ]);
            event(new OperateLogEvent([
                $operate
            ]));
        } catch (ApprovalVerifyException $exception) {
            DB::rollBack();
            return $this->response->errorForbidden($exception->getMessage());
        } catch (Exception $exception) {
            DB::rollBack();
            Log::error($exception);
            return $this->response->errorInternal('审批失败');
        }
        DB::commit();

        //发消息
        $authorization = $request->header()['authorization'][0];
        event(new ApprovalMessageEvent($instance, ApprovalTriggerPoint::REFUSE, $authorization, $user));

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
            // 操作日志
            $operate = new OperateEntity([
                'obj' => $instance,
                'title' => User::find($nextId)->name,
                'start' => null,
                'end' => null,
                'method' => OperateLogMethod::APPROVAL_TRANSFER,
            ]);
            event(new OperateLogEvent([
                $operate
            ]));
        } catch (ApprovalVerifyException $exception) {
            DB::rollBack();
            return $this->response->errorForbidden($exception->getMessage());
        } catch (Exception $exception) {
            DB::rollBack();
            Log::error($exception);
            return $this->response->errorInternal('审批失败');
        }
        DB::commit();
        //发消息
        $authorization = $request->header()['authorization'][0];
        event(new ApprovalMessageEvent($instance, ApprovalTriggerPoint::TRANSFER, $authorization, $user, $nextId));

        return $this->response->created();
    }

    public function cancel(Request $request, $instance)
    {
        $num = $instance->form_instance_number;

        $user = Auth::guard('api')->user();
        $userId = $user->id;

        list($nextId, $type, $principalLevel) = $this->getChainNext($instance, 0);

        $currentStatus = Execute::where('form_instance_number', $num)->first();
        if ($currentStatus->flow_type_id != 231 || $currentStatus->current_handler_id != $nextId) {
            return $this->response->errorForbidden('审批流程已开始，已无法取消');
        }

        $comment = $request->get('comment', null);

        $now = Carbon::now();

        DB::beginTransaction();
        try {
            $this->storeRecord($num, $userId, $now, 242, $comment);

            $this->createOrUpdateHandler($num, $userId, 245, null, 234);
            // 操作日志
            $operate = new OperateEntity([
                'obj' => $instance,
                'title' => null,
                'start' => null,
                'end' => null,
                'method' => OperateLogMethod::APPROVAL_CANCEL,
            ]);
            event(new OperateLogEvent([
                $operate
            ]));

        } catch (Exception $exception) {
            DB::rollBack();
            Log::error($exception);
            return $this->response->errorInternal('审批失败');
        }
        DB::commit();
        return $this->response->created();
    }

    //消息提醒
    public function remind(Request $request, $instance)
    {
        $user = Auth::guard('api')->user();

        $authorization = $request->header()['authorization'][0];
        event(new ApprovalMessageEvent($instance, ApprovalTriggerPoint::REMIND, $authorization, $user));
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

            $this->createOrUpdateHandler($num, $userId, 245, null, 235);
            // 操作日志
            $operate = new OperateEntity([
                'obj' => $instance,
                'title' => null,
                'start' => null,
                'end' => null,
                'method' => OperateLogMethod::APPROVAL_DISCARD,
            ]);
            event(new OperateLogEvent([
                $operate
            ]));
        } catch (Exception $exception) {
            DB::rollBack();
            Log::error($exception);
            return $this->response->errorInternal('审批失败');
        }
        DB::commit();
        return $this->response->created();
    }

    public function changeParticipant(ChangeParticipantReuqest $request, $instance)
    {
        $id = $request->get('id');
        $operate = $request->get('operate', true);
        if ($id)
            $id = hashid_decode($id);

        $num = $instance->form_instance_number;
        $user = User::find($id);
        if (is_null($user))
            return $this->response->errorBadRequest('找不到对应用户');

        try {
            if ($operate) {
                $participant = Participant::where('form_instance_number', $num)->where('notice_id', $id)->first();
                if (is_null($participant))
                    Participant::create([
                        'form_instance_number' => $num,
                        'notice_id' => $id,
                        'notice_type' => 245,
                        'created_at' => Carbon::now()
                    ]);
            } else
                Participant::where('notice_id', $id)->where('form_instance_number', $num)->delete();
        } catch (Exception $exception) {
            Log::error($exception);
            return $this->response->errorInternal('修改知会人失败');
        }
        return $this->response->accepted();
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
     * 重复审批人出现会跳过中间审批人
     * @param $instance
     * @param $preId
     * @param $close boolean 处理作废后标示结束
     * @return array [int $nextId, int $type]
     * @throws Exception
     */
    private function getChainNext($instance, $preId, $close = false, $level = null)
    {
        $form = ApprovalForm::where('form_id', $instance->form_id)->first();

        $principalLevel = null;
        if (!$form)
            throw new Exception('form不存在');

        if ($close)
            return [0, 245, $principalLevel];

        $formId = $form->form_id;
        $num = $instance->form_instance_number;
        $changeType = $form->change_type;
        $count = Change::where('form_instance_number', $num)->whereNotIn('change_state', [237, 241, 242, 243,])->count('form_instance_number');
        $now = Execute::where('form_instance_number', $num)->where('flow_type_id', 231)->count('form_instance_number');
        if ($changeType == 222) {
            // 固定流程
            $chain = ChainFixed::where('form_id', $formId)->where('pre_id', $preId)->where('sort_number', $count + $now)->where('principal_level', $level + 1)->first();
        } else if ($changeType == 223) {
            // 自由流程
            $chain = ChainFree::where('form_number', $num)->where('pre_id', $preId)->where('sort_number', $count + $now)->first();
        } else if ($changeType == 224) {

            // 分支流程
            $formControlIds = Condition::where('form_id', $formId)->value('form_control_id');
            $value = $this->getValuesForCondition($formControlIds, $num);
            $conditionId = $this->getCondition($instance->form_id, $value);
            $chain = ChainFixed::where('form_id', $formId)->where('sort_number', $count + $now)->where('pre_id', $preId)->where('principal_level', $level + 1)->where('condition_id', $conditionId)->first();
        } else {
            throw new Exception('审批流转不存在');
        }
        if (is_null($chain)) {
            $now = Carbon::now();

            return $this->getTransferNextChain($instance, $now);
        }
        if ($chain->next_id == 0)
            return [0, 245, $principalLevel];

        $next = $chain->next;

        $type = $chain->approver_type;
        $principalLevel = $chain->principal_level;
        if (is_null($type))
            $type = 245;

        return [$next->id, $type, $principalLevel];
    }


    private function getTransferNextChain($instance)
    {
        $num = $instance->form_instance_number;
        $count = Change::where('form_instance_number', $num)->whereNotIn('change_state', [240, 241, 242, 243])->count('form_instance_number');

        $principalLevel = null;
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
        if ($preId == 0 && $count > 1)
            $arr = [0, 245, $principalLevel];
        else {
            if ($form->change_type == 223) {
                $chain = ChainFree::where('form_number', $num)->where('sort_number', $count + 1)->first();
                $arr = [$chain->next_id, 245];
            } else if ($form->change_type == 222) {
                $chain = ChainFixed::where('form_id', $form->form_id)->where('sort_number', $count + 1)->first();
                $arr = [$chain->next_id, $chain->approver_type, $chain->principal_level];
            } else if ($form->change_type == 224) {
                $formControlIds = Condition::where('form_id', $form->form_id)->value('form_control_id');
                $value = $this->getValuesForCondition($formControlIds, $num);
                $conditionId = $this->getCondition($instance->form_id, $value);
                $chain = ChainFixed::where('form_id', $form->form_id)->where('condition_id', $conditionId)->where('sort_number', $count + 1)->first();
                $arr = [$chain->next_id, $chain->approver_type, $chain->principal_level];
            }
        }

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

    private function createOrUpdateHandler($num, $nextId, $type, $level = null, $status = 231)
    {
        $instance = Instance::where('form_instance_number', $num)->first();
        $creatorId = $instance->apply_id;
        $principal = DepartmentPrincipal::where('user_id', $creatorId)->first();
        $flag = 0;
        if (!is_null($principal)) {
            $flag = 1;
        }

        try {
            $execute = Execute::where('form_instance_number', $num)->first();
            if ($execute)
                $execute->update([
                    'current_handler_id' => $nextId,
                    'current_handler_type' => $type,
                    'principal_level' => $level + $flag,
                    'flow_type_id' => $status,
                ]);
            else
                Execute::create([
                    'form_instance_number' => $num,
                    'current_handler_id' => $nextId,
                    'current_handler_type' => $type,
                    'principal_level' => $level + $flag,
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

    private function storeRecord($num, $userId, $dateTime, $status, $approverType = null, $roleId = null, $comment = null)
    {
        try {
            if ($approverType != 245)
                $record = Change::create([
                    'form_instance_number' => $num,
                    'change_id' => $userId,
                    'change_at' => $dateTime,
                    'change_state' => $status,
                    'approver_type' => $approverType,
                    'role_id' => $roleId,
                    'comment' => $comment
                ]);
            else
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

    /**
     * 实现逻辑
     * 遍历数字类型的字段的条件
     * 如果值大于需要的条件值就视为符合条件
     * 数值条件记录例：
     *  condition    sort_number
     *      0              1
     *      100            2
     *      200            3
     * @param $formId
     * @param $value
     * @return int
     */
    private function numberForCondition($formId, $value)
    {
        $result = 0;
        foreach (Condition::where('form_id', $formId)->orderBy('sort_number', 'desc')->cursor() as $item) {
            if ($value * 1 >= $item->condition * 1) {
                $result = $item->condition;
                break;
            } else {
                continue;
            }
        }
        return $result;
    }

    private function departmentHeaderToUser($num, $formId, $condition)
    {
        $count = Change::where('form_instance_number', $num)->whereNotIn('change_state', [240, 241, 242, 243])->count();

        $creatorId = Change::where('form_instance_number', $num)->where('change_state', 237)->value('change_id');
        $departmentId = DepartmentUser::where('user_id', $creatorId)->value('department_id');

        $currentChain = ChainFixed::where('form_id', $formId)->where('condition_id', $condition)->where('sort_number', $count)->first();

        if ($currentChain->principal_level == 1) {
            $headerId = DepartmentPrincipal::where('department_id', $departmentId)->value('user_id');
            if ($headerId == $creatorId) {
                $departmentPid = Department::where('id', $departmentId)->value('department_pid');
                $headerId = DepartmentPrincipal::where('department_id', $departmentPid)->value('user_id');
            }
        } elseif ($currentChain->principal_level == 2) {
            $departmentPid = Department::where('id', $departmentId)->value('department_pid');
            $headerId = DepartmentPrincipal::where('department_id', $departmentPid)->value('user_id');
            if ($headerId == $creatorId) {
                $departmentPid = Department::where('id', $departmentPid)->value('department_pid');
                $headerId = DepartmentPrincipal::where('department_id', $departmentPid)->value('user_id');
            }
        } else {
            throw new ApprovalVerifyException('暂不应存在二级以上主管审批，请连续管理员');
        }

        if ($headerId)
            return User::find($headerId);
        else
            return null;
    }

    private function changeRelateStatus($instance, $status)
    {
        $num = $instance->form_instance_number;
        $contract = Contract::where('form_instance_number', $num)->first();
        $project = Project::where('project_number', $num)->first();

        if ($project && $status == 232)
            $project->trail->update([
                'progress_status' => Trail::STATUS_CONFIRMED
            ]);
        else if ($project && $status != 232) {
            $project->delete();
            $project->trail->update([
                'progress_status' => Trail::STATUS_UNCONFIRMED
            ]);
        }

        if (is_null($contract))
            return null;

        // 签约解约处理
        if ($contract->star_type && $status == 232) {
            $starArr = explode(',', $contract->stars);

            //签约
            if (in_array($instance->form_id, [5, 7])) {

                DB::table($contract->star_type)->whereIn('id', $starArr)->update([
                    'sign_contract_at' => $contract->contract_start_date,
                    'sign_contract_status' => SignContractStatus::ALREADY_SIGN_CONTRACT
                ]);

            }
            //解约
            if (in_array($instance->form_id, [6, 8])) {
                DB::table($contract->star_type)->whereIn('id', $starArr)->update([
                    'terminate_agreement_at' => $contract->contract_start_date,
                    'sign_contract_status' => SignContractStatus::ALREADY_TERMINATE_AGREEMENT
                ]);
            }
        }

        // 签约解约处理
        if ($contract->star_type && $status == 235) {
            $starArr = explode(',', $contract->stars);

            //签约
            if (in_array($instance->form_id, [5, 7])) {

                DB::table($contract->star_type)->whereIn('id', $starArr)->update([
                    'sign_contract_at' => null,
                    'sign_contract_status' => SignContractStatus::SIGN_CONTRACTING
                ]);

            }
            //解约
            if (in_array($instance->form_id, [6, 8])) {
                DB::table($contract->star_type)->whereIn('id', $starArr)->update([
                    'terminate_agreement_at' => null,
                    'sign_contract_status' => SignContractStatus::ALREADY_SIGN_CONTRACT
                ]);
            }
        }

    }
}
