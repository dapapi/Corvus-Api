<?php

namespace App\Http\Controllers;

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


use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;

class ApprovalFromController extends Controller
{
    public function index(Request $request)
    {

    }

    public function all(Request $request)
    {

    }

    public function store($notice='',$userId,$projectNumber)
    {
        if($projectNumber){
            DB::beginTransaction();
            try {
                $array = [
                  'form_id'=>1,
                  'form_instance_number'=>$projectNumber,
                  'form_status'=>DataDictionarie::FORM_STATE_DSP,
                  'business_type'=>project::PROJECT_TYPE
                ];

                Business::create($array);

                $executeInfo = ChainFixed::where('form_id',1)->get()->toArray();

                $executeArray = [
                    'form_instance_number'=>$projectNumber,
                    'current_handler_id'=>$userId,
                    'flow_type_id'=>DataDictionarie::FIOW_TYPE_DSP
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
                            'notice_id'=>$value['id'],
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


    public function myApply(Request $request,User $user)
    {
        $payload = $request->all();

        $pageSize = $request->get('page_size', config('app.page_size'));

        $query = DB::table('approval_form_business as bu')

             ->join('project_histories as hi',function($join){
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
            ->where('bu.form_status', DataDictionarie::FORM_STATE_DSP)
            ->select('hi.*','bu.*','users.name','users.id')
            ->paginate($pageSize);

        return $query;
    }

    public function detail(Request $request,User $user)
    {
        $payload = $request->all();
        $projects = DB::table('approval_form_business as bu')

            ->join('project_histories as hi',function($join){
                $join->on('bu.form_instance_number','=','hi.project_number');
            })
            ->join('users',function($join){
                $join->on('hi.creator_id','=','users.id');
            })
            ->join('department_user',function($join){
                $join->on('department_user.user_id','=','users.id');
            })
            ->join('departments',function($join){
                $join->on('departments.id','=','department_user.department_id');
            })

            ->where('hi.project_number', $payload['number'])
            ->select('*')->get();


        return $projects;
    }

    public function myApproval(Request $request,User $user)
    {

        $payload = $request->all();

        $pageSize = $request->get('page_size', config('app.page_size'));

        $query = DB::table('approval_flow_execute as afe')//

            ->join('approval_form_business as bu',function($join){
                $join->on('afe.form_instance_number','=','bu.form_instance_number');
            })
            ->join('users',function($join){
                $join->on('afe.current_handler_id','=','users.id');
            })

            ->join('project_histories as ph',function($join){
                $join->on('ph.project_number','=','bu.form_instance_number');
            })

            ->where(function($query) use($payload,$request) {
                if ($request->has('keyword')) {
                    $query->where('afe.form_instance_number', $payload['keyword'])->orwhere('users.name', 'LIKE', '%' . $payload['keyword'] . '%');
                }
            })
            ->where('afe.current_handler_id', $user->id)
            ->where('afe.flow_type_id', DataDictionarie::FORM_STATE_DSP)
            ->select('afe.*','bu.*','users.name','users.id','ph.created_at')
            ->paginate($pageSize);

        return $query;
    }



    public function myThenApproval(Request $request,User $user)
    {

        $payload = $request->all();

        $pageSize = $request->get('page_size', config('app.page_size'));

        $query = DB::table('approval_flow_change as afe')//

        ->join('approval_form_business as bu',function($join){
            $join->on('afe.form_instance_number','=','bu.form_instance_number');
        })
            ->join('users',function($join){
                $join->on('afe.change_id','=','users.id');
            })

            ->join('project_histories as ph',function($join){
                $join->on('ph.project_number','=','bu.form_instance_number');
            })

            ->where(function($query) use($payload,$request) {
                if ($request->has('keyword')) {
                    $query->where('afe.form_instance_number', $payload['keyword'])->orwhere('users.name', 'LIKE', '%' . $payload['keyword'] . '%');
                }
            })
            ->where('afe.change_id', $user->id)
            ->whereNotIn( 'afe.change_state', [DataDictionarie::FIOW_TYPE_TJSP,DataDictionarie::FIOW_TYPE_DSP])
            ->select('afe.*','bu.*','users.name','users.id','ph.created_at')
            ->paginate($pageSize);

        return $query;
    }

}
