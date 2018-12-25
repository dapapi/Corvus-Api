<?php

namespace App\Http\Controllers;

use App\Models\ProjectHistorie;
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
use App\Http\Transformers\ProjectHistoriesTransformer;


use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;

use Dingo\Api\Facade\Route;
use Exception;
use Illuminate\Support\Facades\URL;
use League\Fractal;
use League\Fractal\Manager;
use League\Fractal\Serializer\DataArraySerializer;

class ApprovalFormController extends Controller
{
    public function index(Request $request)
    {

    }

    public function all(Request $request)
    {

    }

    public function store($formId, $notice='',$projectNumber)
    {

        $user = Auth::guard('api')->user();
        $userId = $user->id;
        if($projectNumber){
            DB::beginTransaction();
            try {
                $array = [
                  'form_id'=>$formId,
                  'form_instance_number'=>$projectNumber,
                  'form_status'=>DataDictionarie::FORM_STATE_DSP,
                  'business_type'=>project::PROJECT_TYPE
                ];

                Business::create($array);

                $executeInfo = ChainFixed::where('form_id', $formId)->get()->toArray();

                $executeArray = [
                    'form_instance_number'=>$projectNumber,
                    'current_handler_id'=>$executeInfo[0]['next_id'],
                    'flow_type_id'=>DataDictionarie::FORM_STATE_DSP
                ];

                Execute::create($executeArray);
                $changeArray = [
                    'form_instance_number'=>$projectNumber,
                    'change_id'=>$userId,
                    'change_at'=>date("Y-m-d H:i:s",time()),
                    'change_state'=>DataDictionarie::FIOW_TYPE_TJSP
                ];

                if(!empty($notice)){
                    foreach ($notice as $value){
                        $participantsArray = [
                            'form_instance_number'=>$projectNumber,
                            'notice_id'=>hashid_decode($value),
                            'notice_type'=>DataDictionarie::NOTICE_TYPE_TEAN,
                            'created_at'=>date("Y-m-d H:i:s",time()),
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
        }else{
            return $this->response->errorInternal('数据提交错误');
        }
    }


    public function myApply(Request $request)
    {
        $payload = $request->all();
        $user = Auth::guard('api')->user();

        $pageSize = $request->get('page_size', config('app.page_size'));

        $payload['status'] = isset($payload['status']) ? $payload['status'] : 1;
        if($payload['status'] == 1){
            $payload['status'] = array('231');
        }else{
            $payload['status'] = array('232','233','234','235');
        }

        $data = DB::table('approval_form_business as bu')
             ->join('projects as hi',function($join){
                 $join->on('bu.form_instance_number','=','hi.project_number');
             })
            ->join('users',function($join){
                $join->on('hi.creator_id','=','users.id');
            })
            ->where(function($query) use($payload,$request) {
                if ($request->has('keyword')) {
                    $query->where('bu.form_instance_number', $payload['keyword'])->orwhere('users.name', 'LIKE', '%' . $payload['keyword'] . '%');
                }
            })

            ->where('hi.creator_id', $user->id)
            ->whereIn('bu.form_status', $payload['status'])
            ->select('hi.*','bu.*','users.name','hi.id')
            ->paginate($pageSize)->toArray();

        //return $this->response->item($data, new ProjectTransformer());

        foreach ($data['data'] as $key=>&$value){
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

        $resource = new Fractal\Resource\Collection($data, new TemplateFieldTransformer($project->id));

        $manager = new Manager();
        $manager->setSerializer(new DataArraySerializer());

        $project = DB::table('projects')
            ->join('approval_form_business as bu',function($join){
                $join->on('projects.project_number','=','bu.form_instance_number');
            })
            ->join('users',function($join){
                $join->on('projects.creator_id','=','users.id');
            })
            ->join('department_user',function($join){
                $join->on('department_user.user_id','=','users.id');
            })
            ->join('departments',function($join){
                $join->on('departments.id','=','department_user.department_id');
            })->select('users.name','departments.name as department_name','projects.project_number','bu.form_status','projects.created_at')
              ->where('projects.project_number' , $project->project_number)->get();

        $result->addMeta('fields', $manager->createData($resource)->toArray());
        $result->addMeta('approval', $project);

        return $result;
    }

    public function myApproval(Request $request)
    {

        $payload = $request->all();
        $user = Auth::guard('api')->user();

        $pageSize = $request->get('page_size', config('app.page_size'));
        $data = DB::table('approval_flow_execute as afe')//

            ->join('approval_form_business as bu',function($join){
                $join->on('afe.form_instance_number','=','bu.form_instance_number');
            })
            ->join('users',function($join){
                $join->on('afe.current_handler_id','=','users.id');
            })

            ->join('projects as ph',function($join){
                $join->on('ph.project_number','=','bu.form_instance_number');
            })

            ->where(function($query) use($payload,$request) {
                if ($request->has('keyword')) {
                    $query->where('afe.form_instance_number', $payload['keyword'])->orwhere('users.name', 'LIKE', '%' . $payload['keyword'] . '%');
                }
            })
            ->where('afe.current_handler_id', $user->id)
            ->where('afe.flow_type_id', DataDictionarie::FORM_STATE_DSP)
            ->select('afe.*','bu.*','users.name','ph.title','ph.created_at','ph.id')
            ->paginate($pageSize)->toArray();

        foreach ($data['data'] as $key=>&$value){
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

        ->join('approval_form_business as bu',function($join){
            $join->on('afe.form_instance_number','=','bu.form_instance_number');
        })
            ->join('users',function($join){
                $join->on('afe.change_id','=','users.id');
            })

            ->join('projects as ph',function($join){
                $join->on('ph.project_number','=','bu.form_instance_number');
            })

            ->where(function($query) use($payload,$request) {
                if ($request->has('keyword')) {
                    $query->where('afe.form_instance_number', $payload['keyword'])->orwhere('users.name', 'LIKE', '%' . $payload['keyword'] . '%');
                }
            })
            ->where('afe.change_id', $user->id)
            ->whereNotIn( 'afe.change_state', [DataDictionarie::FIOW_TYPE_TJSP,DataDictionarie::FIOW_TYPE_DSP])
            ->select('afe.*','ph.title','bu.*','users.name','ph.created_at','ph.id')
            ->paginate($pageSize)->toArray();

        foreach ($data['data'] as $key=>&$value){
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

        if($payload['status'] == 1){
            $payload['status'] = array('231');
        }else{
            $payload['status'] = array('232','233','234','235');
        }

        $pageSize = $request->get('page_size', config('app.page_size'));
        $data = DB::table('approval_form_participants as afp')//

        ->join('approval_form_business as bu',function($join){
            $join->on('afp.form_instance_number','=','bu.form_instance_number');
        })
            ->join('users',function($join){
                $join->on('afp.notice_id','=','users.id');
            })

            ->join('projects as ph',function($join){
                $join->on('ph.project_number','=','afp.form_instance_number');
            })

            ->where(function($query) use($payload,$request) {
                if ($request->has('keyword')) {
                    $query->where('afp.form_instance_number', $payload['keyword'])->orwhere('users.name', 'LIKE', '%' . $payload['keyword'] . '%');
                }
            })
            ->where('afp.notice_id', $user->id)
            ->whereIn( 'bu.form_status', $payload['status'])
            ->select('ph.id','afp.*','bu.*','users.name','ph.created_at')
            ->paginate($pageSize)->toArray();

        foreach ($data['data'] as $key=>&$value){
            $value->id = hashid_encode($value->id);
            $value->notice_id = hashid_encode($value->notice_id);
        }
        return $data;
    }

}
