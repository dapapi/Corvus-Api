<?php

namespace App\Http\Controllers;

use App\Helper\Generator;
use App\Http\Requests\Approval\GetFormIdsRequest;
use App\Http\Requests\Approval\InstanceStoreRequest;
use App\Http\Transformers\ApprovalFormTransformer;
use App\Http\Transformers\GeneralApprovalTransformer;
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

class ApprovalGeneralController extends Controller
{


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

        $data = DB::table('approval_form_instances as afi')
            ->join('users', function ($join) {
                $join->on('afi.apply_id', '=', 'users.id');
            })
            ->join('approval_forms as af', function ($join) {
                $join->on('af.form_id', '=', 'afi.form_id');
            })
            ->join('approval_form_groups as afg', function ($join) {
                $join->on('afg.id', '=', 'af.group_id');
            })
            ->join("data_dictionaries as dds",function ($join){
                $join->on("dds.id",'=','afi.form_status');
            })
            ->where(function ($query) use ($payload, $request) {
                if ($request->has('keywords')) {
                    $query->where('afi.form_instance_number', 'LIKE','%'.$payload['keywords'].'%')->orwhere('users.name','LIKE','%'.$payload['keywords'] . '%')->orwhere('afg.name','LIKE','%'.$payload['keywords'].'%');
                }
                if ($request->has('group_name')) {
                    $query->where('afg.name',$payload['group_name']);
                }
            })
            ->where('afi.apply_id', $user->id)
            ->whereIn('afi.form_status', $payload['status'])
            ->orderBy('afi.created_at', 'desc')
            ->select('afi.*', 'users.name', 'afg.name as group_name','users.icon_url', 'afg.id as group_id','dds.name as approval_status_name','dds.icon')
            ->paginate($pageSize);

