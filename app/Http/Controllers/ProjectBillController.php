<?php

namespace App\Http\Controllers;

use App\Http\Requests\ReviewQuestionStoreRequest;
use App\Http\Requests\ReviewQuestionStoreAnswerRequest;
use App\Http\Transformers\ProjectBillTransformer;
use App\Models\Star;
use App\Models\Blogger;
use App\Models\Project;
use App\Models\ProjectBill;
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
        }
        if ($Blogger && $Blogger->id) {
          //  $array['artist_name'] ='美豆爱厨房';
            $array['artist_name'] = $Blogger->nickname;
        } else if ($project && $project->id) {
         //   $array['project_kd_name'] ='美豆爱厨房';
            $array['project_kd_name'] = $project->title;
        } else if ($star && $star->id) {
            //   $array['project_kd_name'] ='美豆爱厨房';
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
        $result->addMeta('expendituresum', $expendituresum->expendituresum);
        return $result;
    }




}
