<?php

namespace App\Http\Controllers;

use App\Helper\Generator;
use App\Http\Requests\Approval\GetFormIdsRequest;
use App\Http\Requests\Approval\InstanceStoreRequest;
use App\Http\Transformers\ApprovalFormTransformer;
use App\Models\ApprovalForm\ApprovalForm;
use App\Http\Transformers\FormControlTransformer;
use App\Models\ApprovalForm\Group;
use App\Models\ApprovalForm\Instance;
use App\Models\ApprovalForm\InstanceValue;
use App\Models\Contract;
use App\Models\DataDictionary;
use App\Models\ProjectHistorie;
use Carbon\Carbon;
use Illuminate\Http\Request;
use App\Models\Project;
use App\Models\DataDictionarie;
use App\User;
use App\Http\Transformers\ProjectTransformer;

use App\Models\ApprovalForm\Business;
use App\Models\ApprovalFlow\Execute;
use App\Models\ApprovalFlow\ChainFixed;
use App\Models\ApprovalFlow\Change;
use App\Models\ApprovalForm\Participant;
use App\Http\Transformers\TemplateFieldTransformer;
use App\Models\TemplateField;


use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use Exception;
use League\Fractal;
use League\Fractal\Manager;
use League\Fractal\Serializer\DataArraySerializer;

class ApprovalFormController extends Controller
{
    protected $generator;

    protected $company;
    protected $type;

    public function __construct(Generator $generator)
    {
        $this->generator = $generator;
    }

    public function index(GetFormIdsRequest $request)
    {
        $type = $request->get('type');
        if ($type == 0)
            $forms = ApprovalForm::where('group_id', 2)->orderBy('sort_number')->get();
        else
            $forms = ApprovalForm::whereNotIn('group_id', [1, 2])->orderBy('sort_number')->get();

        return $this->response->collection($forms, new ApprovalFormTransformer());
    }

    public function all(Request $request)
    {

    }

    public function projectStore($formId, $notice = '', $projectNumber)
    {
        $user = Auth::guard('api')->user();
        $userId = $user->id;
        if ($projectNumber) {
            DB::beginTransaction();
            try {
                $array = [
                    'form_id' => $formId,
                    'form_instance_number' => $projectNumber,
                    'form_status' => DataDictionarie::FORM_STATE_DSP,
                    'business_type' => project::PROJECT_TYPE
                ];

                Business::create($array);

                $executeInfo = ChainFixed::where('form_id', $formId)->get()->toArray();

                $executeArray = [
                    'form_instance_number' => $projectNumber,
                    'current_handler_id' => $executeInfo[0]['next_id'],
                    // todo 角色处理
                    'current_handler_type' => 245,
                    'flow_type_id' => DataDictionarie::FORM_STATE_DSP
                ];

                Execute::create($executeArray);
                $changeArray = [
                    'form_instance_number' => $projectNumber,
                    'change_id' => $userId,
                    'change_at' => date("Y-m-d H:i:s", time()),
                    'change_state' => DataDictionarie::FIOW_TYPE_TJSP
                ];

                if (!empty($notice)) {
                    foreach ($notice as $value) {
                        $participantsArray = [
                            'form_instance_number' => $projectNumber,
                            'created_at' => date("Y-m-d H:i:s", time()),
                            'notice_id' => hashid_decode($value),
                            'notice_type' => DataDictionarie::NOTICE_TYPE_TEAN,
                        ];
                        Participant::create($participantsArray);
                    }
                }

                Change::create($changeArray);

            } catch (Exception $e) {
                DB::rollBack();
                Log::error($e);
                return $this->response->errorInternal('创建失败');
            }
            DB::commit();
            return $this->response->accepted();
//
        } else {
            return $this->response->errorInternal('数据提交错误');
        }
    }

