<?php

namespace App\Http\Controllers;

use App\Http\Requests\ReviewQuestionStoreRequest;
use App\Http\Requests\ReviewQuestionStoreAnswerRequest;
use App\Http\Transformers\ProjectBillTransformer;
use App\Models\ReviewQuestion;
use App\Models\ReviewQuestionItem;
use App\Models\ReviewQuestionnaire;
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
    public function index(Request $request,Blogger $Blogger,Project $project)
    {
        $payload = $request->all();
        $pageSize = $request->get('page_size', config('app.page_size'));
        if($request->has('expense_type')){
        $array['expense_type'] = $payload['expense_type'];
        }

        if ($Blogger && $Blogger->id) {
            $array['artist_name'] ='美豆爱厨房';
          //  $array['artist_name'] = $Blogger->nickname;
        } else if ($project && $project->id) {
            $array['project_kd_name'] ='美豆爱厨房';
          //  $array['project_kd_name'] = $project->title;
        }
//        $sums =  ProjectBill::where($array)->select(DB::raw('sum(money) as sums'))->groupby('expense_type')->get();
        $projectbill = ProjectBill::where($array)->createDesc()->paginate($pageSize);
        return $this->response->paginator($projectbill, new ProjectBillTransformer());
    }




}