        return $this->response->paginator($data, new GeneralApprovalTransformer());

    }


    public function myApproval(Request $request)
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



            //查询角色
            $dataRole = DB::table('approval_flow_execute as afe')//
            ->join('role_users as ru', function ($join) {
                $join->on('afe.current_handler_id', '=', 'ru.role_id');
            })
                ->join('users as u', function ($join) {
                    $join->on('ru.user_id', '=', 'u.id');
                })
                ->join('approval_form_instances as afi', function ($join) {
                    $join->on('afe.form_instance_number', '=', 'afi.form_instance_number');
                })
                ->join('approval_forms as af', function ($join) {
                    $join->on('af.form_id', '=', 'afi.form_id');
                })
                ->join('approval_form_groups as afg', function ($join) {
                    $join->on('afg.id', '=', 'af.group_id');
                })
                ->join('users as us', function ($join) {
                    $join->on('afi.apply_id', '=', 'us.id');
                })
                ->join("data_dictionaries as dds",function ($join){
                    $join->on("dds.id",'=','afi.form_status');
                })
                ->where(function ($query) use ($payload, $request) {
                    if ($request->has('keywords')) {
                        $query->where('afi.form_instance_number', 'LIKE','%'.$payload['keywords'].'%')->orwhere('us.name','LIKE','%'.$payload['keywords'] . '%')->orwhere('afg.name','LIKE','%'.$payload['keywords'].'%');
                    }
                    if ($request->has('group_name')) {
                        $query->where('afg.name',$payload['group_name']);
                    }
                })
                ->whereIn('afe.flow_type_id', $payload['status'])->where('afe.current_handler_type', 247)->where('u.id', $userId)
                ->orderBy('afi.created_at', 'desc')
                ->select('afe.form_instance_number', 'afe.flow_type_id as form_status', 'afi.*', 'afg.name as group_name', 'afg.id as group_id','us.name','us.icon_url','dds.name as approval_status_name','dds.icon')->get()->toArray();
            //->paginate($pageSize)->toArray();

            //查询个人
            $dataUser = DB::table('approval_flow_execute as afe')//

            ->join('users as u', function ($join) {
                $join->on('afe.current_handler_id', '=', 'u.id');
            })
                ->join('approval_form_instances as afi', function ($join) {
                    $join->on('afe.form_instance_number', '=', 'afi.form_instance_number');
                })
                ->join('approval_forms as af', function ($join) {
                    $join->on('af.form_id', '=', 'afi.form_id');
                })
                ->join('approval_form_groups as afg', function ($join) {
                    $join->on('afg.id', '=', 'af.group_id');
                })
                ->join('users as us', function ($join) {
                    $join->on('afi.apply_id', '=', 'us.id');
                })
                ->join("data_dictionaries as dds",function ($join){
                    $join->on("dds.id",'=','afi.form_status');
                })
                ->where(function ($query) use ($payload, $request) {
                    if ($request->has('keywords')) {
                        $query->where('afi.form_instance_number', 'LIKE','%'.$payload['keywords'].'%')->orwhere('us.name','LIKE','%'.$payload['keywords'] . '%')->orwhere('afg.name','LIKE','%'.$payload['keywords'].'%');
                    }
                    if ($request->has('group_name')) {
                        $query->where('afg.name',$payload['group_name']);
                    }
                })

                ->whereIn('afe.flow_type_id', $payload['status'])->where('afe.current_handler_type', 245)->where('u.id', $userId)
                ->orderBy('afi.created_at', 'desc')
                ->select('afe.form_instance_number', 'afe.flow_type_id as form_status', 'afi.*', 'afg.name as group_name', 'afg.id as group_id','us.name','us.icon_url','dds.name as approval_status_name','dds.icon')->get()->toArray();


            //部门负责人
            $dataPrincipal = DB::table('approval_flow_execute as afe')//

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
                ->join('approval_form_instances as afi', function ($join) {
                    $join->on('afe.form_instance_number', '=', 'afi.form_instance_number');
                })
                ->join('approval_forms as af', function ($join) {
                    $join->on('af.form_id', '=', 'afi.form_id');
                })
                ->join('approval_form_groups as afg', function ($join) {
                    $join->on('afg.id', '=', 'af.group_id');
                })
                ->join('users as us', function ($join) {
                    $join->on('afi.apply_id', '=', 'us.id');
                })
                ->join("data_dictionaries as dds",function ($join){
                    $join->on("dds.id",'=','afi.form_status');
                })
                ->where(function ($query) use ($payload, $request) {
                    if ($request->has('keywords')) {
                        $query->where('afi.form_instance_number', 'LIKE','%'.$payload['keywords'].'%')->orwhere('us.name','LIKE','%'.$payload['keywords'] . '%')->orwhere('afg.name','LIKE','%'.$payload['keywords'].'%');
                    }
                    if ($request->has('group_name')) {
                        $query->where('afg.name',$payload['group_name']);
                    }
                })
                ->where('dp.user_id', $userId)
                ->whereIn('afe.flow_type_id', $payload['status'])
                ->orderBy('afi.created_at', 'desc')
                ->select('afe.form_instance_number', 'afe.flow_type_id as form_status', 'afi.*', 'afg.name as group_name', 'afg.id as group_id','us.name','us.icon_url','dds.name as approval_status_name','dds.icon')->get()->toArray();

            //查询二级主管
            $dataPrincipalLevel = DB::table('approval_flow_execute as afe')//

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
                    //$join->on('dp.department_id', '=', 'du.department_id')->where('afe.current_handler_type', '=', 246);
