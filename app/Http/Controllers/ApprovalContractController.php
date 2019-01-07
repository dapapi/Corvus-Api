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
            ->leftjoin('approval_form_instance_values as afis', function ($join) {
                $join->on('afis.form_instance_number', '=', 'cs.form_instance_number')->whereIn('form_control_id',[6,25,43]);
            })

            ->where(function ($query) use ($payload, $request) {
                if ($request->has('keyword')) {
                    $query->where('bu.form_instance_number', $payload['keyword'])->orwhere('users.name', 'LIKE', '%' . $payload['keyword'] . '%');
                }
            })
            ->where('cs.creator_id', $user->id)
            ->whereIn('bu.form_status', $payload['status'])
            ->orderBy('cs.created_at', 'desc')
            ->select('cs.*', 'bu.*', 'users.name', 'cs.id','afis.form_control_value as title')
            ->paginate($pageSize)->toArray();

        foreach ($data['data'] as $key => &$value) {
            $value->id = hashid_encode($value->id);
            $value->creator_id = hashid_encode($value->creator_id);

        }
        return $data;

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
                $join->on('ru.user_id', '=','u.id');
            })
            ->join('contracts as ph', function ($join) {
                $join->on('afe.form_instance_number', '=', 'ph.form_instance_number');
            })
            ->join('users as us', function ($join) {
                $join->on('ph.creator_id', '=','us.id');
            })
            ->join('approval_form_instance_values as afis', function ($join) {
                $join->on('afis.form_instance_number', '=', 'ph.form_instance_number')->whereIn('form_control_id',[6,25,43]);
            })

            ->whereIn('afe.flow_type_id',$payload['status'])->where('afe.current_handler_type',247)->where('u.id',$userId)
            //->where('afe.form_instance_number',$payload['keyword'])->orwhere('us.name', 'LIKE', '%' . $payload['keyword'] . '%')->orwhere('afis.form_control_value', 'LIKE', '%' . $payload['keyword'] . '%')
            ->orderBy('ph.created_at', 'desc')
            ->select('ph.id','afe.form_instance_number','afe.current_handler_type','afe.current_handler_type','afe.flow_type_id as form_status','afis.form_control_value as title','us.name', 'ph.created_at')->get()->toArray();

        //查询个人
        $dataUser = DB::table('approval_flow_execute as afe')//

            ->join('users as u', function ($join) {
                $join->on('afe.current_handler_id', '=','u.id');
            })
            ->join('contracts as ph', function ($join) {
                $join->on('afe.form_instance_number', '=', 'ph.form_instance_number');
            })

            ->join('users as us', function ($join) {
                $join->on('ph.creator_id', '=', 'us.id');
            })

            ->join('approval_form_instance_values as afis', function ($join) {
                $join->on('afis.form_instance_number', '=', 'ph.form_instance_number')->whereIn('form_control_id',[6,25,43]);
            })
           // ->where('us.name', 'LIKE', '%' . $payload['keyword'] . '%')->orwhere('afis.form_control_value', 'LIKE', '%' . $payload['keyword'] . '%')->orwhere('afe.form_instance_number',$payload['keyword'])

            ->whereIn('afe.flow_type_id',$payload['status'])->where('afe.current_handler_type',245)->where('u.id',$userId)
            ->orderBy('ph.created_at', 'desc')
            ->select('afe.form_instance_number','afe.flow_type_id as form_status', 'afis.form_control_value as title', 'us.name', 'ph.created_at', 'ph.id')->get()->toArray();


        //部门负责人
        $dataPrincipal = DB::table('approval_flow_execute as afe')//
            ->join('approval_form_business as bu', function ($join) {
                $join->on('afe.form_instance_number', '=','bu.form_instance_number');
            })
            ->join('approval_flow_change as recode', function ($join) {
                $join->on('afe.form_instance_number', '=','recode.form_instance_number')->where('recode.change_state','=',237);
            })
            ->join('users as creator', function ($join) {
                $join->on('recode.change_id', '=','creator.id');
            })
            ->join('department_user as du', function ($join) {
                $join->on('creator.id', '=', 'du.user_id');
            })
            ->join('department_principal as dp', function ($join) {
                $join->on('dp.department_id', '=', 'du.department_id')->where('afe.current_handler_type','=',246);
            })
            ->join('contracts as ph', function ($join) {
                $join->on('ph.form_instance_number', '=','bu.form_instance_number');
            })

            ->join('approval_form_instance_values as afis', function ($join) {
                $join->on('afis.form_instance_number', '=', 'ph.form_instance_number')->whereIn('form_control_id',[6,25,43]);
            })

            ->where('dp.user_id',$userId)
            ->whereIn('afe.flow_type_id',$payload['status'])
            //->where('afe.form_instance_number',$payload['keyword'])->orwhere('creator.name', 'LIKE', '%' . $payload['keyword'] . '%')->orwhere('ph.title', 'LIKE', '%' . $payload['keyword'] . '%')
            ->orderBy('ph.created_at', 'desc')
            ->select('afe.form_instance_number','afe.flow_type_id as form_status','ph.title', 'creator.name', 'ph.created_at', 'ph.id')->get()->toArray();

            $resArr = array_merge($dataPrincipal,$dataUser,$dataRole);

        } else {
            $resArr = $this->thenApproval();
        }

        $count = count($resArr);//总条数
        $start = ($payload['page']-1)*$pageSize;//偏移量，当前页-1乘以每页显示条数
        $article = array_slice($resArr,$start,$pageSize);

        $arr = array();
        $arr['total'] = $count;
        $arr['data'] = $article;
        $arr['meta']['pagination'] = $count;
        $arr['meta']['current_page'] = $count;
        $arr['meta']['total_pages'] = ceil($count/20);

        foreach ($arr['data'] as $key => &$value) {
            $value->id = hashid_encode($value->id);
        }
        return $arr;
    }

    //获取已审批信息
    public function thenApproval(){

        $user = Auth::guard('api')->user();
        $userId = $user->id;
        //查询个人
        $dataUser = DB::table('approval_flow_change as afc')//
            ->join('users as u', function ($join) {
                $join->on('afc.change_id', '=','u.id');
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

            ->leftjoin('approval_form_instance_values as afis', function ($join) {
                $join->on('afis.form_instance_number', '=', 'ph.form_instance_number')->whereIn('form_control_id',[6,25,43]);
            })
            //->where('afe.form_instance_number',$payload['keyword'])->orwhere('us.name', 'LIKE', '%' . $payload['keyword'] . '%')->orwhere('afis.form_control_value', 'LIKE', '%' . $payload['keyword'] . '%')

            ->where('afc.change_state','!=',237)->where('afc.change_state','!=',238)->where('afc.change_id',$userId)
            ->orderBy('ph.created_at', 'desc')
            ->groupBy('afb.form_instance_number')
            ->select('afb.form_instance_number','afb.form_status', 'afis.form_control_value as title', 'us.name', 'ph.created_at', 'ph.id')->get()->toArray();

        return $dataUser;
    }

    public function myThenApproval(Request $request)
    {

        $payload = $request->all();
        $user = Auth::guard('api')->user();
        $userId = $user->id;
        $executeInfo = DB::table('approval_flow_execute')->get()->toArray();
        $user = array();
        foreach($executeInfo as $value){
            if($value->current_handler_type == 245){
                $user[] = (int)$value->current_handler_id;
            }else{
                $roleInfo = RoleUser::where('user_id',$userId)->where('role_id',$value->current_handler_id)->get()->toArray();
                foreach ($roleInfo as $rvalue){
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
            ->whereIn('afb.form_status', $payload['status'])->where('afp.notice_type', 245)->where('afp.notice_id', $userId)
            ->orderBy('afp.created_at', 'desc')
            ->select('afb.form_instance_number', 'cs.title', 'us.name', 'us.name', 'afp.created_at', 'afb.form_status','cs.id')->get()->toArray();


            //查询角色
            $dataRole = DB::table('approval_form_participants as afe')//
            ->join('role_users as ru', function ($join) {
                $join->on('afe.notice_id', '=', 'ru.role_id');
            })
            ->join('approval_form_business as afb', function ($join) {
                $join->on('afe.form_instance_number', '=', 'afb.form_instance_number');
            })
            ->join('users as u', function ($join) {
                $join->on('ru.user_id', '=','u.id');
            })
            ->join('contracts as ph', function ($join) {
                $join->on('afe.form_instance_number', '=', 'ph.form_instance_number');
            })
            ->join('users as us', function ($join) {
                $join->on('ph.creator_id', '=','us.id');
            })
            ->whereIn('afb.form_status',$payload['status'])->where('afe.notice_type',247)->where('u.id',$userId)
            ->orderBy('ph.created_at', 'desc')
            ->select('ph.id','afe.form_instance_number','afe.notice_type','afb.form_status','ph.title','us.name', 'ph.created_at')->get()->toArray();


            //部门负责人
            $dataPrincipal = DB::table('approval_form_participants as afe')//

            ->join('approval_form_business as bu', function ($join) {
                $join->on('afe.form_instance_number', '=','bu.form_instance_number');
            })
            ->join('approval_flow_change as recode', function ($join) {
                $join->on('afe.form_instance_number', '=','recode.form_instance_number')->where('recode.change_state','=',237);
            })
            ->join('users as creator', function ($join) {
                $join->on('recode.change_id', '=','creator.id');
            })
            ->join('department_user as du', function ($join) {
                $join->on('creator.id', '=', 'du.user_id');
            })
            ->join('department_principal as dp', function ($join) {
                $join->on('dp.department_id', '=', 'du.department_id')->where('afe.notice_type','=',246);
            })
            ->join('contracts as ph', function ($join) {
                $join->on('ph.form_instance_number', '=','bu.form_instance_number');
            })

            ->where('dp.user_id',$userId)
            ->whereIn('bu.form_status',$payload['status'])
            ->orderBy('ph.created_at', 'desc')
            ->select('ph.id','afe.form_instance_number','afe.notice_type','bu.form_status','ph.title','creator.name', 'ph.created_at')->get()->toArray();


        $resArr = array_merge($dataPrincipal,$dataUser,$dataRole);


        } else {
            $resArr = $this->thenNotifyApproval();
        }

            $count = count($resArr);//总条数
            $start = ($payload['page']-1)*$pageSize;//偏移量，当前页-1乘以每页显示条数
            $article = array_slice($resArr,$start,$pageSize);

            $arr = array();
            $arr['total'] = $count;
            $arr['data'] = $article;
            $arr['meta']['pagination'] = $count;
            $arr['meta']['current_page'] = $count;
            $arr['meta']['total_pages'] = ceil($count/20);

            foreach ($arr['data'] as $key => &$value) {
                $value->id = hashid_encode($value->id);
            }
            return $arr;
        }

    //获取已审批信息
    public function thenNotifyApproval(){

        $user = Auth::guard('api')->user();
        $userId = $user->id;
        //查询个人
        $dataUser = DB::table('approval_form_participants as afc')//
        ->join('users as u', function ($join) {
            $join->on('afc.notice_id', '=','u.id');
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

        ->where('afc.notice_type','!=',237)->where('afc.notice_type','!=',238)->where('afc.notice_id',$userId)
        ->orderBy('ph.created_at', 'desc')
        ->groupBy('afb.form_instance_number')
        ->select('ph.id','afb.form_instance_number','afb.form_status','ph.title','us.name', 'ph.created_at')->get()->toArray();

        return $dataUser;
    }

}
