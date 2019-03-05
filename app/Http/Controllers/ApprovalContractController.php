<?php

namespace App\Http\Controllers;

use App\Http\Transformers\ContractApprovalTransformer;
use App\Http\Transformers\ContractTransformer;
use App\Models\ApprovalForm\Business;
use App\Models\DataDictionarie;
use App\Models\RoleUser;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;


class ApprovalContractController extends Controller
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

        $data = DB::table('approval_form_business as bu')
            ->join('contracts as cs', function ($join) {
                $join->on('bu.form_instance_number', '=', 'cs.form_instance_number');
            })
            ->join('users', function ($join) {
                $join->on('cs.creator_id', '=', 'users.id');
            })
            ->join("data_dictionaries as dds",function ($join){
                $join->on("dds.id",'=','bu.form_status');
            })
//            ->leftjoin('approval_form_instance_values as afis', function ($join) {
//                $join->on('afis.form_instance_number', '=', 'cs.form_instance_number')->whereIn('form_control_id',[6,25,43]);
//            })

            ->where(function ($query) use ($payload, $request) {
                if ($request->has('keywords')) {
                    $query->where('bu.form_instance_number', 'LIKE', '%' . $payload['keywords'].'%')->orwhere('users.name', 'LIKE', '%' . $payload['keywords'] . '%');
                }
            })
            ->where('cs.creator_id', $user->id)
            ->whereIn('bu.form_status', $payload['status'])
            ->orderBy('cs.created_at', 'desc')
            ->select('cs.*', 'bu.*', 'users.name','users.icon_url', 'cs.id', 'cs.title','dds.icon','dds.name as approval_status_name')
            ->paginate($pageSize);

        return $this->response->paginator($data, new ContractApprovalTransformer());

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
                ->join('contracts as ph', function ($join) {
                    $join->on('afe.form_instance_number', '=', 'ph.form_instance_number');
                })
                ->join('users as us', function ($join) {
                    $join->on('ph.creator_id', '=', 'us.id');
                })
                ->join("data_dictionaries as dds",function ($join){
                    $join->on("dds.id",'=','afe.flow_type_id');
                })
//            ->join('approval_form_instance_values as afis', function ($join) {
//                $join->on('afis.form_instance_number', '=', 'ph.form_instance_number')->whereIn('form_control_id',[6,25,43]);
//            })
                ->where(function ($query) use ($payload, $request) {
                    if ($request->has('keywords')) {
                        $query->where('ph.form_instance_number', 'LIKE', '%' . $payload['keywords'].'%')->orwhere('us.name', 'LIKE', '%' . $payload['keywords'] . '%');
                    }
                })

                ->whereIn('afe.flow_type_id', $payload['status'])->where('afe.current_handler_type', 247)->where('u.id', $userId)
                //->where('afe.form_instance_number',$payload['keyword'])->orwhere('us.name', 'LIKE', '%' . $payload['keyword'] . '%')->orwhere('afis.form_control_value', 'LIKE', '%' . $payload['keyword'] . '%')
                ->orderBy('ph.created_at', 'desc')
                ->select('ph.id', 'afe.form_instance_number', 'afe.current_handler_type', 'afe.current_handler_type', 'afe.flow_type_id as form_status', 'ph.title', 'us.name','us.icon_url', 'ph.created_at','dds.icon','dds.name as approval_status_name')->get()->toArray();

            //查询个人
            $dataUser = DB::table('approval_flow_execute as afe')//

            ->join('users as u', function ($join) {
                $join->on('afe.current_handler_id', '=', 'u.id');
            })
                ->join('contracts as ph', function ($join) {
                    $join->on('afe.form_instance_number', '=', 'ph.form_instance_number');
                })
                ->join('users as us', function ($join) {
                    $join->on('ph.creator_id', '=', 'us.id');
                })
                ->join("data_dictionaries as dds",function ($join){
                    $join->on("dds.id",'=','afe.flow_type_id');
                })
