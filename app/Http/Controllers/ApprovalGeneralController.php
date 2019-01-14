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
//
//            ->where(function ($query) use ($payload, $request) {
//                if ($request->has('keyword')) {
//                    $query->where('bu.form_instance_number', $payload['keyword'])->orwhere('users.name', 'LIKE', '%' . $payload['keyword'] . '%');
//                }
//            })
            ->where('afi.apply_id', $user->id)
            ->whereIn('afi.form_status', $payload['status'])
            ->orderBy('afi.created_at', 'desc')
            ->select('afi.*', 'users.name')
            ->paginate($pageSize)->toArray();

        foreach ($data['data'] as $key => &$value) {

            $value->creator_id = hashid_encode($value->apply_id);

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
                $join->on('ru.user_id', '=', 'u.id');
            })
            ->join('approval_form_instances as afi', function ($join) {
                $join->on('afe.form_instance_number', '=', 'afi.form_instance_number');
            })
            ->join('users as us', function ($join) {
                $join->on('afi.apply_id', '=', 'us.id');
            })
            ->whereIn('afe.flow_type_id', $payload['status'])->where('afe.current_handler_type', 247)->where('u.id', $userId)
            ->orderBy('afi.created_at', 'desc')
            ->select('afi.*','afe.form_instance_number','afe.current_handler_type','afe.current_handler_type','afe.flow_type_id as form_status','us.name')->get()->toArray();
        //->paginate($pageSize)->toArray();

        //查询个人
        $dataUser = DB::table('approval_flow_execute as afe')//


            ->join('users as u', function ($join) {
                $join->on('afe.current_handler_id', '=','u.id');
            })

            ->join('approval_form_instances as afi', function ($join) {
                $join->on('afe.form_instance_number', '=', 'afi.form_instance_number');
            })

            ->join('users as us', function ($join) {
                $join->on('afi.apply_id', '=', 'us.id');
            })

            ->whereIn('afe.flow_type_id',$payload['status'])->where('afe.current_handler_type',245)->where('u.id',$userId)
            ->orderBy('afi.created_at', 'desc')
            ->select('afi.*','afe.form_instance_number','afe.current_handler_type','afe.current_handler_type','afe.flow_type_id as form_status','us.name')->get()->toArray();


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
                ->where('dp.user_id', 18)
                ->whereIn('afe.flow_type_id', $payload['status'])
                ->orderBy('afi.created_at', 'desc')
                ->select('afe.form_instance_number', 'afe.flow_type_id as form_status', 'afi.*')->get()->toArray();

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

//        foreach ($arr['data'] as $key => &$value) {
//            $value->id = hashid_encode($value->id);
//        }
        return $arr;
    }

    //获取已审批信息
    public function thenApproval(){

        $user = Auth::guard('api')->user();
        $userId = $user->id;
        //查询个人
        $dataUser = DB::table('approval_flow_change as afc')//
            ->join('users as u', function ($join) {
                $join->on('afc.change_id', '=', 'u.id');
            })


            ->join('approval_form_instances as afi', function ($join) {
                $join->on('afc.form_instance_number', '=', 'afi.form_instance_number');
            })
            ->join('users as us', function ($join) {
                $join->on('us.id', '=', 'afi.apply_id');
            })
            //->where('afe.form_instance_number',$payload['keyword'])->orwhere('us.name', 'LIKE', '%' . $payload['keyword'] . '%')->orwhere('afis.form_control_value', 'LIKE', '%' . $payload['keyword'] . '%')

            ->where('afc.change_state', '!=', 237)->where('afc.change_state', '!=', 238)->where('afc.change_id', $userId)
            ->orderBy('afi.created_at', 'desc')
            ->select('afi.*', 'us.name')->get()->toArray();

        return $dataUser;
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

            ->join('users as us', function ($join) {
                $join->on('afi.apply_id', '=', 'us.id');
            })
            ->whereIn('afi.form_status', $payload['status'])->where('afp.notice_type', 245)->where('afp.notice_id', $userId)
            ->orderBy('afi.created_at', 'desc')
            ->select('afi.*','us.name', 'afp.created_at')->get()->toArray();


            //查询角色
            $dataRole = DB::table('approval_form_participants as afe')//
            ->join('role_users as ru', function ($join) {
                $join->on('afe.notice_id', '=', 'ru.role_id');
            })

            ->join('users as u', function ($join) {
                $join->on('ru.user_id', '=','u.id');
            })
            ->join('approval_form_instances as afi', function ($join) {
                $join->on('afe.form_instance_number', '=', 'afi.form_instance_number');
            })
            ->join('users as us', function ($join) {
                $join->on('afi.apply_id', '=','us.id');
            })
            ->whereIn('afi.form_status',$payload['status'])->where('afe.notice_type',247)->where('u.id',$userId)
            ->orderBy('afi.created_at', 'desc')
            ->select('afe.form_instance_number','afe.notice_type','afi.form_status','us.name')->get()->toArray();


            //部门负责人
            $dataPrincipal = DB::table('approval_form_participants as afe')//

            ->join('approval_form_instances as afi', function ($join) {
                $join->on('afe.form_instance_number', '=','afi.form_instance_number');
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


            ->where('dp.user_id',$userId)
            ->whereIn('afi.form_status',$payload['status'])
            ->orderBy('afi.created_at', 'desc')
            ->select('afi.form_instance_number','afe.notice_type','afi.form_status','creator.name')->get()->toArray();
            dd($dataPrincipal);

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

        ->join('approval_form_instances as afi', function ($join) {
            $join->on('afc.form_instance_number', '=','afi.form_instance_number');
        })

        ->join('users as us', function ($join) {
            $join->on('us.id', '=', 'afi.apply_id');
        })


        ->where('afc.notice_type','!=',237)->where('afc.notice_type','!=',238)->where('afc.notice_id',$userId)
        ->where('afi.form_status','!=', 231)
        ->orderBy('afi.created_at', 'desc')
        ->select('afi.form_instance_number','afi.form_status','us.name', 'afi.created_at')->get()->toArray();

        return $dataUser;
    }

}