    public function myApply(Request $request)
    {
        $payload = $request->all();
        $user = Auth::guard('api')->user();

        $pageSize = $request->get('page_size', config('app.page_size'));

        $payload['status'] = isset($payload['status']) ? $payload['status'] : 1;
        if ($payload['status'] == 1) {
            $payload['status'] = array('231');
        } else {
            $payload['status'] = array('232', '233', '234', '235');
        }

        $data = DB::table('approval_form_business as bu')
            ->join('projects as hi', function ($join) {
                $join->on('bu.form_instance_number', '=', 'hi.project_number');
            })
            ->join('users', function ($join) {
                $join->on('hi.creator_id', '=', 'users.id');
            })
            ->where(function ($query) use ($payload, $request) {
                if ($request->has('keyword')) {
                    $query->where('bu.form_instance_number', $payload['keyword'])->orwhere('users.name', 'LIKE', '%' . $payload['keyword'] . '%');
                }
            })
            ->where('hi.creator_id', $user->id)
            ->whereIn('bu.form_status', $payload['status'])
            ->select('hi.*', 'bu.*', 'users.name', 'hi.id')
            ->paginate($pageSize)->toArray();

        //return $this->response->item($data, new ProjectTransformer());

        foreach ($data['data'] as $key => &$value) {
            $value->id = hashid_encode($value->id);
            $value->creator_id = hashid_encode($value->creator_id);

        }
        return $data;

    }

    public function detail(Request $request, Project $project)
    {
        $payload = $request->all();
        $payload['type'] = isset($payload['type']) ? $payload['type'] : 1;

        $result = $this->response->item($project, new ProjectTransformer());

        $data = TemplateField::where('status', $payload['type'])->get();

        $participant = DB::table('approval_form_participants as afp')
            ->join('users', function ($join) {
                $join->on('afp.notice_id', '=', 'users.id');
            })->select('users.name', 'users.icon_url', 'afp.notice_id')
            ->where('afp.form_instance_number', $project->project_number)->get()->toArray();

        foreach ($participant as &$value) {
            $value->notice_id = hashid_encode($value->notice_id);
        }

        $resource = new Fractal\Resource\Collection($data, new TemplateFieldTransformer($project->id));

        $manager = new Manager();
        $manager->setSerializer(new DataArraySerializer());

        $project = DB::table('projects')
            ->join('approval_form_business as bu', function ($join) {
                $join->on('projects.project_number', '=', 'bu.form_instance_number');
            })
            ->join('users', function ($join) {
                $join->on('projects.creator_id', '=', 'users.id');
            })
            ->join('department_user', function ($join) {
                $join->on('department_user.user_id', '=', 'users.id');
            })
            ->join('departments', function ($join) {
                $join->on('departments.id', '=', 'department_user.department_id');
            })->select('users.name', 'departments.name as department_name', 'projects.project_number', 'bu.form_status', 'projects.created_at')
            ->where('projects.project_number', $project->project_number)->get();

        $result->addMeta('fields', $manager->createData($resource)->toArray());
        $result->addMeta('approval', $project);
        $result->addMeta('participant', $participant);

        return $result;
    }

    public function myApproval(Request $request)
    {

        $payload = $request->all();
        $user = Auth::guard('api')->user();

        $pageSize = $request->get('page_size', config('app.page_size'));
        $data = DB::table('approval_flow_execute as afe')//

        ->join('approval_form_business as bu', function ($join) {
            $join->on('afe.form_instance_number', '=', 'bu.form_instance_number');
        })
            ->join('users', function ($join) {
                $join->on('afe.current_handler_id', '=', 'users.id');
            })
            ->join('projects as ph', function ($join) {
                $join->on('ph.project_number', '=', 'bu.form_instance_number');
            })
            ->where(function ($query) use ($payload, $request) {
                if ($request->has('keyword')) {
                    $query->where('afe.form_instance_number', $payload['keyword'])->orwhere('users.name', 'LIKE', '%' . $payload['keyword'] . '%');
                }
            })
            ->where('afe.current_handler_id', $user->id)
            ->where('afe.flow_type_id', DataDictionarie::FORM_STATE_DSP)
            ->select('afe.*', 'bu.*', 'users.name', 'ph.title', 'ph.created_at', 'ph.id')
            ->paginate($pageSize)->toArray();

        foreach ($data['data'] as $key => &$value) {
            $value->id = hashid_encode($value->id);
            $value->current_handler_id = hashid_encode($value->current_handler_id);
        }

        return $data;
    }