//            ->join('approval_form_instance_values as afis', function ($join) {
//                $join->on('afis.form_instance_number', '=', 'ph.form_instance_number')->whereIn('form_control_id',[6,25,43]);
//            })
                // ->where('us.name', 'LIKE', '%' . $payload['keyword'] . '%')->orwhere('afis.form_control_value', 'LIKE', '%' . $payload['keyword'] . '%')->orwhere('afe.form_instance_number',$payload['keyword'])
                ->where(function ($query) use ($payload, $request) {
                    if ($request->has('keywords')) {
                        $query->where('ph.form_instance_number', 'LIKE', '%' . $payload['keywords'].'%')->orwhere('us.name', 'LIKE', '%' . $payload['keywords'] . '%');
                    }
                })
                ->whereIn('afe.flow_type_id', $payload['status'])->where('afe.current_handler_type', 245)->where('u.id', $userId)
                ->orderBy('ph.created_at', 'desc')
                ->select('afe.form_instance_number', 'afe.flow_type_id as form_status', 'ph.title', 'us.name', 'us.icon_url','ph.created_at', 'ph.id','dds.icon','dds.name as approval_status_name')->get()->toArray();


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
                ->join('contracts as ph', function ($join) {
                    $join->on('ph.form_instance_number', '=', 'bu.form_instance_number');
                })
                ->join("data_dictionaries as dds",function ($join){
                    $join->on("dds.id",'=','afe.flow_type_id');
                })