//                    DB::raw("department_principal as `dp` on `dp`.`department_id` = (
//select department_pid from department_principal as dep
//left join department_user as du on du.`department_id`=dep.`department_id` where du.user_id=afi.`apply_id`)");
                    DB::raw("select dpl.`user_id` from department_user as dur 
                        left join  departments as ds ON dur.`department_id`=ds.`id`
                        left join  department_principal as dpl ON dpl.`department_id`=ds.`department_pid`
                        where dur.`user_id`=afi.`apply_id`");

                })

                ->join('approval_form_instances as afi', function ($join) {
                    $join->on('afe.form_instance_number', '=', 'afi.form_instance_number');
                })
                ->join('approval_forms as af', function ($join) {
                    $join->on('af.form_id', '=', 'afi.form_id');
                })
                ->join('approval_form_groups as afg', function ($join) {
                    $join->on('afg.id', '=', 'af.group_id');
                })
                ->join('users as us', function ($join) {
                    $join->on('afi.apply_id', '=', 'us.id');
                })
                ->join("data_dictionaries as dds",function ($join){
                    $join->on("dds.id",'=','afi.form_status');
                })
                ->where(function ($query) use ($payload, $request) {
                    if ($request->has('keywords')) {
                        $query->where('afi.form_instance_number', 'LIKE','%'.$payload['keywords'].'%')->orwhere('us.name','LIKE','%'.$payload['keywords'] . '%')->orwhere('afg.name','LIKE','%'.$payload['keywords'].'%');
                    }
                    if ($request->has('group_name')) {
                        $query->where('afg.name',$payload['group_name']);
                    }
                })
                ->where('dp.user_id', $userId)->where('afe.principal_level',2)
                ->whereIn('afe.flow_type_id', $payload['status'])
                ->orderBy('afi.created_at', 'desc')
                ->select('afe.form_instance_number', 'afe.flow_type_id as form_status', 'afi.*', 'afg.name as group_name', 'afg.id as group_id','us.name','us.icon_url','dds.name as approval_status_name','dds.icon')->get()->toArray();

            $resArrs = array_merge($dataPrincipal, $dataUser, $dataRole,$dataPrincipalLevel);
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
            $resArr = $this->thenApproval($request,$payload);
        }

        $start = ($payload['page'] - 1) * $pageSize;//偏移量，当前页-1乘以每页显示条数
        $article = array_slice($resArr, $start, $pageSize);

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
//        foreach ($arr['data'] as $key => &$value) {
//            $value->id = hashid_encode($value->id);
//        }
        return $arr;
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
            $array=explode(",",$v);        //再将拆开的数组重新组装
            $temp2[$k]["form_instance_number"] =$array[0];
            $temp2[$k]["form_status"] =$array[1];
            $temp2[$k]["form_instance_id"] =$array[2];
            $temp2[$k]["form_id"] =$array[3];
            $temp2[$k]["apply_id"] =$array[4];
            $temp2[$k]["created_by"] =$array[5];
            $temp2[$k]["created_at"] =$array[6];
            $temp2[$k]["updated_by"] =$array[7];

            $temp2[$k]["updated_at"] =$array[8];
            $temp2[$k]["order_by"] =$array[9];
            $temp2[$k]["group_name"] =$array[10];
            $temp2[$k]["group_id"] =$array[11];
            $temp2[$k]["name"] =$array[12];
            $temp2[$k]["icon_url"] =$array[13];
            $temp2[$k]["approval_status_name"] =$array[14];
            $temp2[$k]["icon"] =$array[15];

        }
        return $temp2;
    }

    //获取已审批信息
    public function thenApproval($request,$payload)
    {

        $user = Auth::guard('api')->user();
        $userId = 8;
        //查询个人
        $dataUser = DB::table('approval_flow_change as afc')//
        ->join('users as u', function ($join) {
            $join->on('afc.change_id', '=', 'u.id');
        })
            ->join('approval_form_instances as afi', function ($join) {
                $join->on('afc.form_instance_number', '=', 'afi.form_instance_number');
            })
            ->join('approval_forms as af', function ($join) {
                $join->on('af.form_id', '=', 'afi.form_id');
            })
            ->join('approval_form_groups as afg', function ($join) {
                $join->on('afg.id', '=', 'af.group_id');
            })
            ->join('users as us', function ($join) {
                $join->on('us.id', '=', 'afi.apply_id');
            })
            ->join("data_dictionaries as dds",function ($join){
                $join->on("dds.id",'=','afi.form_status');
            })
            //->where('afe.form_instance_number',$payload['keyword'])->orwhere('us.name', 'LIKE', '%' . $payload['keyword'] . '%')->orwhere('afis.form_control_value', 'LIKE', '%' . $payload['keyword'] . '%')

            ->where(function ($query) use ($payload, $request) {
                if ($request->has('keywords')) {
                    $query->where('afi.form_instance_number', 'LIKE','%'.$payload['keywords'].'%')->orwhere('us.name','LIKE','%'.$payload['keywords'] . '%')->orwhere('afg.name','LIKE','%'.$payload['keywords'].'%');
                }
                if ($request->has('group_name')) {
                    $query->where('afg.name',$payload['group_name']);
                }
            })
            ->where('afc.change_state', '!=', 237)->where('afc.change_state', '!=', 238)->where('afc.change_id', $userId)->orwhere('afc.approver_type','!=',247)
            ->orderBy('afi.created_at', 'desc')
            ->select('afi.*', 'us.name', 'us.icon_url','afg.name as group_name', 'afg.id as group_id','dds.name as approval_status_name','dds.icon')->get()->toArray();

        //查询角色
        //根据user_id 查询角色id

        $dataUserInfo = DB::table('approval_flow_change as afc')
            ->join('role_users', function ($join) {
                $join->on('role_users.role_id', '=','afc.role_id');
            })

            ->join('approval_form_instances as afi', function ($join) {
                $join->on('afc.form_instance_number', '=', 'afi.form_instance_number');
            })
            ->join('approval_forms as af', function ($join) {
                $join->on('af.form_id', '=', 'afi.form_id');
            })
            ->join('approval_form_groups as afg', function ($join) {
                $join->on('afg.id', '=', 'af.group_id');
            })
            ->join('users as us', function ($join) {
                $join->on('us.id', '=', 'afi.apply_id');
            })
            ->join("data_dictionaries as dds",function ($join){
                $join->on("dds.id",'=','afi.form_status');
            })

            ->where(function ($query) use ($payload, $request) {
                if ($request->has('keywords')) {
                    $query->where('afi.form_instance_number', 'LIKE','%'.$payload['keywords'].'%')->orwhere('us.name','LIKE','%'.$payload['keywords'] . '%')->orwhere('afg.name','LIKE','%'.$payload['keywords'].'%');
                }
                if ($request->has('group_name')) {
                    $query->where('afg.name',$payload['group_name']);
                }
            })
            ->where('afc.change_state', '!=', 237)->where('afc.change_state', '!=', 238)
            ->where('role_users.user_id',$userId)
            ->orderBy('afi.created_at', 'desc')
            ->select('afi.*', 'us.name', 'us.icon_url','afg.name as group_name', 'afg.id as group_id','dds.name as approval_status_name','dds.icon')->get()->toArray();

        $resArrs = array_merge($dataUser, $dataUserInfo);
        $resArrInfo = json_decode(json_encode($resArrs), true);

        if(empty($resArrInfo)){
            $resArr = array();
        }else{
            $resArr = $this->array_unique_tl($resArrInfo);
        }
        return $resArr;
    }

    function array_unique_tl($array2D)
    {
        foreach ($array2D as $k=>$v)
        {
            $v = join(",",$v);  //降维,也可以用implode,将一维数组转换为用逗号连接的字符串
            $temp[$k] = $v;
        }
        $temp = array_unique($temp);    //去掉重复的字符串,也就是重复的一维数组
        foreach ($temp as $k => $v)
        {
            $array=explode(",",$v);        //再将拆开的数组重新组装
            $temp2[$k]["form_instance_id"] =$array[0];
            $temp2[$k]["form_id"] =$array[1];
            $temp2[$k]["form_instance_number"] =$array[2];
            $temp2[$k]["apply_id"] =$array[3];
            $temp2[$k]["form_status"] =$array[4];
            $temp2[$k]["created_by"] =$array[5];
            $temp2[$k]["created_at"] =$array[6];
            $temp2[$k]["updated_by"] =$array[7];

            $temp2[$k]["updated_at"] =$array[8];
            $temp2[$k]["order_by"] =$array[9];
            $temp2[$k]["name"] =$array[10];
            $temp2[$k]["icon_url"] =$array[11];
            $temp2[$k]["group_name"] =$array[12];
            $temp2[$k]["group_id"] =$array[13];
            $temp2[$k]["approval_status_name"] =$array[14];
            $temp2[$k]["icon"] =$array[15];

        }
        return $temp2;
    }


    // todo 角色判断
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

            ->join('approval_form_instances as afi', function ($join) {
                $join->on('afp.form_instance_number', '=', 'afi.form_instance_number');
            })
                ->join('approval_forms as af', function ($join) {
                    $join->on('af.form_id', '=', 'afi.form_id');
                })
                ->join('approval_form_groups as afg', function ($join) {
                    $join->on('afg.id', '=', 'af.group_id');
                })
                ->join('users as us', function ($join) {
                    $join->on('afi.apply_id', '=', 'us.id');
                })
                ->where(function ($query) use ($payload, $request) {
                    if ($request->has('keywords')) {
                        $query->where('afi.form_instance_number', 'LIKE','%'.$payload['keywords'].'%')->orwhere('us.name','LIKE','%'.$payload['keywords'] . '%')->orwhere('afg.name','LIKE','%'.$payload['keywords'].'%');
                    }
                    if ($request->has('group_name')) {
                        $query->where('afg.name',$payload['group_name']);
                    }
                })
                ->whereIn('afi.form_status', $payload['status'])->where('afp.notice_type', 245)->where('afp.notice_id', $userId)
                ->orderBy('afi.created_at', 'desc')
                ->select('afi.*', 'us.name', 'us.icon_url','afp.created_at','afg.name as group_name')->get()->toArray();

            //查询角色
            $dataRole = DB::table('approval_form_participants as afe')//
            ->join('role_users as ru', function ($join) {
                $join->on('afe.notice_id', '=', 'ru.role_id');
            })
                ->join('users as u', function ($join) {
                    $join->on('ru.user_id', '=', 'u.id');
                })
                ->join('approval_form_instances as afi', function ($join) {
                    $join->on('afe.form_instance_number', '=', 'afi.form_instance_number');
                })
                ->join('approval_forms as af', function ($join) {
                    $join->on('af.form_id', '=', 'afi.form_id');
                })
                ->join('approval_form_groups as afg', function ($join) {
                    $join->on('afg.id', '=', 'af.group_id');
                })
                ->join('users as us', function ($join) {
                    $join->on('afi.apply_id', '=', 'us.id');
                })
                ->where(function ($query) use ($payload, $request) {
                    if ($request->has('keywords')) {
                        $query->where('afi.form_instance_number', 'LIKE','%'.$payload['keywords'].'%')->orwhere('us.name','LIKE','%'.$payload['keywords'] . '%')->orwhere('afg.name','LIKE','%'.$payload['keywords'].'%');
                    }
                    if ($request->has('group_name')) {
                        $query->where('afg.name',$payload['group_name']);
                    }
                })
                ->whereIn('afi.form_status', $payload['status'])->where('afe.notice_type', 247)->where('u.id', $userId)
                ->orderBy('afi.created_at', 'desc')
                ->select('afe.form_instance_number', 'afe.notice_type', 'afi.form_status', 'us.name', 'afg.name as group_name', 'afg.id as group_id')->get()->toArray();


            //部门负责人
            $dataPrincipal = DB::table('approval_form_participants as afe')//

            ->join('approval_form_instances as afi', function ($join) {
                $join->on('afe.form_instance_number', '=', 'afi.form_instance_number');
            })
                ->join('approval_forms as af', function ($join) {
                    $join->on('af.form_id', '=', 'afi.form_id');
                })
                ->join('approval_form_groups as afg', function ($join) {
                    $join->on('afg.id', '=', 'af.group_id');
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
                ->where(function ($query) use ($payload, $request) {
                    if ($request->has('keywords')) {
                        $query->where('afi.form_instance_number', 'LIKE','%'.$payload['keywords'].'%')->orwhere('creator.name','LIKE','%'.$payload['keywords'] . '%')->orwhere('afg.name','LIKE','%'.$payload['keywords'].'%');
                    }
                    if ($request->has('group_name')) {
                        $query->where('afg.name',$payload['group_name']);
                    }
                })
                ->where('dp.user_id', $userId)
                ->whereIn('afi.form_status', $payload['status'])
                ->orderBy('afi.created_at', 'desc')
                ->select('afi.form_instance_number', 'afe.notice_type', 'afi.form_status', 'creator.name', 'afg.name as group_name', 'afg.id as group_id')->get()->toArray();


            $resArr = array_merge($dataPrincipal, $dataUser, $dataRole);


        } else {
            $resArr = $this->thenNotifyApproval($request,$payload);
        }

        $count = count($resArr);//总条数
        $start = ($payload['page'] - 1) * $pageSize;//偏移量，当前页-1乘以每页显示条数
        $article = array_slice($resArr, $start, $pageSize);

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

    //获取已审批信息
    public function thenNotifyApproval($request,$payload)
    {

        $user = Auth::guard('api')->user();
        $userId = $user->id;
        //查询个人
        $dataUser = DB::table('approval_form_participants as afc')//
        ->join('users as u', function ($join) {
            $join->on('afc.notice_id', '=', 'u.id');
        })
            ->join('approval_form_instances as afi', function ($join) {
                $join->on('afc.form_instance_number', '=', 'afi.form_instance_number');
            })
            ->join('approval_forms as af', function ($join) {
                $join->on('af.form_id', '=', 'afi.form_id');
            })
            ->join('approval_form_groups as afg', function ($join) {
                $join->on('afg.id', '=', 'af.group_id');
            })
            ->join('users as us', function ($join) {
                $join->on('us.id', '=', 'afi.apply_id');
            })
            ->where(function ($query) use ($payload, $request) {
                if ($request->has('keywords')) {
                    $query->where('afi.form_instance_number', 'LIKE','%'.$payload['keywords'].'%')->orwhere('us.name','LIKE','%'.$payload['keywords'] . '%')->orwhere('afg.name','LIKE','%'.$payload['keywords'].'%');
                }
                if ($request->has('group_name')) {
                    $query->where('afg.name',$payload['group_name']);
                }
            })
            ->where('afc.notice_type', '!=', 237)->where('afc.notice_type', '!=', 238)->where('afc.notice_id', $userId)
            ->where('afi.form_status', '!=', 231)
            ->orderBy('afi.created_at', 'desc')
            ->select('afi.form_instance_number', 'afi.form_status', 'us.name', 'afi.created_at', 'afg.name as group_name', 'afg.id as group_id')->get()->toArray();

        return $dataUser;
    }

}
