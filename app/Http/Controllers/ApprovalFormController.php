<?php

namespace App\Http\Controllers;

use App\Exceptions\ApprovalVerifyException;
use App\Helper\Generator;
use App\Http\Requests\Approval\GetFormIdsRequest;
use App\Http\Requests\Approval\InstanceStoreRequest;
use App\Http\Transformers\ApprovalFormTransformer;
use App\Http\Transformers\ApprovalInstanceTransformer;
use App\Http\Transformers\ApprovalParticipantTransformer;
use App\Http\Transformers\ControlTransformer;
use App\Interfaces\ApprovalInstanceInterface;
use App\Models\ApprovalFlow\Condition;
use App\Models\ApprovalForm\ApprovalForm;
use App\Http\Transformers\FormControlTransformer;
use App\Models\ApprovalForm\Control;
use App\Models\ApprovalForm\Group;
use App\Models\ApprovalForm\Instance;
use App\Models\ApprovalForm\InstanceValue;
use App\Models\Contract;
use App\Models\DataDictionary;
use App\Models\ProjectHistorie;
use App\Models\TemplateFieldHistories;
use Carbon\Carbon;
use Illuminate\Http\Request;
use App\Models\Project;
use App\Models\DataDictionarie;
use App\Models\DepartmentPrincipal;
use App\Models\DepartmentUser;
use App\User;
use App\Http\Transformers\ProjectTransformer;
use App\Http\Transformers\ProjectHistoriesTransformer;
use App\Http\Transformers\TemplateFieldHistoriesTransformer;

