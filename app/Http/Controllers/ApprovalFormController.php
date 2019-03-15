<?php

namespace App\Http\Controllers;

use App\Events\ApprovalMessageEvent;
use App\Events\OperateLogEvent;
use App\Exceptions\ApprovalVerifyException;
use App\Helper\Generator;
use App\Http\Requests\Approval\GetContractFormRequest;
use App\Http\Requests\Approval\GetFormIdsRequest;
use App\Http\Requests\Approval\InstanceStoreRequest;
use App\Http\Requests\GeneralFormsRequest;
use App\Http\Transformers\ApprovalFormTransformer;
use App\Http\Transformers\ApprovalGroupTransformer;
use App\Http\Transformers\ApprovalInstanceTransformer;
use App\Http\Transformers\ApprovalParticipantTransformer;
use App\Http\Transformers\ContractArchiveTransformer;
use App\Http\Transformers\ControlTransformer;
use App\Http\Transformers\ProjectHistoriesTransformer;
use App\Http\Transformers\ProjectTransformer;
use App\Http\Transformers\TemplateFieldHistoriesTransformer;
use App\Http\Transformers\TemplateFieldTransformer;

use App\Interfaces\ApprovalInstanceInterface;
use App\Models\ApprovalFlow\ChainFixed;
use App\Models\ApprovalFlow\ChainFree;
use App\Models\ApprovalFlow\Change;
use App\Models\ApprovalFlow\Condition;
use App\Models\ApprovalFlow\Execute;
use App\Models\ApprovalForm\ApprovalForm;
use App\Models\ApprovalForm\Business;
use App\Models\ApprovalForm\Control;
use App\Models\ApprovalForm\ControlProperty;
use App\Models\ApprovalForm\DetailValue;
use App\Models\ApprovalForm\Instance;
use App\Models\ApprovalForm\InstanceValue;
use App\Models\ApprovalForm\Participant;
use App\Models\ApprovalGroup;
use App\Models\Blogger;
use App\Models\Contract;
use App\Models\DataDictionarie;
use App\Models\DataDictionary;
use App\Models\DepartmentPrincipal;
use App\Models\DepartmentUser;
use App\Models\Message;
use App\Models\OperateEntity;
use App\Models\Project;
use App\Models\ProjectHistorie;
use App\Models\RoleUser;
use App\Models\Star;
use App\Models\TemplateFieldHistories;
use App\OperateLogMethod;
use App\Repositories\MessageRepository;
use App\TriggerPoint\ApprovalTriggerPoint;
use App\User;
use Carbon\Carbon;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\URL;
use League\Fractal;
use League\Fractal\Manager;
use League\Fractal\Serializer\DataArraySerializer;
use App\Models\FilterJoin;
use App\Http\Requests\Filter\FilterRequest;
use App\Repositories\FilterReportRepository;



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

    public function projectStore(Request $request,$formId, $notice = '', $projectNumber)
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
                    'business_type' => project::PROJECT_TYPE,
                ];

                Business::create($array);

                $executeInfo = ChainFixed::where('form_id', $formId)->get()->toArray();

                //查询创建人是否是部门
                $principalInfo = DepartmentPrincipal::where('user_id', $userId)->get()->toArray();
                if(empty($principalInfo)){
                    $principalLevel = '';
                }else{
                    $principalLevel = 2;
                }

                $executeArray = [
                    'form_instance_number' => $projectNumber,
                    'current_handler_id' => $executeInfo[0]['next_id'],
                    // todo 角色处理
                    'current_handler_type' => $executeInfo[0]['approver_type'],
                    'principal_level' => $principalLevel,
                    'flow_type_id' => DataDictionarie::FORM_STATE_DSP,
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
            $instance = Business::where("form_instance_number",$projectNumber)->first();
            //向知会人发消息
            $authorization = $request->header()['authorization'][0];
            event(new ApprovalMessageEvent($instance, ApprovalTriggerPoint::NOTIFY, $authorization, $user));
            //向下一个审批人发消息
            event(new ApprovalMessageEvent($instance, ApprovalTriggerPoint::WAIT_ME, $authorization, $user));
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
        $payload['page'] = isset($payload['page']) ? $payload['page'] : 1;

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
            ->join("data_dictionaries as dds",function ($join){
                $join->on("dds.id",'=','bu.form_status');
            })
            ->where(function ($query) use ($payload, $request) {
                if ($request->has('keywords')) {
                    $query->where('bu.form_instance_number', 'LIKE', '%'. $payload['keywords'].'%')->orwhere('users.name', 'LIKE', '%' . $payload['keywords'] . '%');
                }
            })
            ->where('ph.creator_id', $user->id)
            ->whereIn('bu.form_status', $payload['status'])
            ->orderBy('ph.created_at', 'desc')
            ->select('ph.*', 'bu.*', 'users.name','users.icon_url', 'ph.id','bu.form_status as approval_status','dds.name as approval_status_name','dds.icon')
            //->pluck('ph.id');
            ->get()->toArray();

//        $projects = Project::whereIn('id', $data)->paginate($pageSize);
//        return $this->response->paginator($projects, new ProjectTransformer());
//
//        foreach ($data['data'] as $key => &$value) {
//            $value->id = hashid_encode($value->id);
//            $value->creator_id = hashid_encode($value->creator_id);
//
//        }
//        return $data;

        $count = count($data);//总条数
        $start = ($payload['page'] - 1) * $pageSize;//偏移量，当前页-1乘以每页显示条数
        $article = array_slice($data, $start, $pageSize);

        $totalPages = ceil($count / $pageSize) ?? 1;
        $arr['data'] = $data;
        $arr['meta']['pagination'] = [
            'total' => $count,
            'count' => $payload['page'] < $totalPages ? $pageSize : $count - (($payload['page'] - 1) * $pageSize),
            'per_page' => $pageSize,
            'current_page' => $payload['page'],
            'total_pages' => $totalPages == 0 ? 1 : $totalPages,
        ];

        foreach ($arr['data'] as $key => &$value) {
            $value->id = hashid_encode($value->id);
            $value->creator_id = hashid_encode($value->creator_id);

        }
        return $arr;

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

        // 操作日志
        $operate = new OperateEntity([
            'obj' => $instance,
            'title' => null,
            'start' => null,
            'end' => null,
            'method' => OperateLogMethod::LOOK,
        ]);
        event(new OperateLogEvent([
            $operate
        ]));

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
                ->join("data_dictionaries as dds",function ($join){
                    $join->on("dds.id",'=','afe.flow_type_id');
                })
                ->where(function ($query) use ($payload, $request) {
                    if ($request->has('keywords')) {
                        $query->where('ph.project_number', 'LIKE', '%' . $payload['keywords'].'%')->orwhere('us.name', 'LIKE', '%' . $payload['keywords'] . '%');
                    }
                })
                ->whereIn('afe.flow_type_id', $payload['status'])->where('afe.current_handler_type', 247)->where('u.id', $userId)
                ->orderBy('ph.created_at', 'desc')
                ->select('ph.id', 'afe.form_instance_number', 'afe.current_handler_type', 'afe.current_handler_type', 'afe.flow_type_id as form_status', 'ph.title', 'us.name','us.icon_url', 'ph.created_at','dds.name as approval_status_name','dds.icon')->get()->toArray();

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
                ->join("data_dictionaries as dds",function ($join){
                    $join->on("dds.id",'=','afe.flow_type_id');
                })
                ->where(function ($query) use ($payload, $request) {
                    if ($request->has('keywords')) {
                        $query->where('ph.project_number', 'LIKE', '%' . $payload['keywords'].'%')->orwhere('us.name', 'LIKE', '%' . $payload['keywords'] . '%');
                    }
                })
                ->whereIn('afe.flow_type_id', $payload['status'])->where('afe.current_handler_type', 245)->where('u.id', $userId)
                ->orderBy('ph.created_at', 'desc')
                ->select('ph.id', 'afe.form_instance_number', 'afe.current_handler_type', 'afe.current_handler_type', 'afe.flow_type_id as form_status', 'ph.title', 'us.name','us.icon_url', 'ph.created_at','dds.name as approval_status_name','dds.icon')->get()->toArray();

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
                ->join("data_dictionaries as dds",function ($join){
                    $join->on("dds.id",'=','afe.flow_type_id');
                })
                ->where(function ($query) use ($payload, $request) {
                    if ($request->has('keywords')) {
                        $query->where('bu.form_instance_number', 'LIKE', '%' . $payload['keywords'].'%')->orwhere('creator.name', 'LIKE', '%' . $payload['keywords'] . '%');
                    }
                })
                ->where('dp.user_id', $userId)
                ->whereIn('afe.flow_type_id', $payload['status'])
                ->orderBy('ph.created_at', 'desc')
                ->select('ph.id', 'afe.form_instance_number', 'afe.current_handler_type', 'afe.current_handler_type', 'afe.flow_type_id as form_status', 'ph.title', 'creator.name','creator.icon_url', 'ph.created_at','dds.name as approval_status_name','dds.icon')->get()->toArray();

            $dataPrincipals = $this->getPrincipalLevel($userId,$request,$payload);
           
            $resArrs = array_merge($dataPrincipal, $dataUser, $dataRole,$dataPrincipals);

            $resArrInfo = json_decode(json_encode($resArrs), true);

            if(empty($resArrInfo)){
                $resArr = array();
            }else{
                $resArr = $this->array_unique_fb($resArrInfo);
            }

            $ctime_str = array();
            foreach($resArr as $key=>$v){

                $arr[$key]['ctime_str'] = strtotime($v['created_at']);
                $ctime_str[] = $arr[$key]['ctime_str'];
            }
            array_multisort($ctime_str,SORT_DESC,$resArr);

        } else {

            //$payload['status'] = array('232', '233', '234', '235');
            $resArr = $this->thenApproval($request,$payload);
        }

        $count = count($resArr);//总条数
        $start = ($payload['page'] - 1) * $pageSize;//偏移量，当前页-1乘以每页显示条数
        $article = array_slice($resArr, $start, $pageSize);


        $arr = array();

        $totalPages = ceil($count / $pageSize) ?? 1;
        $arr['data'] = $article;
        $arr['meta']['pagination'] = [
            'total' => $count,
            'count' => $payload['page'] < $totalPages ? $pageSize : $count - (($payload['page'] - 1) * $pageSize),
            'per_page' => $pageSize,
            'current_page' => $payload['page'],
            'total_pages' => $totalPages == 0 ? 1 : $totalPages,
        ];

        return $arr;
    }

    function getPrincipalLevel($userId,$request,$payload){
        $dataPrincipalLevel = DB::table('approval_flow_execute as afe')
        ->join('project_histories as ph', function ($join) {
            $join->on('afe.form_instance_number', '=', 'ph.project_number');
        })
        ->where('afe.principal_level',2)
        ->select('ph.creator_id')->get()->toArray();

        $resArrInfo = json_decode(json_encode($dataPrincipalLevel), true);

        if(!empty($resArrInfo)){

            foreach ($resArrInfo as $value){
                $creator_id = $value['creator_id'];
                $info[] = DB::select("select dpl.`user_id` as user_ids,dur.user_id as creator_ids  from department_user as dur
                            left join  departments as ds ON dur.`department_id`=ds.`id`
                            left join  department_principal as dpl ON dpl.`department_id`=ds.`department_pid`
                            where dur.`user_id`=$creator_id");

            }
            $arr = json_decode(json_encode($info), true);
            if(!empty($arr)) {

                foreach ($arr as $values) {
                    foreach ($values as $val) {
                        if ($val['user_ids'] == $userId) {
                            $vale[] = $val;
                        }else{
                            $vale = array();
                        }
                    }
                }
                if(!empty($vale)){
                    foreach ($vale as $item) {
                        $arrIds[] = $item['creator_ids'];
                    }
                }else{
                    $arrIds = array();
                }

            }
        }

        //查询二级主管
        $dataPrincipals = DB::table('approval_flow_execute as afe')//
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

//
            })

            ->join('project_histories as ph', function ($join) {
                $join->on('ph.project_number', '=', 'bu.form_instance_number');
            })
            ->join('users as us', function ($join) {
                $join->on('recode.change_id', '=', 'us.id');
            })
            ->join("data_dictionaries as dds",function ($join){
                $join->on("dds.id",'=','afe.flow_type_id');
            })
            ->where(function ($query) use ($payload, $request) {
                if ($request->has('keywords')) {
                    $query->where('bu.form_instance_number', 'LIKE', '%' . $payload['keywords'].'%')->orwhere('creator.name', 'LIKE', '%' . $payload['keywords'] . '%');
                }
            })
            ->whereIn('ph.creator_id', $arrIds)->where('afe.principal_level',2)
            ->whereIn('afe.flow_type_id', $payload['status'])
            ->orderBy('ph.created_at', 'desc')
            ->select('ph.id', 'afe.form_instance_number', 'afe.current_handler_type', 'afe.current_handler_type', 'afe.flow_type_id as form_status', 'ph.title', 'us.name','us.icon_url', 'ph.created_at','dds.name as approval_status_name','dds.icon')->distinct()->get()->toArray();

        return $dataPrincipals;
    }



    


    function array_unique_fb($array2D)
    {
        foreach ($array2D as $k=>$v)
        {
            $v = join(",",$v);  //降维,也可以用implode,将一维数组转换为用逗号连接的字符串
            $temp[$k] = $v;
        }
        $temp = array_unique($temp);    //去掉重复的字符串,也就是重复的一维数组
        foreach ($temp as $k => $v)
        {
            $array=explode(",",$v);//再将拆开的数组重新组装
            $temp2[$k]["id"] =hashid_encode($array[0]);;
            $temp2[$k]["form_instance_number"] =$array[1];
            $temp2[$k]["current_handler_type"] =$array[2];
            $temp2[$k]["form_status"] =$array[3];
            $temp2[$k]["title"] =$array[4];
            $temp2[$k]["name"] =$array[5];
            $temp2[$k]["icon_url"] =$array[6];
            $temp2[$k]["created_at"] =$array[7];
            $temp2[$k]["approval_status_name"] =$array[8];
            $temp2[$k]["icon"] =$array[9];
        }
        return $temp2;
    }

    //获取已审批信息
    public function thenApproval($request,$payload)
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
            ->join("data_dictionaries as dds",function ($join){
                $join->on("dds.id",'=','afb.form_status');
            })
            ->where(function ($query) use ($payload, $request) {
                if ($request->has('keywords')) {
                    $query->where('ph.project_number', 'LIKE', '%' .$payload['keywords'].'%')->orwhere('us.name', 'LIKE', '%' . $payload['keywords'] . '%');
                }
            })
            //->where('afe.form_instance_number',$payload['keyword'])->orwhere('us.name', 'LIKE', '%' . $payload['keyword'] . '%')->orwhere('afis.form_control_value', 'LIKE', '%' . $payload['keyword'] . '%')

            ->where('afc.change_state', '!=', 237)->where('afc.change_state', '!=', 238)->where('afc.change_id', $userId)
            ->orderBy('afc.change_at', 'desc')
            ->groupBy('afb.form_instance_number')
            ->select('afb.form_instance_number', 'afb.form_status', 'ph.title', 'us.name', 'ph.created_at', 'ph.id', 'afc.change_at','us.icon_url','dds.icon','dds.name as approval_status_name')->get()->toArray();


        //查询角色
        //根据user_id 查询角色id

        $dataUserInfo = DB::table('approval_flow_change as afc')
            ->join('role_users', function ($join) {
                $join->on('role_users.role_id', '=','afc.role_id');
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
            ->join("data_dictionaries as dds",function ($join){
                $join->on("dds.id",'=','afb.form_status');
            })

            ->where(function ($query) use ($payload, $request) {
                if ($request->has('keywords')) {
                    $query->where('afb.form_instance_number', 'LIKE','%'.$payload['keywords'].'%')->orwhere('us.name','LIKE','%'.$payload['keywords'] . '%');
                }
                if ($request->has('group_name')) {
                    $query->where('afg.name',$payload['group_name']);
                }
            })
            ->where('afc.change_state', '!=', 237)->where('afc.change_state', '!=', 238)
            ->where('approver_type',247)->where('role_users.user_id',$userId)
            ->orderBy('ph.created_at', 'desc')
            ->select('afb.form_instance_number', 'afb.form_status', 'ph.title', 'us.name', 'ph.created_at', 'ph.id', 'afc.change_at','us.icon_url','dds.icon','dds.name as approval_status_name')->get()->toArray();

        $resArr = array_merge($dataUser, $dataUserInfo);
        return $resArr;
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
                if ($request->has('keywords')) {
                    $query->where('afe.form_instance_number', 'LIKE', '%' . $payload['keywords'].'%')->orwhere('users.name', 'LIKE', '%' . $payload['keywords'] . '%');
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
                ->join("data_dictionaries as dds",function ($join){
                    $join->on("dds.id",'=','afb.form_status');
                })
                ->where(function ($query) use ($payload, $request) {
                    if ($request->has('keywords')) {
                        $query->where('afb.form_instance_number', 'LIKE', '%' . $payload['keywords'].'%')->orwhere('us.name', 'LIKE', '%' . $payload['keywords'] . '%');
                    }
                })
                ->whereIn('afb.form_status', $payload['status'])->where('afp.notice_type', 245)->where('afp.notice_id', $userId)
                ->orderBy('afp.created_at', 'desc')
                ->select('afb.form_instance_number', 'cs.title', 'us.name', 'us.name','us.icon_url', 'afp.created_at', 'afb.form_status', 'cs.id','dds.icon','dds.name as approval_status_name')->get()->toArray();

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
                ->join("data_dictionaries as dds",function ($join){
                    $join->on("dds.id",'=','afb.form_status');
                })
                ->where(function ($query) use ($payload, $request) {
                    if ($request->has('keywords')) {
                        $query->where('afb.form_instance_number', 'LIKE', '%' . $payload['keywords'].'%')->orwhere('us.name', 'LIKE', '%' . $payload['keywords'] . '%');
                    }
                })
                ->whereIn('afb.form_status', $payload['status'])->where('afe.notice_type', 247)->where('u.id', $userId)
                ->orderBy('ph.created_at', 'desc')
                ->select('ph.id', 'afe.form_instance_number', 'afe.notice_type', 'afb.form_status', 'ph.title', 'us.name','us.icon_url', 'ph.created_at','dds.icon','dds.name as approval_status_name')->get()->toArray();


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
                ->join("data_dictionaries as dds",function ($join){
                    $join->on("dds.id",'=','bu.form_status');
                })
                ->where(function ($query) use ($payload, $request) {
                    if ($request->has('keywords')) {
                        $query->where('bu.form_instance_number', 'LIKE', '%' . $payload['keywords'].'%')->orwhere('creator.name', 'LIKE', '%' . $payload['keywords'] . '%');
                    }
                })
                ->where('dp.user_id', $userId)
                ->whereIn('bu.form_status', $payload['status'])
                ->orderBy('ph.created_at', 'desc')
                ->select('ph.id', 'afe.form_instance_number', 'afe.notice_type', 'bu.form_status', 'ph.title', 'creator.name','creator.icon_url', 'ph.created_at','dds.icon','dds.name as approval_status_name')->get()->toArray();

            $resArr = array_merge($dataPrincipal, $dataUser, $dataRole);
        } else {
            $resArr = $this->thenNotifyApproval();
        }

        $start = ($payload['page'] - 1) * $pageSize;//偏移量，当前页-1乘以每页显示条数
        $article = array_slice($resArr, $start, $pageSize);

        $count = count($resArr);//总条数
        $totalPages = ceil($count / $pageSize) ?? 1;

        $arr = array();
        $arr['total'] = $count;
        $arr['data'] = $article;
        $arr['meta']['pagination'] = [
            'total' => $count,
            'count' => $payload['page'] < $totalPages ? $pageSize : $count - (($payload['page'] - 1) * $pageSize),
            'per_page' => $pageSize,
            'current_page' => $payload['page'],
            'total_pages' => $totalPages == 0 ? 1 : $totalPages,
        ];

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
            ->join("data_dictionaries as dds",function ($join){
                $join->on("dds.id",'=','afb.form_status');
            })
            ->where('afc.notice_type', '!=', 237)->where('afc.notice_type', '!=', 238)->where('afc.notice_id', $userId)
            ->where('afb.form_status', '!=', 231)
            ->orderBy('ph.created_at', 'desc')
            ->groupBy('afb.form_instance_number')
            ->select('ph.id', 'afb.form_instance_number', 'afb.form_status', 'ph.title', 'us.name', 'ph.created_at','us.icon_url','dds.icon','dds.name as approval_status_name')->get()->toArray();

        return $dataUser;
    }

    // 获取一般审批表单
    public function getForm(Request $request, ApprovalForm $approval)
    {
        $num = $request->get('number', null);
        return $this->response->item($approval, new ApprovalFormTransformer($num));
    }

    // todo 增加归档详情内容
    private function getInstance($instance)
    {
        $num = $instance->form_instance_number;
        $result = $this->response->item($instance, new ApprovalInstanceTransformer());

        $data = Control::where('form_id', $instance->form_id)->where('control_id', '!=', 88)->where('pid', 0)->orderBy('sort_number')->get();
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

        $form = $instance->form;
        if ($form->group_id == 2) {
            $contract = Contract::where('form_instance_number', $num)->first();
            $result->addMeta('contract', $contract->contract_number);
            if ($contract->status) {
                $archives = new Fractal\Resource\Collection($contract->archives, new ContractArchiveTransformer());
                $result->addMeta('contract_archive', [
                    'comment' => $contract->comment,
                    'archives' => $manager->createData($archives)->toArray()
                ]);
            }
        }

        // todo 明细单列
        $detailControl = Control::where('form_id', $instance->form_id)->where('control_id', 88)->first();

        if ($detailControl) {
            $detailArr = [];
            foreach (DetailValue::where('form_instance_number', $num)->cursor() as $item) {
                $detailArr[$item->sort_number][] = [
                    'key' => $item->key,
                    'values' => [
                        'data' => [
                            'value' => $item->value
                        ]
                    ]
                ];
            }
            $result->addMeta('detail_control', $detailArr);
        }

        return $result;
    }

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

        unset($value);

//        $resource = new Fractal\Resource\Collection($data, new TemplateFieldHistoriesTransformer($project->id));
//
//        $manager = new Manager();
//        $manager->setSerializer(new DataArraySerializer());
        ////////////////////////////////////////////////////////////

        //查找关联信息
        $projectInfo = DB::table('project_template_fields as ptf')
            ->join('template_field_value_histories as tfvh', function ($join) {
                $join->on('tfvh.field_id', '=', 'ptf.id');
            })
            ->select('*')
            ->where('tfvh.project_id', $project->id)->get()->toArray();
        $data = json_decode(json_encode($projectInfo), true);

        if ($data) {
            $arr = array();
            foreach ($data as $value) {
                $arr['data']['key'][] = $value['key'];
                $arr['data']['values'][] = $value['value'];
                $info = array_combine($arr['data']['key'], $arr['data']['values']);
            }
            $fields = DB::table('project_template_fields as ptf')->select('ptf.content')->where('ptf.id', 42)->first();
            $fieldValue = json_decode(json_encode($fields), true);
            $fieldValues = explode('|', $fieldValue['content']);
            $list = array_flip($fieldValues);

            $strArr = array();
            foreach ($info as $key => $value) {
                $tmp = array();
                $tmp['key'] = $key;
                if ($key == '状态') {
                    $d = $value - 1;

                    $tmp['values']['data']['value'] = $key = array_search($d, $list);;
                } else {
                    $tmp['values']['data']['value'] = $value;

                }
                $strArr[] = $tmp;
            }
        } else {
            $strArr = array();
        }
        $projectArr = DB::table('project_histories as ph')
            ->leftjoin('trails', function ($join) {
                $join->on('ph.trail_id', '=', 'trails.id');
            })
            ->leftjoin('trail_star', function ($join) {
                $join->on('trail_star.trail_id', '=', 'trails.id');
            })
            ->join('users', function ($join) {
                $join->on('users.id', '=', 'ph.principal_id');
            })
//            ->join('stars', function ($join) {
//                $join->on('stars.id', '=', 'trail_star.starable_id')->where('trail_star.type',1);
//            })
//            ->join('bloggers', function ($join) {
//                $join->on('bloggers.id', '=', 'trail_star.starable_id')->where('trail_star.type',1);
//            })
            ->select('trails.id', 'trails.title', 'ph.priority', 'ph.projected_expenditure', 'ph.start_at', 'ph.end_at', 'ph.desc', 'trail_star.starable_type', 'trail_star.starable_id', 'trails.fee', 'trails.cooperation_type', 'trails.status', 'users.name as principal_name', 'trails.title', 'trails.resource_type', 'trails.resource')
            ->where('ph.id', $project->id)->where('trail_star.type', 1)->get()->toArray();
        $data1 = json_decode(json_encode($projectArr), true);
        //目标艺人
        $arrName = array();
        foreach ($data1 as $value) {
            if ($value['starable_type'] == 'blogger') {
                $arrName[] = DB::table('bloggers')->select('nickname')->where('bloggers.id', $value['starable_id'])->get()->toArray();

            } else {
                $arrName[] = DB::table('stars')->select('name')->where('stars.id', $value['starable_id'])->get()->toArray();
            }

        }

        $dataName = json_decode(json_encode($arrName), true);
        if ($dataName) {
            foreach ($dataName as $value) {
                foreach ($value as $kal) {
                    foreach ($kal as $g) {
                        $s[] = $g;
                    }
                }
            }
        } else {
            $s = array();
        }
        //获取关联项目来源
        $dictionaries = DB::table('data_dictionaries as dds')->select('dds.val', 'dds.name')->where('dds.parent_id', 49)->get()->toArray();
        $dictionariesValue = json_decode(json_encode($dictionaries), true);
        foreach ($data1 as $value) {

            $tmpsArr = array();

            if ($value['resource_type'] == 4 || $value['resource_type'] == 5) {

                $tmpsArr['key'] = DB::table('data_dictionaries as dds')->select('dds.name')->where('dds.val', $value['resource_type'])->where('dds.parent_id', 37)->first();

                $userNmae = DB::table('users')->select('users.name')->where('users.id', hashid_decode($value['resource']))->first();
                $tmpsArr['value'] = $userNmae->name;
            } else {

                $tmpsArr['key'] = DB::table('data_dictionaries as dds')->select('dds.name')->where('dds.val', $value['resource_type'])->where('dds.parent_id', 37)->first();
                $tmpsArr['value'] = $value['resource'];
            }
            $str1Arr = $tmpsArr['key']->name . '-' . $tmpsArr['value'];
        }
        //优先级查找匹配
        if ($data1[0]['priority'] !== '') {
            //查询数据字典优先级
            $dictionaries = DB::table('data_dictionaries as dds')->select('dds.val', 'dds.name')->where('dds.parent_id', 49)->get()->toArray();
            $dictionariesArr = json_decode(json_encode($dictionaries), true);

            if ($dictionariesArr) {
                foreach ($dictionariesArr as $dvalue) {
                    if ($data1[0]['priority'] == $dvalue['val']) {
                        $priority = $dvalue['name'];
                    }
                }
            }
        } else {

            $priority = '';
        }

        // 合作类型
        if ($data1[0]['cooperation_type'] !== '') {
            $cooperation = DB::table('data_dictionaries as dds')
                ->where('dds.parent_id', 28)
                ->where('dds.val', $data1[0]['cooperation_type'])
                ->value('dds.name');
        } else {
            $cooperation = null;
        }

        // 线索状态
        if ($data1[0]['cooperation_type'] !== '') {
            $status = DB::table('data_dictionaries as dds')
                ->where('dds.parent_id', 488)
                ->where('dds.val', $data1[0]['status'])
                ->value('dds.name');
        } else {
            $status = null;
        }
        $tmpArr['key'] = '关联销售线索';
        $tmpArr['values']['data']['value'] = isset($data1[0]['title']) ? $data1[0]['title'] : null;
        $tmpArr1['key'] = '优先级';
        $tmpArr1['values']['data']['value'] = isset($data1[0]['priority']) ? $priority : null;
        $tmpArr2['key'] = '预计支出';
        $tmpArr2['values']['data']['value'] = isset($data1[0]['projected_expenditure']) ? $data1[0]['projected_expenditure'] : null;
        $tmpArr3['key'] = '开始时间';
        $tmpArr3['values']['data']['value'] = isset($data1[0]['start_at']) ? $data1[0]['start_at'] : null;
        $tmpArr4['key'] = '结束时间';
        $tmpArr4['values']['data']['value'] = isset($data1[0]['end_at']) ? $data1[0]['end_at'] : null;
        $tmpArr5['key'] = '备注';
        $tmpArr5['values']['data']['value'] = isset($data1[0]['desc']) ? $data1[0]['desc'] : null;
        $tmpArr6['key'] = '目标艺人';
        $tmpArr6['values']['data']['value'] = isset($arrName) ? implode(",", $s) : null;
        $tmpArr7['key'] = '预计订单收入';
        $tmpArr7['values']['data']['value'] = isset($data1[0]['fee']) ? $data1[0]['fee'] : null;//
        $tmpArr8['key'] = '负责人';
        $tmpArr8['values']['data']['value'] = isset($data1[0]['principal_name']) ? $data1[0]['principal_name'] : null;//title
        $tmpArr9['key'] = '项目来源';
        $tmpArr9['values']['data']['value'] = isset($str1Arr) ? $str1Arr : null;//title
        $tmpArr10['key'] = '合作类型';
        $tmpArr10['values']['data']['value'] = isset($data1[0]['cooperation_type']) ? $cooperation : null;//合作类型
        $tmpArr11['key'] = '状态';
        $tmpArr11['values']['data']['value'] = isset($data1[0]['status']) ? $status: null;//状态

        array_push($strArr, $tmpArr7);
        array_push($strArr, $tmpArr);
        array_push($strArr, $tmpArr1);
        array_push($strArr, $tmpArr2);
        array_push($strArr, $tmpArr3);
        array_push($strArr, $tmpArr4);
        array_push($strArr, $tmpArr5);
        array_push($strArr, $tmpArr6);
        array_push($strArr, $tmpArr8);
        array_push($strArr, $tmpArr9);
        array_push($strArr, $tmpArr10);
        array_push($strArr, $tmpArr11);

        ////////////////////////////////////////////////////////////
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
        $resArr['data'] = $strArr;
        $result->addMeta('fields', $resArr);
        $result->addMeta('approval', $project);
        $result->addMeta('notice', ['data' => $participant]);

        return $result;
    }

    // 获取group里的form_ids => 改为只给合同使用
    public function getContractForms(Request $request)
    {
        //2 表示合同审批
        $forms = ApprovalForm::where('group_id', 2)->orderBy('sort_number', 'asc')->select('form_id', 'name', 'change_type', 'modified')->get();

        return $this->response->collection($forms, new ApprovalFormTransformer());
    }

    public function getGeneralForms(GeneralFormsRequest $request)
    {
        $default_except = [1,2];
        $form_group_id = $request->get('form_group_id',null);
        $except_form_group_id = $request->get('except_form_group_id',null);
        if ($form_group_id != null){
            $groups = ApprovalGroup::where('id', $form_group_id)->orderBy('sort_number')->get();
        }else{
            $except_form_group_id = array_merge($default_except,[$except_form_group_id]);
            $except_form_group_id = array_filter($except_form_group_id);
            $groups = ApprovalGroup::whereNotIn('id', $except_form_group_id)->orderBy('sort_number')->get();
        }
        return $this->response->collection($groups, new ApprovalGroupTransformer());
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
        if ($approval->group_id == 2)//合同审批
            $type = 1;
        else
            $type = 0;

        // 按合同规定艺人or博主
        if (in_array($approval->form_id, [5, 6, 10]))//Papi签约合同，Papi解约合同，Papi项目合同
            $this->starType = 'bloggers';

        if (in_array($approval->form_id, [7, 8, 9]))//泰洋签约合同，泰洋解约合同，泰洋项目合同
            $this->starType = 'stars';

        $user = Auth::guard('api')->user();

        DB::beginTransaction();
        try {
            // todo 待验证   一般审批可以自定义
            if ($approval->change_type == 223 && count($chains) > 0) {
                $flow = new ApprovalFlowController();
                $flow->storeFreeChains($chains, $num);
            }

            if ($type) {
                $contract = Contract::create([//创建合同
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
                    'created_by' => $user->name,
                    'created_at' => Carbon::now(),
                    'updated_at' => Carbon::now(),
                ]);
            }
            // 操作日志
            $operate = new OperateEntity([
                'obj' => $instance,
                'title' => null,
                'start' => null,
                'end' => null,
                'method' => OperateLogMethod::CREATE,
            ]);
            event(new OperateLogEvent([
                $operate
            ]));

            foreach ($controlValues as $value) {
                $this->instanceValueStore($num, $value['key'], $value['value'], $value['type'], $approval);
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
            //记录日志
            //泰洋项目合同，papi醒目合同
            if ($approval->form_id == 9 || $approval->form_id == 10) {
                foreach ($controlValues as $value) {
                    if ($value['type'] == "project_id") {
                        $project = Project::find(hashid_decode($value['value']['id']));
                        if ($project) {
                            $operate = new OperateEntity([
                                'obj' => $project,
                                'title' => null,
                                'start' => null,
                                'end' => null,
                                'method' => OperateLogMethod::CREATE_CONTRACTS,
                            ]);
                            event(new OperateLogEvent([
                                $operate,
                            ]));
                        }
                    }
                }
            }

            if ($approval->form_id == 9 || $approval->form_id == 10) {
                foreach ($controlValues as $value) {
                    if ($value['type'] == "project_id") {
                        $project = Project::find(hashid_decode($value['value']['id']));
                        if ($project) {
                            $operate = new OperateEntity([
                                'obj' => $project,
                                'title' => null,
                                'start' => null,
                                'end' => null,
                                'method' => OperateLogMethod::CREATE_CONTRACTS,
                            ]);
                            event(new OperateLogEvent([
                                $operate,
                            ]));
                        }
                    }
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
        //向知会人发消息
        $authorization = $request->header()['authorization'][0];
        $curr_user = Auth::guard('api')->user();
        event(new ApprovalMessageEvent($instance, ApprovalTriggerPoint::NOTIFY, $authorization, $curr_user));
        event(new ApprovalMessageEvent($instance, ApprovalTriggerPoint::WAIT_ME, $authorization, $curr_user));
        // 发送消息
        DB::beginTransaction();
        try {

            $user = Auth::guard('api')->user();
            $title = $project->title . "项目成单了";  //通知消息的标题
            $subheading = $project->title . "项目成单了";
            $module = Message::PROJECT;
            $link = URL::action("ProjectController@detail", ["project" => $project->id]);
            $data = [];
            $data[] = [
                "title" => '项目名称', //通知消息中的消息内容标题
                'value' => $project->title,  //通知消息内容对应的值
            ];
            $principal = User::findOrFail($project->principal_id);
            $data[] = [
                'title' => '项目负责人',
                'value' => $principal->name
            ];
            //发送给创建人的直属领导
            $department = DepartmentUser::where('user_id', $user->id)->first();
            $leader = DepartmentUser::where('department_id', $department->id)->where('type', 1)->first();
            $send_user = [$leader->id];
            $authorization = $request->header()['authorization'][0];

            (new MessageRepository())->addMessage($user, $authorization, $title, $subheading, $module, $link, $data, $send_user, $project->id);
        } catch (Exception $e) {
            Log::error($e);
            DB::rollBack();
        }

        return $this->response->created();
    }

    /**
     * todo 暂为硬编码
     * @param GetContractFormRequest $request
     * $request->type projects stars bloggers
     * $request->status 1: 泰洋项目 签约 2：papi项目 解约
     * @return \Dingo\Api\Http\Response
     */
    public function getContractForm(GetContractFormRequest $request)
    {
        $type = $request->get('type');
        $status = $request->get('status');

        if ($type == 'projects' && $status == 1)
            $form = ApprovalForm::where('form_id', 9)->select('form_id', 'name', 'change_type', 'modified')->first();
        elseif ($type == 'projects' && $status == 2)
            $form = ApprovalForm::where('form_id', 10)->select('form_id', 'name', 'change_type', 'modified')->first();
        elseif ($type == 'stars' && $status == 1)
            $form = ApprovalForm::where('form_id', 7)->select('form_id', 'name', 'change_type', 'modified')->first();
        elseif ($type == 'stars' && $status == 2)
            $form = ApprovalForm::where('form_id', 8)->select('form_id', 'name', 'change_type', 'modified')->first();
        elseif ($type == 'bloggers' && $status == 1)
            $form = ApprovalForm::where('form_id', 5)->select('form_id', 'name', 'change_type', 'modified')->first();
        elseif ($type == 'bloggers' && $status == 2)
            $form = ApprovalForm::where('form_id', 6)->select('form_id', 'name', 'change_type', 'modified')->first();
        else
            return $this->response->errorBadRequest('参数错误');

        return $this->response->item($form, new ApprovalFormTransformer());
    }

    // todo 分支写的太差，优化结构
    private function instanceValueStore($num, $key, $value, $type = null, $approval)
    {
        try {
            $key = hashid_decode($key);
            $controlId = Control::where('form_control_id', $key)->value('control_id');
            if ($controlId == 88) {
                foreach ($value as $sort => $item) {
                    foreach ($item as $control) {
                        $keyStr = ControlProperty::where('form_control_id', hashid_decode($control['key']))->where('property_id', 67)->value('property_value');
                        DetailValue::create([
                            'form_instance_number' => $num,
                            'key' => $keyStr,
                            'value' => $control['value'],
                            'sort_number' => $sort
                        ]);
                    }
                }
            } else {
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
                        if ($type == 'stars') {
                            $this->contract->update([
                                'star_type' => $this->starType
                            ]);
                            $this->createTalentContractOperateLog($ids, $approval);
                        }

                        if (in_array($type, ['project_id', 'client_id', 'stars']))
                            $this->contract->update([
                                $type => $ids
                            ]);
                        else
                            $this->contract->update([
                                $type => $value
                            ]);

                        if ($type == 'type') {
                            $dataType = $this->formatType($value);
                            $this->contract->update([
                                'type' => $dataType
                            ]);
                        }
                    }
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

        // 多文件上传时
        if (array_key_exists('fileUrl', $value[0])) {
            $str = '';
            foreach ($value as $item) {
                $str .= $item['fileUrl'] . ',';
            }
            $value = rtrim($str, ',');
            return [$value, ''];
        }

        // 多选框时
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
                $string = $this->company . '-' . $this->type;
                break;
            default:
                throw new Exception('合同编号生成错误');
                break;
        }

        return $string;
    }

    private function formatType($type)
    {
        if (strpos($type, '收入') !== false) {
            $this->type = 'SR';
            return '收入';
        } elseif (strpos($type, '成本') !== false) {
            $this->type = 'ZC';
            return '成本';
        } elseif (strpos($type, '无金额') !== false) {
            $this->type = 'W';
            return '无金额';
        } else
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

        $principal = DepartmentPrincipal::where('user_id', $userId)->first();
        $flag = 0;
        if (!is_null($principal)) {
            $flag = 1;
        }


        $executeInfo = ChainFixed::where('form_id', $formId)->where('condition_id', $conditionId)->orderBy('sort_number')->first();
        if (is_null($executeInfo))
            $executeInfo = ChainFree::where('form_number', $num)->orderBy('sort_number')->first();

        if (is_null($executeInfo))
            throw new ApprovalVerifyException('审批流不存在');

        try {
            $executeArray = [
                'form_instance_number' => $num,
                'current_handler_id' => $executeInfo->next_id,
                'current_handler_type' => $executeInfo->approver_type ?? 245,
                'flow_type_id' => DataDictionarie::FORM_STATE_DSP,
                'principal_level' => $executeInfo->principal_level + $flag,
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

    private function createTalentContractOperateLog($ids, $approval)
    {
        $formId = $approval->form_id;
        $idArr = explode(',', $ids);
        switch ($formId) {
            case 5:
                foreach ($idArr as $id) {
                    $blogger = Blogger::find($id);
                    // 操作日志
                    $operate = new OperateEntity([
                        'obj' => $blogger,
                        'title' => null,
                        'start' => null,
                        'end' => null,
                        'method' => OperateLogMethod::CREATE_SIGNING_CONTRACTS,
                    ]);
                    event(new OperateLogEvent([
                        $operate
                    ]));
                }
                break;
            case 6:
                foreach ($idArr as $id) {
                    $blogger = Blogger::find($id);
                    // 操作日志
                    $operate = new OperateEntity([
                        'obj' => $blogger,
                        'title' => null,
                        'start' => null,
                        'end' => null,
                        'method' => OperateLogMethod::CREATE_RESCISSION_CONTRACTS,
                    ]);
                    event(new OperateLogEvent([
                        $operate
                    ]));
                }
                break;
            case 7:
                foreach ($idArr as $id) {
                    $star = Star::find($id);
                    // 操作日志
                    $operate = new OperateEntity([
                        'obj' => $star,
                        'title' => null,
                        'start' => null,
                        'end' => null,
                        'method' => OperateLogMethod::CREATE_SIGNING_CONTRACTS,
                    ]);
                    event(new OperateLogEvent([
                        $operate
                    ]));
                }
                break;
            case 8:
                foreach ($idArr as $id) {
                    $star = Star::find($id);
                    // 操作日志
                    $operate = new OperateEntity([
                        'obj' => $star,
                        'title' => null,
                        'start' => null,
                        'end' => null,
                        'method' => OperateLogMethod::CREATE_RESCISSION_CONTRACTS,
                    ]);
                    event(new OperateLogEvent([
                        $operate
                    ]));
                }
                break;
            default:
                break;
        }
    }

    public function pendingSum(Request $request)
    {
        //项目立项我审批的数量
        $projectRes = $this->myApproval($request);
        $projectCount = $projectRes['meta']['pagination']['count'];

        //合同我审批的数量
        $contract = new ApprovalContractController();
        $contractRes = $contract->myApproval($request);
        $contractCount = $contractRes['meta']['pagination']['count'];

        //一般我审批的数量
        $general = new ApprovalGeneralController();
        $generalRes = $general->myApproval($request);
        $generalCount = $generalRes['meta']['pagination']['count'];

        $array = array();
        $array['project'] = $projectCount;
        $array['contract'] = $contractCount;
        $array['general'] = $generalCount;

        return $array;

    }

    public function getFilter(FilterRequest $request)
    {
        $payload = $request->all();
        $pageSize = $request->get('page_size', config('app.page_size'));
        $status = $request->get('status', config('app.status'));
        $payload['page'] = isset($payload['page']) ? $payload['page'] : 1;
        $joinSql = FilterJoin::where('table_name', 'projects')->first()->join_sql;
        $query = Contract::selectRaw('DISTINCT(ps.id) as ids')->from(DB::raw($joinSql));
        $contracts = $query->where(function ($query) use ($payload) {
            FilterReportRepository::getTableNameAndCondition($payload,$query);
        });
       
        $array = [];//查询条件
        if ($request->has('number'))

            $array[] = ['cs.contract_number','like','%'.$payload['number'].'%'];
        if ($request->has('type'))
            $array[] = ['trails.type',$payload['type']];
        if ($request->has('keyword'))

            $array[] = ['cs.title','like','%'.$payload['keyword'].'%'];

        $projectsInfo = $contracts->searchData()->where($array)->orderBy('cs.created_at', 'desc')
            ->select('cs.contract_number', 'afb.form_instance_number', 'cs.title', 'af.name as form_name', 'cs.creator_name as name', 'cs.created_at', 'afb.form_status')->distinct()->get()->toArray();

        $start = ($payload['page'] - 1) * $pageSize;//偏移量，当前页-1乘以每页显示条数
        $article = array_slice($projectsInfo, $start, $pageSize);

        $total = count($article);//总条数
        $totalPages = ceil($total / $pageSize);

        $arr = array();
        $arr['data'] = $article;
        $arr['meta']['pagination'] = [
            'total' => $total,
            'count' => $payload['page'] < $totalPages ? $pageSize : $total - (($payload['page'] - 1) * $pageSize),
            'per_page' => $pageSize,
            'current_page' => $payload['page'],
            'total_pages' => $totalPages == 0 ? 1 : $totalPages,
        ];

        return $arr;
    }
}