//            ->join('approval_form_instance_values as afis', function ($join) {
//                $join->on('afis.form_instance_number', '=', 'ph.form_instance_number')->whereIn('form_control_id',[6,25,43]);
//            })
                ->where(function ($query) use ($payload, $request) {
                    if ($request->has('keywords')) {
                        $query->where('ph.form_instance_number', 'LIKE', '%' . $payload['keywords'].'%')->orwhere('creator.name', 'LIKE', '%' . $payload['keywords'] . '%');
                    }
                })
                ->where('dp.user_id', $userId)
                ->whereIn('afe.flow_type_id', $payload['status'])
                //->where('afe.form_instance_number',$payload['keyword'])->orwhere('creator.name', 'LIKE', '%' . $payload['keyword'] . '%')->orwhere('ph.title', 'LIKE', '%' . $payload['keyword'] . '%')
                ->orderBy('ph.created_at', 'desc')
                ->select('afe.form_instance_number', 'afe.flow_type_id as form_status', 'ph.title', 'creator.name','creator.icon_url', 'ph.created_at', 'ph.id','dds.icon','dds.name as approval_status_name')->get()->toArray();



            //查询二级主管
            $dataPrincipalLevel = DB::table('approval_flow_execute as afe')//
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

                    DB::raw("select dpl.`user_id` from department_user as dur 
                        left join  departments as ds ON dur.`department_id`=ds.`id`
                        left join  department_principal as dpl ON dpl.`department_id`=ds.`department_pid`
                        where dur.`user_id`=afi.`apply_id`");
                })

                ->join('users as us', function ($join) {
                    $join->on('recode.change_id', '=', 'us.id');
                })
                ->join('contracts as ph', function ($join) {
                    $join->on('ph.form_instance_number', '=', 'bu.form_instance_number');
                })
                ->join("data_dictionaries as dds",function ($join){
                    $join->on("dds.id",'=','afe.flow_type_id');
                })
                ->where(function ($query) use ($payload, $request) {
                    if ($request->has('keywords')) {
                        $query->where('ph.form_instance_number', 'LIKE', '%' . $payload['keywords'].'%')->orwhere('creator.name', 'LIKE', '%' . $payload['keywords'] . '%');
                    }
                })
                ->where('dp.user_id', $userId)->where('afe.principal_level',2)
                ->whereIn('afe.flow_type_id', $payload['status'])
                ->orderBy('ph.created_at', 'desc')
                ->select('afe.form_instance_number', 'afe.flow_type_id as form_status', 'ph.title', 'creator.name','creator.icon_url', 'ph.created_at', 'ph.id','dds.icon','dds.name as approval_status_name')->get()->toArray();

            $resArrs = array_merge($dataPrincipal, $dataUser, $dataRole,$dataPrincipalLevel);

            $resArrInfo = json_decode(json_encode($resArrs), true);



            $resArr = array_merge($dataPrincipal, $dataUser, $dataRole);

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


        foreach ($arr['data'] as $key => &$value) {
            $value->id = hashid_encode($value->id);
        }
        return $arr;
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
            ->join('contracts as ph', function ($join) {
                $join->on('afc.form_instance_number', '=', 'ph.form_instance_number');
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
//            ->leftjoin('approval_form_instance_values as afis', function ($join) {
//                $join->on('afis.form_instance_number', '=', 'ph.form_instance_number')->whereIn('form_control_id',[6,25,43]);
//            })
            ->where(function ($query) use ($payload, $request) {
                if ($request->has('keywords')) {
                    $query->where('ph.form_instance_number', 'LIKE', '%' . $payload['keywords'].'%')->orwhere('us.name', 'LIKE', '%' . $payload['keywords'] . '%');
                }
            })
            ->where('afc.change_state', '!=', 237)->where('afc.change_state', '!=', 238)->where('afc.change_id', $userId)
            ->orderBy('afc.change_at', 'desc')
            ->groupBy('afb.form_instance_number')
            ->select('afb.form_instance_number', 'afb.form_status', 'ph.title', 'us.name','us.icon_url', 'ph.created_at', 'ph.id', 'afc.change_at','dds.icon','dds.name as approval_status_name')->get()->toArray();

        //查询角色
        //根据user_id 查询角色id

        $dataUserInfo = DB::table('approval_flow_change as afc')
            ->join('role_users', function ($join) {
                $join->on('role_users.role_id', '=','afc.role_id');
            })

            ->join('contracts as ph', function ($join) {
                $join->on('afc.form_instance_number', '=', 'ph.form_instance_number');
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
                    $query->where('afi.form_instance_number', 'LIKE','%'.$payload['keywords'].'%')->orwhere('us.name','LIKE','%'.$payload['keywords'] . '%')->orwhere('afg.name','LIKE','%'.$payload['keywords'].'%');
                }
                if ($request->has('group_name')) {
                    $query->where('afg.name',$payload['group_name']);
                }
            })
            ->where('afc.change_state', '!=', 237)->where('afc.change_state', '!=', 238)->where('role_users.user_id',$userId)
            ->orderBy('afc.change_at', 'desc')
            ->groupBy('afb.form_instance_number')
            ->select('afb.form_instance_number', 'afb.form_status', 'ph.title', 'us.name','us.icon_url', 'ph.created_at', 'ph.id', 'afc.change_at','dds.icon','dds.name as approval_status_name')->get()->toArray();

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
            ->join('contracts as cs', function ($join) {
                $join->on('cs.form_instance_number', '=', 'bu.form_instance_number');
            })
            ->where(function ($query) use ($payload, $request) {
                if ($request->has('keyword')) {
                    $query->where('afe.form_instance_number', $payload['keyword'])->orwhere('users.name', 'LIKE', '%' . $payload['keyword'] . '%');
                }
            })
            ->whereIn('afe.change_id', $user)
            ->whereNotIn('afe.change_state', [DataDictionarie::FIOW_TYPE_TJSP, DataDictionarie::FIOW_TYPE_DSP])
            ->select('afe.*', 'cs.title', 'bu.*', 'users.name', 'cs.created_at', 'cs.id')
            ->paginate($pageSize)->toArray();

        foreach ($data['data'] as $key => &$value) {
            $value->id = hashid_encode($value->id);
            $value->change_id = hashid_encode($value->change_id);
        }

        return $data;
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

            ->join('approval_form_business as afb', function ($join) {
                $join->on('afp.form_instance_number', '=', 'afb.form_instance_number');
            })
                ->join('contracts as cs', function ($join) {
                    $join->on('cs.form_instance_number', '=', 'afp.form_instance_number');
                })
                ->join('users as us', function ($join) {
                    $join->on('cs.creator_id', '=', 'us.id');
                })
                ->join("data_dictionaries as dds",function ($join){
                    $join->on("dds.id",'=','afb.form_status');
                })
                ->where(function ($query) use ($payload, $request) {
                    if ($request->has('keywords')) {
                        $query->where('cs.form_instance_number', 'LIKE', '%' . $payload['keywords'].'%')->orwhere('us.name', 'LIKE', '%' . $payload['keywords'] . '%');
                    }
                })
                ->whereIn('afb.form_status', $payload['status'])->where('afp.notice_type', 245)->where('afp.notice_id', $userId)
                ->orderBy('afp.created_at', 'desc')
                ->select('afb.form_instance_number', 'cs.title', 'us.name', 'us.icon_url', 'afp.created_at', 'afb.form_status', 'cs.id','dds.icon','dds.name as approval_status_name')->get()->toArray();


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
                ->join('contracts as ph', function ($join) {
                    $join->on('afe.form_instance_number', '=', 'ph.form_instance_number');
                })
                ->join('users as us', function ($join) {
                    $join->on('ph.creator_id', '=', 'us.id');
                })
                ->join("data_dictionaries as dds",function ($join){
                    $join->on("dds.id",'=','afb.form_status');
                })

                ->where(function ($query) use ($payload, $request) {
                    if ($request->has('keywords')) {
                        $query->where('ph.form_instance_number', 'LIKE', '%' . $payload['keywords'].'%')->orwhere('us.name', 'LIKE', '%' . $payload['keywords'] . '%');
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
                ->join('contracts as ph', function ($join) {
                    $join->on('ph.form_instance_number', '=', 'bu.form_instance_number');
                })
                ->join("data_dictionaries as dds",function ($join){
                    $join->on("dds.id",'=','bu.form_status');
                })
                ->where(function ($query) use ($payload, $request) {
                    if ($request->has('keywords')) {
                        $query->where('ph.form_instance_number', 'LIKE', '%' . $payload['keywords'].'%')->orwhere('creator.name', 'LIKE', '%' . $payload['keywords'] . '%');
                    }
                })
                ->where('dp.user_id', $userId)
                ->whereIn('bu.form_status', $payload['status'])
                ->orderBy('ph.created_at', 'desc')
                ->select('ph.id', 'afe.form_instance_number', 'afe.notice_type', 'bu.form_status', 'ph.title', 'creator.name','creator.icon_url', 'ph.created_at','dds.icon','dds.name as approval_status_name')->get()->toArray();


            $resArr = array_merge($dataPrincipal, $dataUser, $dataRole);


        } else {
            $resArr = $this->thenNotifyApproval($request,$payload);
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

        foreach ($arr['data'] as $key => &$value) {
            $value->id = hashid_encode($value->id);
        }
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
            ->join('contracts as ph', function ($join) {
                $join->on('afc.form_instance_number', '=', 'ph.form_instance_number');
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
                    $query->where('ph.form_instance_number', 'LIKE', '%' . $payload['keywords'].'%')->orwhere('us.name', 'LIKE', '%' . $payload['keywords'] . '%');
                }
            })
            ->where('afc.notice_type', '!=', 237)->where('afc.notice_type', '!=', 238)->where('afc.notice_id', $userId)
            ->where('afb.form_status', '!=', 231)
            ->orderBy('ph.created_at', 'desc')
            ->groupBy('afb.form_instance_number')
            ->select('ph.id', 'afb.form_instance_number', 'afb.form_status', 'ph.title', 'us.name', 'us.icon_url','ph.created_at','dds.icon','dds.name as approval_status_name')->get()->toArray();

        return $dataUser;
    }


    //项目合同
    public function project(Request $request)
    {

        $payload = $request->all();
        $pageSize = $request->get('page_size', config('app.page_size'));
        $payload['page'] = isset($payload['page']) ? $payload['page'] : 1;
        $payload['keyword'] = isset($payload['keyword']) ? $payload['keyword'] : '';
        $payload['number'] = isset($payload['number']) ? $payload['number'] : '';
        $payload['type'] = isset($payload['type']) ? $payload['type'] : '';

//        $data = DB::table('approval_form_business as afb')//
        $data = (new Business())->setTable("afb")->from("approval_form_business as afb")
            ->join('approval_forms as af', function ($join) {
                $join->on('af.form_id', '=', 'afb.form_id');
            })
            ->join('contracts as cs', function ($join) {
                $join->on('afb.form_instance_number', '=', 'cs.form_instance_number');
            })
            ->join('projects as ps', function ($join) {
                $join->on('ps.id', '=', 'cs.project_id');
            })
            ->join('users as us', function ($join) {
                $join->on('us.id', '=', 'ps.creator_id');
            })->contractSearchData()
            ->whereIn('afb.form_id', [9, 10])
            ->where('cs.title', 'LIKE', '%' . $payload['keyword'] . '%');
        if ($payload['number'])
            $data->Where('afb.form_instance_number', $payload['number']);

        if ($payload['type'])
            $data->Where('afb.form_id', $payload['type']);

        $res = $data->orderBy('cs.created_at', 'desc')
            ->select('cs.contract_number', 'afb.form_instance_number', 'cs.title', 'af.name as form_name', 'us.name', 'cs.created_at', 'afb.form_status')->get()->toArray();

        $start = ($payload['page'] - 1) * $pageSize;//偏移量，当前页-1乘以每页显示条数
        $article = array_slice($res, $start, $pageSize);


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

    //经济合同
    public function economic(Request $request)
    {

        $payload = $request->all();
        $pageSize = $request->get('page_size', config('app.page_size'));
        $payload['page'] = isset($payload['page']) ? $payload['page'] : 1;
        $payload['keyword'] = isset($payload['keyword']) ? $payload['keyword'] : '';
        $payload['number'] = isset($payload['number']) ? $payload['number'] : '';
        $payload['type'] = isset($payload['type']) ? $payload['type'] : '';
        $payload['talent'] = isset($payload['talent']) ? $payload['talent'] : '';

//        $data = DB::table('approval_form_business as afb')
        $data = (new Business())->setTable("afb")->from("approval_form_business as afb")
            ->join('approval_forms as af', function ($join) {
                $join->on('af.form_id', '=', 'afb.form_id');
            })
            ->join('contracts as cs', function ($join) {
                $join->on('afb.form_instance_number', '=', 'cs.form_instance_number');
            })->contractSearchData();
        if ($payload['talent'] == 'bloggers') {

            $data->join('bloggers as bs', function ($join) {
                $join->on('cs.stars', '=', 'bs.id')->where('cs.star_type', '=', 'bloggers');
            });
        } elseif ($payload['talent'] == 'stars') {


            $data->join('stars as s', function ($join) {
                $join->on('cs.stars', '=', 's.id')->where('cs.star_type', '=', 'stars');
            });
        } else {

            $data->leftjoin('stars as s', function ($join) {
                $join->on('cs.stars', '=', 's.id');
            });
            $data->leftjoin('bloggers as bs', function ($join) {
                $join->on('cs.stars', '=', 'bs.id');
            });
        }
        $data->join('users as us', function ($join) {
            $join->on('us.id', '=', 'cs.creator_id');
        })->join('data_dictionaries as dd','dd.id','afb.form_status')
            ->whereIn('afb.form_id', [5, 6, 7, 8])
            ->where('cs.title', 'LIKE', '%' . $payload['keyword'] . '%');
        if ($payload['number'])
            $data->Where('afb.form_instance_number', $payload['number']);

        if ($payload['type'])
            $data->Where('afb.form_id', $payload['type']);

        $res = $data->orderBy('cs.created_at', 'desc')
            ->select('cs.contract_number', 'afb.form_instance_number', 'cs.title', 'af.name as form_name', 'us.name','us.icon_url', 'cs.created_at', 'afb.form_status','dd.icon','dd.name', 'cs.star_type', 'cs.stars')->get()->toArray();

        $start = ($payload['page'] - 1) * $pageSize;//偏移量，当前页-1乘以每页显示条数
        $article = array_slice($res, $start, $pageSize);

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

    //项目详情合同列表
    public function projectList(Request $request, $model = null)
    {

        $payload = $request->all();
        $pageSize = $request->get('page_size', config('app.page_size'));
        $payload['page'] = isset($payload['page']) ? $payload['page'] : 1;


        if ($model) {
            $projects = $model->id;
        } else {
            $projects = hashid_decode($payload['project_id']);
        }

        $data = DB::table('approval_form_business as afb')//
        ->join('approval_forms as af', function ($join) {
            $join->on('af.form_id', '=', 'afb.form_id');
        })
            ->join('contracts as cs', function ($join) {
                $join->on('afb.form_instance_number', '=', 'cs.form_instance_number');
            })
            ->join('projects as ps', function ($join) {
                $join->on('ps.id', '=', 'cs.project_id');
            })
            ->where('cs.project_id', $projects)
            ->where('afb.form_status', 232)
            ->orderBy('cs.created_at', 'desc')
            ->select('afb.form_instance_number', 'cs.contract_number', 'cs.title', 'af.name as form_name', 'cs.creator_name', DB::raw("DATE_FORMAT(cs.created_at,'%Y-%m-%d %h:%i') as created_at"), 'afb.form_status', 'cs.stars', 'cs.star_type', 'cs.contract_money', 'cs.type')->get()->toArray();


        $dataInfo = json_decode(json_encode($data), true);
        $sum = 0;
        if (!empty($dataInfo)) {
            foreach ($dataInfo as &$value) {

                if ($value['star_type'] == 'stars') {
                    $starsId = explode(',', $value['stars']);
                    $value['stars_name'] = DB::table('stars')->whereIn('stars.id', $starsId)->select('stars.name')->get()->toArray();
                    $sum += $value['contract_money'];
                } else if ($value['star_type'] == 'bloggers') {
                    $starsId = explode(',', $value['stars']);
                    $value['stars_name'] = DB::table('bloggers')->whereIn('bloggers.id', $starsId)->select('bloggers.nickname')->get()->toArray();
                    $sum += $value['contract_money'];
                }
            }
        }

        $start = ($payload['page'] - 1) * $pageSize;//偏移量，当前页-1乘以每页显示条数
        $article = array_slice($dataInfo, $start, $pageSize);

        $total = count($article);//总条数
        $totalPages = ceil($total / $pageSize);

        $arr = array();
        $arr['data'] = $article;
        $arr['money'] = $sum;
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