use App\Models\RoleUser;
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
    protected $contract;
    protected $starType;

    public function __construct(Generator $generator = null)
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
                    'current_handler_type' => $executeInfo[0]['approver_type'],
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
            ->join('project_histories as ph', function ($join) {
                $join->on('bu.form_instance_number', '=', 'ph.project_number');
            })
            ->join('users', function ($join) {
                $join->on('ph.creator_id', '=', 'users.id');
            })
            ->where(function ($query) use ($payload, $request) {
                if ($request->has('keyword')) {
                    $query->where('bu.form_instance_number', $payload['keyword'])->orwhere('users.name', 'LIKE', '%' . $payload['keyword'] . '%');
                }
            })
            ->where('ph.creator_id', $user->id)
            ->whereIn('bu.form_status', $payload['status'])
            ->orderBy('ph.created_at', 'desc')
            ->select('ph.*', 'bu.*', 'users.name', 'ph.id')
            ->paginate($pageSize)->toArray();

        //return $this->response->item($data, new ProjectTransformer());

        foreach ($data['data'] as $key => &$value) {
            $value->id = hashid_encode($value->id);
            $value->creator_id = hashid_encode($value->creator_id);

        }
        return $data;

    }

    public function detail(Request $request, ApprovalInstanceInterface $instance)
    {

        $type = $instance->business_type;
        if ($type == 'projects') {
            $project = ProjectHistorie::where('project_number', $instance->form_instance_number)->first();
            $result = $this->getProject($request, $project);
        } else {
            $result = $this->getInstance($instance);
        }

        return $result;
    }

    public function myApproval(Request $request)
    {

        $payload = $request->all();
        $user = Auth::guard('api')->user();
        $userId = $user->id;
        $pageSize = $request->get('page_size', config('app.page_size'));

        $payload['page'] = isset($payload['page']) ? $payload['page'] : 1;
        $payload['status'] = isset($payload['status']) ? $payload['status'] : 1;
        if ($payload['status'] == 1) {
            $payload['status'] = array('231');

            //查询角色
            $dataRole = DB::table('approval_flow_execute as afe')//
            ->join('role_users as ru', function ($join) {
                $join->on('afe.current_handler_id', '=', 'ru.role_id');
            })
                ->join('users as u', function ($join) {
                    $join->on('ru.user_id', '=', 'u.id');
                })
                ->join('project_histories as ph', function ($join) {
                    $join->on('afe.form_instance_number', '=', 'ph.project_number');
                })
                ->join('users as us', function ($join) {
                    $join->on('ph.creator_id', '=', 'us.id');
                })
                ->whereIn('afe.flow_type_id', $payload['status'])->where('afe.current_handler_type', 247)->where('u.id', $userId)
                ->orderBy('ph.created_at', 'desc')
                ->select('ph.id', 'afe.form_instance_number', 'afe.current_handler_type', 'afe.current_handler_type', 'afe.flow_type_id as form_status', 'ph.title', 'us.name', 'ph.created_at')->get()->toArray();
            //->paginate($pageSize)->toArray();
            //查询个人
            $dataUser = DB::table('approval_flow_execute as afe')//
            ->join('users as u', function ($join) {
                $join->on('afe.current_handler_id', '=', 'u.id');
            })
                ->join('project_histories as ph', function ($join) {
                    $join->on('afe.form_instance_number', '=', 'ph.project_number');
                })
                ->join('users as us', function ($join) {
                    $join->on('ph.creator_id', '=', 'us.id');
                })
                ->whereIn('afe.flow_type_id', $payload['status'])->where('afe.current_handler_type', 245)->where('u.id', $userId)
                ->orderBy('ph.created_at', 'desc')
                ->select('afe.form_instance_number', 'afe.flow_type_id as form_status', 'ph.title', 'us.name', 'ph.created_at', 'ph.id')->get()->toArray();

            //部门负责人
            $dataPrincipal = DB::table('approval_flow_execute as afe')//
            ->join('approval_form_business as bu', function ($join) {
                $join->on('afe.form_instance_number', '=', 'bu.form_instance_number');
            })
                ->join('approval_flow_change as recode', function ($join) {
                    $join->on('afe.form_instance_number', '=', 'recode.form_instance_number')->where('recode.change_state', '=', 237);
                })
                ->join('users as creator', function ($join) {
                    $join->on('recode.change_id', '=', 'creator.id');
                })
                ->join('department_user as du', function ($join) {
                    $join->on('creator.id', '=', 'du.user_id');
                })
                ->join('department_principal as dp', function ($join) {
                    $join->on('dp.department_id', '=', 'du.department_id')->where('afe.current_handler_type', '=', 246);
                })
                ->join('project_histories as ph', function ($join) {
                    $join->on('ph.project_number', '=', 'bu.form_instance_number');
                })
                ->where('dp.user_id', $userId)
                ->whereIn('afe.flow_type_id', $payload['status'])
                ->orderBy('ph.created_at', 'desc')
                ->select('afe.form_instance_number', 'afe.flow_type_id as form_status', 'ph.title', 'creator.name', 'ph.created_at', 'ph.id')->get()->toArray();

            $resArr = array_merge($dataPrincipal, $dataUser, $dataRole);
        } else {

            //$payload['status'] = array('232', '233', '234', '235');
            $resArr = $this->thenApproval();
        }

        $count = count($resArr);//总条数
        $start = ($payload['page'] - 1) * $pageSize;//偏移量，当前页-1乘以每页显示条数
        $article = array_slice($resArr, $start, $pageSize);


        $arr = array();

        $arr['data'] = $article;
        $arr['meta']['pagination'] = $count;
        $arr['meta']['current_page'] = $payload['page'];
        $arr['meta']['total_pages'] = ceil($count / 20);

        foreach ($arr['data'] as $key => &$value) {
            $value->id = hashid_encode($value->id);
        }
        return $arr;
    }

    //获取已审批信息
    public function thenApproval()
    {
        $user = Auth::guard('api')->user();
        $userId = $user->id;
        //查询个人
        $dataUser = DB::table('approval_flow_change as afc')//
        ->join('users as u', function ($join) {
            $join->on('afc.change_id', '=', 'u.id');
        })
            ->join('project_histories as ph', function ($join) {
                $join->on('afc.form_instance_number', '=', 'ph.project_number');
            })
            ->join('users as us', function ($join) {
                $join->on('us.id', '=', 'ph.creator_id');
            })
            ->join('approval_form_business as afb', function ($join) {
                $join->on('afb.form_instance_number', '=', 'afc.form_instance_number');
            })
            //->where('afe.form_instance_number',$payload['keyword'])->orwhere('us.name', 'LIKE', '%' . $payload['keyword'] . '%')->orwhere('afis.form_control_value', 'LIKE', '%' . $payload['keyword'] . '%')

            ->where('afc.change_state', '!=', 237)->where('afc.change_state', '!=', 238)->where('afc.change_id', $userId)
            ->orderBy('afc.change_at', 'desc')
            ->groupBy('afb.form_instance_number')
            ->select('afb.form_instance_number', 'afb.form_status', 'ph.title', 'us.name', 'ph.created_at', 'ph.id','afc.change_at')->get()->toArray();

        return $dataUser;
    }

    public function myThenApproval(Request $request)
    {

        $payload = $request->all();
        $user = Auth::guard('api')->user();
        $userId = $user->id;
        $executeInfo = DB::table('approval_flow_execute')->get()->toArray();
        $user = array();
        foreach ($executeInfo as $value) {
            if ($value->current_handler_type == 245) {
                $user[] = (int)$value->current_handler_id;
            } else {
                $roleInfo = RoleUser::where('user_id', $userId)->where('role_id', $value->current_handler_id)->get()->toArray();
                foreach ($roleInfo as $rvalue) {
                    $user[] = $rvalue['role_id'];
                }
            }
        }

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
            ->whereIn('afe.change_id', $user)
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
        $userId = $user->id;
        $pageSize = $request->get('page_size', config('app.page_size'));

        $payload['page'] = isset($payload['page']) ? $payload['page'] : 1;
        $payload['status'] = isset($payload['status']) ? $payload['status'] : 1;
        $payload['keyword'] = isset($payload['keyword']) ? $payload['keyword'] : '';
        if ($payload['status'] == 1) {
            $payload['status'] = array('231');
            //查询个人
            $dataUser = DB::table('approval_form_participants as afp')//

            ->join('approval_form_business as afb', function ($join) {
                $join->on('afp.form_instance_number', '=', 'afb.form_instance_number');
            })
                ->join('project_histories as cs', function ($join) {
                    $join->on('cs.project_number', '=', 'afp.form_instance_number');
                })
                ->join('users as us', function ($join) {
                    $join->on('cs.creator_id', '=', 'us.id');
                })
                ->whereIn('afb.form_status', $payload['status'])->where('afp.notice_type', 245)->where('afp.notice_id', $userId)
                ->orderBy('afp.created_at', 'desc')
                ->select('afb.form_instance_number', 'cs.title', 'us.name', 'us.name', 'afp.created_at', 'afb.form_status', 'cs.id')->get()->toArray();


            //查询角色
            $dataRole = DB::table('approval_form_participants as afe')//
            ->join('role_users as ru', function ($join) {
                $join->on('afe.notice_id', '=', 'ru.role_id');
            })
                ->join('approval_form_business as afb', function ($join) {
                    $join->on('afe.form_instance_number', '=', 'afb.form_instance_number');
                })
                ->join('users as u', function ($join) {
                    $join->on('ru.user_id', '=', 'u.id');
                })
                ->join('project_histories as ph', function ($join) {
                    $join->on('afe.form_instance_number', '=', 'ph.project_number');
                })
                ->join('users as us', function ($join) {
                    $join->on('ph.creator_id', '=', 'us.id');
                })
                ->whereIn('afb.form_status', $payload['status'])->where('afe.notice_type', 247)->where('u.id', $userId)
                ->orderBy('ph.created_at', 'desc')
                ->select('ph.id', 'afe.form_instance_number', 'afe.notice_type', 'afb.form_status', 'ph.title', 'us.name', 'ph.created_at')->get()->toArray();


            //部门负责人
            $dataPrincipal = DB::table('approval_form_participants as afe')//

            ->join('approval_form_business as bu', function ($join) {
                $join->on('afe.form_instance_number', '=', 'bu.form_instance_number');
            })
                ->join('approval_flow_change as recode', function ($join) {
                    $join->on('afe.form_instance_number', '=', 'recode.form_instance_number')->where('recode.change_state', '=', 237);
                })
                ->join('users as creator', function ($join) {
                    $join->on('recode.change_id', '=', 'creator.id');
                })
                ->join('department_user as du', function ($join) {
                    $join->on('creator.id', '=', 'du.user_id');
                })
                ->join('department_principal as dp', function ($join) {
                    $join->on('dp.department_id', '=', 'du.department_id')->where('afe.notice_type', '=', 246);
                })
                ->join('project_histories as ph', function ($join) {
                    $join->on('ph.project_number', '=', 'bu.form_instance_number');
                })
                ->where('dp.user_id', $userId)
                ->whereIn('bu.form_status', $payload['status'])
                ->orderBy('ph.created_at', 'desc')
                ->select('ph.id', 'afe.form_instance_number', 'afe.notice_type', 'bu.form_status', 'ph.title', 'creator.name', 'ph.created_at')->get()->toArray();

            $resArr = array_merge($dataPrincipal, $dataUser, $dataRole);
        } else {
            $resArr = $this->thenNotifyApproval();
        }

        $count = count($resArr);//总条数
        $start = ($payload['page'] - 1) * $pageSize;//偏移量，当前页-1乘以每页显示条数
        $article = array_slice($resArr, $start, $pageSize);

        $arr = array();
        $arr['total'] = $count;
        $arr['data'] = $article;
        $arr['meta']['pagination'] = $count;
        $arr['meta']['current_page'] = $count;
        $arr['meta']['total_pages'] = ceil($count / 20);

        foreach ($arr['data'] as $key => &$value) {
            $value->id = hashid_encode($value->id);
        }
        return $arr;
    }

    //获取已审批信息
    public function thenNotifyApproval()
    {

        $user = Auth::guard('api')->user();
        $userId = $user->id;
        //查询个人
        $dataUser = DB::table('approval_form_participants as afc')//
        ->join('users as u', function ($join) {
            $join->on('afc.notice_id', '=', 'u.id');
        })
            ->join('project_histories as ph', function ($join) {
                $join->on('afc.form_instance_number', '=', 'ph.project_number');
            })
            ->join('users as us', function ($join) {
                $join->on('us.id', '=', 'ph.creator_id');
            })
            ->join('approval_form_business as afb', function ($join) {
                $join->on('afb.form_instance_number', '=', 'afc.form_instance_number');
            })
            ->where('afc.notice_type', '!=', 237)->where('afc.notice_type', '!=', 238)->where('afc.notice_id', $userId)
            ->where('afb.form_status','!=', 231)
            ->orderBy('ph.created_at', 'desc')
            ->groupBy('afb.form_instance_number')
            ->select('ph.id', 'afb.form_instance_number', 'afb.form_status', 'ph.title', 'us.name', 'ph.created_at')->get()->toArray();

        return $dataUser;
    }

    // 获取一般审批表单
    public function getForm(Request $request, ApprovalForm $approval)
    {
        return $this->response->item($approval, new ApprovalFormTransformer());
    }

    private function getInstance($instance)
    {
        $num = $instance->form_instance_number;
        $result = $this->response->item($instance, new ApprovalInstanceTransformer());

        $data = Control::where('form_id', $instance->form_id)->orderBy('sort_number')->get();
        $resource = new Fractal\Resource\Collection($data, new ControlTransformer($num));
        $manager = new Manager();
        $manager->setSerializer(new DataArraySerializer());

        // todo 申请人、知会人
        $approval = [];

        $approvalStart = Change::where('form_instance_number', $num)->where('change_state', 237)->first();
        $user = User::where('id', $approvalStart->change_id)->first();
        $department = $user->department()->first();

        if ($department)
            $approval = [
                'user_id' => hashid_encode($user->id),
                'name' => $user->name,
                'department_name' => $department->name,
                'position' => $user->position,
                'created_at' => $approvalStart->change_at
            ];

        $participants = Participant::where('form_instance_number', $num)->get();
        $notice = new Fractal\Resource\Collection($participants, new ApprovalParticipantTransformer());
        $result->addMeta('fields', $manager->createData($resource)->toArray());
        $result->addMeta('approval', $approval);
        $result->addMeta('notice', $manager->createData($notice)->toArray());

        return $result;
    }

    // todo 拆成两个
    private function getProject(Request $request, ProjectHistorie $project)
    {
        $payload = $request->all();
        $payload['type'] = isset($payload['type']) ? $payload['type'] : 1;

        $result = $this->response->item($project, new ProjectHistoriesTransformer());

        $data = TemplateFieldHistories::where('status', $payload['type'])->get();

        $participant = DB::table('approval_form_participants as afp')
            ->join('users', function ($join) {
                $join->on('afp.notice_id', '=', 'users.id');
            })->select('users.name', 'users.icon_url', 'afp.notice_id')
            ->where('afp.form_instance_number', $project->project_number)->get()->toArray();

        foreach ($participant as &$value) {
            $value->notice_id = hashid_encode($value->notice_id);
        }

        $resource = new Fractal\Resource\Collection($data, new TemplateFieldHistoriesTransformer($project->id));

        $manager = new Manager();
        $manager->setSerializer(new DataArraySerializer());

        $project = DB::table('project_histories as projects')
            ->join('approval_form_business as bu', function ($join) {
                $join->on('projects.project_number', '=', 'bu.form_instance_number');
            })
            ->join('users', function ($join) {
                $join->on('projects.creator_id', '=', 'users.id');
            })
            ->leftjoin('position', function ($join) {
                $join->on('position.id', '=', 'users.position_id');
            })
            ->join('department_user', function ($join) {
                $join->on('department_user.user_id', '=', 'users.id');
            })
            ->join('departments', function ($join) {
                $join->on('departments.id', '=', 'department_user.department_id');
            })->select('users.name', 'departments.name as department_name', 'projects.project_number as form_instance_number', 'bu.form_status', 'projects.created_at', 'position.name as position')
            ->where('projects.project_number', $project->project_number)->get();

        $result->addMeta('fields', $manager->createData($resource)->toArray());
        $result->addMeta('approval', $project);
        $result->addMeta('participant', $participant);

        return $result;
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
        if ($approval->group_id == 2)
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

            if ($type) {
                $contract = Contract::create([
                    'form_instance_number' => $num,
                    'creator_id' => $user->id,
                    'creator_name' => $user->name,
                ]);
                $this->contract = $contract;

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

            $this->instanceStoreInit($instance->form_id, $num, $user->id);

            // 存知会人
            if ($notice) {
                foreach ($notice as $user) {
                    Participant::create([
                        'form_instance_number' => $num,
                        'notice_id' => hashid_decode($user['id']),
                        'notice_type' => in_array('type', $user) ? $user['type'] : 245,
                        'created_at' => Carbon::now(),
                    ]);
                }
            }

        } catch (ApprovalVerifyException $exception) {
            DB::rollBack();
            return $this->response->errorBadRequest($exception->getMessage());
        } catch (Exception $exception) {
            DB::rollBack();
            Log::error($exception);
            return $this->response->errorInternal('新建审批失败');
        }

        DB::commit();
        return $this->response->created();
    }

    private function instanceValueStore($num, $key, $value, $type = null)
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
                    if ($type == 'type')
                        $this->type = $this->formatType($value);

                    if ($type == 'stars')
                        $this->contract->update([
                            'star_type' => $this->starType
                        ]);

                    if (in_array($type, ['project_id', 'client_id', 'stars']))
                        $this->contract->update([
                            $type => $ids
                        ]);
                    else
                        $this->contract->update([
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
            return [$value, ''];

        if (array_key_exists('id', $value)) {
            $value['id'] = hashid_decode($value['id']);
            return [$value['name'], $value['id']];
        }

        if (!is_array($value[0])) {
            $value = implode(',', $value);
            return [$value, ''];
        }

        if (array_key_exists('fileUrl', $value[0])) {
            $str = '';
            foreach ($value as $item) {
                $str .= $item['fileUrl'] . ',';
            }
            $value = rtrim($str, ',');
            return [$value, ''];
        }

        if (array_key_exists('id', $value[0])) {
            $idArr = [];
            $nameArr = [];
            foreach ($value as $item) {
                $idArr[] = hashid_decode($item['id']);
                $nameArr[] = $item['name'];
            }
            $names = implode(',', $nameArr);
            $ids = implode(',', $idArr);
            return [$names, $ids];
        }


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

    // todo debug 分支存储不对
    private function instanceStoreInit($formId, $num, $userId)
    {
        $form = ApprovalForm::where('form_id', $formId)->first();
        if ($form->change_type == 224) {
            // 分支流程去找对应value
            $controlIds = Condition::where('form_id', $formId)->value('form_control_id');
            $controlIdArr = explode(',', $controlIds);
            $valueArr = [];
            foreach ($controlIdArr as $controlId) {
                $valueArr[] = InstanceValue::where('form_instance_number', $num)->where('form_control_id', $controlId)->value('form_control_value');
            }
            $values = implode(',', $valueArr);
            $conditionId = Condition::where('form_id', $formId)->where('condition', $values)->value('flow_condition_id');
        } else {
            $conditionId = null;
        }

        $executeInfo = ChainFixed::where('form_id', $formId)->where('condition_id', $conditionId)->orderBy('sort_number')->first();
        if (is_null($executeInfo))
            throw new ApprovalVerifyException('审批流不存在');

        try {
            $executeArray = [
                'form_instance_number' => $num,
                'current_handler_id' => $executeInfo->next_id,
                'current_handler_type' => $executeInfo->approver_type,
                'flow_type_id' => DataDictionarie::FORM_STATE_DSP
            ];

            Execute::create($executeArray);
            $changeArray = [
                'form_instance_number' => $num,
                'change_id' => $userId,
                'change_at' => date("Y-m-d H:i:s", time()),
                'change_state' => DataDictionarie::FIOW_TYPE_TJSP
            ];
            Change::create($changeArray);
        } catch (Exception $exception) {
            throw $exception;
        }

    }
}
