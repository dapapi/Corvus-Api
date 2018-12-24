<?php

namespace App\Http\Controllers;


use App\Http\Transformers\ProjectBillTransformer;
use App\Models\Star;
use App\Models\Blogger;
use App\Models\Project;
use App\Models\ProjectBill;
use App\Models\ProjectBillsResource;
use App\Models\OperateEntity;
use App\OperateLogMethod;
use App\Events\OperateLogEvent;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Exception;

class ProjectBillController extends Controller
{
    public function index(Request $request,Blogger $Blogger,Star $star,Project $project)
    {
        $payload = $request->all();
        $pageSize = $request->get('page_size', config('app.page_size'));
        if($request->has('expense_type')){
            if($payload['expense_type']==1){
                $array['expense_type'] = '收入';
            }else if($payload['expense_type']==2){
                $array['expense_type'] = '支出';
            }else{
                $array['expense_type'] = '';
            }
        }else{
            $array['expense_type'] = '';
        }
        if ($Blogger && $Blogger->id) {
            $array['artist_name'] = $Blogger->nickname;
        } else if ($project && $project->id) {
            $array['project_kd_name'] = $project->title;
        } else if ($star && $star->id) {
            $array['project_kd_name'] = $star->name;
        }
        if($array['expense_type'] != '支出') {
            $array['expense_type'] = '支出';
            $expendituresum = ProjectBill::where($array)->select(DB::raw('sum(money) as expendituresum'))->groupby('expense_type')->first();

        }else{
            $expendituresum = ProjectBill::where($array)->select(DB::raw('sum(money) as expendituresum'))->groupby('expense_type')->first();
        }
        $projectbill = ProjectBill::where($array)->createDesc()->paginate($pageSize);

        $result = $this->response->paginator($projectbill, new ProjectBillTransformer());
        if(isset($expendituresum)){
            $result->addMeta('expendituresum', $expendituresum->expendituresum);
            return $result;
        }else{
            return $result;
        }

    }

    public function store(Request $request,Blogger $Blogger,Star $star,Project $project)
    {

        $payload = $request->all();
        $user = Auth::guard('api')->user();
        unset($payload['status']);
        $payload['creator_id'] = $user->id;
        $array = $payload;
        if ($Blogger && $Blogger->id) {
            $array['resourceable_id'] = $project->id;
            $array['resourceable_title'] = $Blogger->nickname;
            $array['resourceable_type'] = 'blogger';

        } else if ($project && $project->id) {
            $array['resourceable_id'] = $project->id;
            $array['resourceable_title'] = $project->title;
            $array['resourceable_type'] = 'project';

        } else if ($star && $star->id) {
            $array['resourceable_id'] = $project->id;
            $array['resourceable_title'] = $star->name;
            $array['resourceable_type'] = 'star';

        }
        $is_exist = ProjectBillsResource::where(['resourceable_id'=> $array['resourceable_id'],'resourceable_title'=> $array['resourceable_title'],'resourceable_type'=> $array['resourceable_type']])->first();

        if(isset($is_exist)){
            return $this->response->errorNotFound('已存在');
        }
            try {


                $bill =  ProjectBillsResource::create($array);
                // 操作日志
                $operate = new OperateEntity([
                    'obj' => $bill,
                    'title' => null,
                    'start' => null,
                    'end' => null,
                    'method' => OperateLogMethod::CREATE,
                ]);
                event(new OperateLogEvent([
                    $operate,
                ]));
            } catch (Exception $e) {
                DB::rollBack();
                Log::error($e);
                return $this->response->errorInternal('创建失败');
            }
        DB::commit();




    }


}