    public function myThenApproval(Request $request)
    {

        $payload = $request->all();
        $user = Auth::guard('api')->user();

        $pageSize = $request->get('page_size', config('app.page_size'));

        $data = DB::table('approval_flow_change as afe')//

        ->join('approval_form_business as bu', function ($join) {
            $join->on('afe.form_instance_number', '=', 'bu.form_instance_number');
        })
            ->join('users', function ($join) {
                $join->on('afe.change_id', '=', 'users.id');
            })
            ->join('projects as ph', function ($join) {
                $join->on('ph.project_number', '=', 'bu.form_instance_number');
            })
            ->where(function ($query) use ($payload, $request) {
                if ($request->has('keyword')) {
                    $query->where('afe.form_instance_number', $payload['keyword'])->orwhere('users.name', 'LIKE', '%' . $payload['keyword'] . '%');
                }
            })
            ->where('afe.change_id', $user->id)
            ->whereNotIn('afe.change_state', [DataDictionarie::FIOW_TYPE_TJSP, DataDictionarie::FIOW_TYPE_DSP])
            ->select('afe.*', 'ph.title', 'bu.*', 'users.name', 'ph.created_at', 'ph.id')
            ->paginate($pageSize)->toArray();

        foreach ($data['data'] as $key => &$value) {
            $value->id = hashid_encode($value->id);
            $value->change_id = hashid_encode($value->change_id);
        }

        return $data;
    }

    public function notify(Request $request)
    {

        $payload = $request->all();
        $user = Auth::guard('api')->user();

        $payload['status'] = isset($payload['status']) ? $payload['status'] : 1;

        if ($payload['status'] == 1) {
            $payload['status'] = array('231');
        } else {
            $payload['status'] = array('232', '233', '234', '235');
        }

        $pageSize = $request->get('page_size', config('app.page_size'));
        $data = DB::table('approval_form_participants as afp')//

        ->join('approval_form_business as bu', function ($join) {
            $join->on('afp.form_instance_number', '=', 'bu.form_instance_number');
        })
            ->join('users', function ($join) {
                $join->on('afp.notice_id', '=', 'users.id');
            })
            ->join('projects as ph', function ($join) {
                $join->on('ph.project_number', '=', 'afp.form_instance_number');
            })
            ->where(function ($query) use ($payload, $request) {
                if ($request->has('keyword')) {
                    $query->where('afp.form_instance_number', $payload['keyword'])->orwhere('users.name', 'LIKE', '%' . $payload['keyword'] . '%');
                }
            })
            ->where('afp.notice_id', $user->id)
            ->whereIn('bu.form_status', $payload['status'])
            ->select('ph.id', 'afp.*', 'bu.*', 'users.name', 'ph.created_at')
            ->paginate($pageSize)->toArray();

        foreach ($data['data'] as $key => &$value) {
            $value->id = hashid_encode($value->id);
            $value->notice_id = hashid_encode($value->notice_id);
        }
        return $data;
    }

    // 获取一般审批表单
    public function getForm(Request $request, ApprovalForm $approval)
    {
        $controls = $approval->controls;

        return $this->response->item($approval, new ApprovalFormTransformer());
//        return $this->response->collection($controls, new FormControlTransformer());
    }

    // 获取group里的form_ids
    public function getForms(GetFormIdsRequest $request)
    {
        $type = $request->get('type');
        if ($type)
            $forms = ApprovalForm::whereNotIn('group_id', [1, 2])->orderBy('sort_number', 'asc')->select('form_id', 'name', 'change_type', 'modified')->get();
        else
            $forms = ApprovalForm::where('group_id', 2)->orderBy('sort_number', 'asc')->select('form_id', 'name', 'change_type', 'modified')->get();

        return $this->response->collection($forms, new ApprovalFormTransformer());
    }

    public function instanceStore(InstanceStoreRequest $request, ApprovalForm $approval)
    {
        // 生成instance num
        $num = date("Ymd", time()) . rand(100000000, 999999999);

        $controlValues = $request->get('values');

        $chains = $request->get('chains', null);

        // 添加知会人
        $notice = $request->get('notice', null);

        // 区分合同还是普通审批
        if ($approval->group_id === 2)
            $type = 1;
        else
            $type = 0;

        // 按合同规定艺人or博主
        if (in_array($approval->form_id, [5, 6, 10]))
            $this->starType = 'bloggers';

        if (in_array($approval->form_id, [7, 8, 9]))
            $this->starType = 'stars';

        $user = Auth::guard('api')->user();

        DB::beginTransaction();
        try {
            // 一般审批可以自定义
            if (!$type && $chains) {
                $flow = new ApprovalFlowController();
                $flow->storeFreeChains($chains, $num);
            }

            if (!empty($notice)) {
                foreach ($notice as $value) {
                    $participantsArray = [
                        'form_instance_number' => $num,
                        'created_at' => date("Y-m-d H:i:s", time()),
                        'notice_id' => hashid_decode($value),
                        'notice_type' => DataDictionarie::NOTICE_TYPE_TEAN,
                    ];
                    Participant::create($participantsArray);
                }
            }

            if ($type) {
                $contract = Contract::create([
                    'form_instance_number' => $num,
                    'creator_id' => $user->id,
                    'creator_name' => $user->name,
                ]);

                $instance = Business::create([
                    'form_id' => $approval->form_id,
                    'form_instance_number' => $num,
                    'form_status' => 231,
                    'business_type' => 'contracts'
                ]);
            } else {
                $instance = Instance::create([
                    'form_id' => $approval->form_id,
                    'form_instance_number' => $num,
                    'apply_id' => $user->id,
                    'form_status' => 231,
                    'create_by' => $user->name,
                    'create_at' => Carbon::now(),
                    'updated_at' => Carbon::now(),
                ]);
            }
            foreach ($controlValues as $value) {
                $this->instanceValueStore($num, $value['key'], $value['value'], $value['type']);
            }

            if ($type) {
                $contractNumber = $this->generator->generatorBrokerId($this->formatContractStr($approval->form_id));
                $contract->update(['contract_number' => $contractNumber]);
            }
        } catch (Exception $exception) {
            DB::rollBack();
            Log::error($exception);
            return $this->response->error('新建审批失败');
        }

        DB::commit();
        return $this->response->created();
    }

    private function instanceValueStore($num, $key, $value, $type = null, $contract = null)
    {
        try {
            $key = hashid_decode($key);
            list($value, $ids) = $this->formatValue($value);
            InstanceValue::create([
                'form_instance_number' => $num,
                'form_control_id' => $key,
                'form_control_value' => $value,
            ]);
            if ($type) {
                if ($type == 'contract_number') {
                    $this->company = $this->getCompanyCode($value);
                } else {
                    if ($type === 'type')
                        $this->type = $this->formatType($value);

                    if ($type === 'stars')
                        $contract->update([
                            'star_type' => $this->starType
                        ]);

                    if (in_array($type, ['project_id', 'client_id', 'stars']))
                        $contract->update([
                            $type => $ids
                        ]);
                    else
                        $contract->update([
                            $type => $value
                        ]);
                }
            }
        } catch (Exception $exception) {
            throw $exception;
        }

    }

    private function formatValue($value)
    {
        if (!is_array($value))
            return [$value];

        foreach ($value['id'] as &$id) {
            $id = hashid_decode($id);
        }
        unset($id);
        $names = implode('|', $value['name']);
        $ids = implode('|', $value['id']);
        return [$names, $ids];
    }

    private function formatContractStr($form_id)
    {
        switch ($form_id) {
            case 5:
            case 6:
                $string = 'BZJJ';
                break;
            case 7:
            case 8:
                $string = 'YRJJ';
                break;
            case 9:
            case 10:
                $string = $this->company . $this->type;
                break;
            default:
                throw new Exception('合同编号生成错误');
                break;
        }

        return $string;
    }

    private function formatType($type)
    {
        if (strpos($type, '收入') !== false)
            $this->type = 'SR';
        elseif (strpos($type, '成本') !== false)
            $this->type = 'ZC';
        elseif (strpos($type, '无金额') !== false)
            $this->type = 'W';
        else
            $this->type = null;
    }

    private function getCompanyCode($value)
    {
        return DataDictionary::where('name', $value)->whereIn('parent_id', [136, 177])->value('code');
    }
}
