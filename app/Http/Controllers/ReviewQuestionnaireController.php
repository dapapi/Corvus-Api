<?php

namespace App\Http\Controllers;

use App\Http\Requests\ReviewQuestionnaireStoreRequest;
use App\Http\Requests\ReviewUpdateRequest;
use App\Http\Transformers\ReviewQuestionnaireTransformer;
use App\Http\Transformers\ReviewQuestionnaireShowTransformer;
use App\Models\DepartmentPrincipal;
use App\Models\DepartmentUser;
use App\Models\Production;
use App\Models\ReviewQuestion;
use App\Models\ReviewQuestionItem;
use App\Models\ModuleUser;
use App\ReviewItemAnswer;
use App\Models\ReviewAnswer;
use App\Models\ReviewUser;
use App\Models\ReviewQuestionnaire;
use Illuminate\Support\Facades\Auth;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ReviewQuestionnaireController extends Controller {
    public function index(Request $request) {
        $payload = $request->all();
        $pageSize = $request->get('page_size', config('app.page_size'));
        $reviews = ReviewQuestionnaire::createDesc()->paginate($pageSize);
        return $this->response->paginator($reviews, new ReviewQuestionnaireTransformer());
    }

    public function show(Request $request,ReviewQuestionnaire $reviewquestionnaire) {
        $payload = $request->all();
        $user = Auth::guard('api')->user();
        $array['user_id'] = $user->id;
        $array['reviewquestionnaire_id'] = $reviewquestionnaire->id;
        $selectuser = ReviewUser::where($array)->get()->toarray();
        if(empty($selectuser)){
            $data = ['data'=>''];
            return $data;
        }
        $due = $reviewquestionnaire->deadline;
        if($due < now()->toDateTimeString()){
            $error = ['data'=>'问卷已过期'];
        }
        $pageSize = $request->get('page_size', config('app.page_size'));
        $reviews = ReviewQuestionnaire::where('id',$reviewquestionnaire->id)->createDesc()->paginate($pageSize);
        $result = $this->response->paginator($reviews, new ReviewQuestionnaireShowTransformer());

        if(isset($error)){

        $result->addMeta('error', $error);
        return $result;
        }else{
            return $result;
        }
    }


    public function store(ReviewQuestionnaireStoreRequest $request, Production $production,ReviewQuestionnaire $reviewquestionnaire,ReviewQuestion $reviewquestion) {
        $payload = $request->all();
        $user = Auth::guard('api')->user();
        $array['creator_id'] = $user->id;
        DB::beginTransaction();
        try {
        if (!empty($array['creator_id'])) {
            $department_id = DepartmentUser::where('user_id',$array['creator_id'])->first()->department_id;
            $users = DepartmentUser::where('department_id',$department_id)->get(['user_id'])->toArray();
            if(isset($users)){
                foreach($users as $key => $val){
                    $moduleuser = new ModuleUser;
                    $moduleuser->user_id = $val['user_id'];
                    $moduleuser->moduleable_id = $production->id;
                    $moduleuser->moduleable_type = 'reviewquestionnaire';
                    $moduleuser->type = 1;  //1  参与人
                    $modeluseradd = $moduleuser->save();
                }
            }

            $reviewquestionnairemodel = new ReviewQuestionnaire;
            $reviewquestionnairemodel->name = '制作人视频评分-视频评分';
            $reviewquestionnairemodel->creator_id = $array['creator_id'];
            //  $now = now()->toDateTimeString();

            $number = date("w",time());  //当时是周几
            $number = $number == 0 ? 7 : $number; //如遇周末,将0换成7
            $diff_day = $number - 6; //求到周一差几天
            if($diff_day >= 0 ){
                $diff_day = - (7 + $diff_day) ;
            }
            $deadline = date("Y-m-d 00:00:00",time() - ($diff_day * 60 * 60 * 24));
            $reviewquestionnairemodel->deadline = $deadline;
            $reviewquestionnairemodel->reviewable_id = $production->id;
            $reviewquestionnairemodel->reviewable_type = 'production';
            $reviewquestionnairemodel->auth_type = '2';
            $reviewquestionnaireadd = $reviewquestionnairemodel->save();

            if($reviewquestionnaireadd == true){
                foreach($users as $key => $val){
                    $reviewuser = new ReviewUser;
                    $reviewuser->user_id = $val['user_id'];
                    $reviewuser->reviewquestionnaire_id = $reviewquestionnairemodel->id;
                    $reviewuseradd = $reviewuser->save();
                }

            }

            $issues =ReviewItemAnswer::getIssue();
            $answer =ReviewItemAnswer::getAnswer();
            foreach ($issues as $key => $value){
                $issue['title'] = $value['titles'];
                $issue['review_id'] = $reviewquestionnairemodel->id;
                $issue['sort'] = $reviewquestionnaire->questions()->count() + 1;;
                $reviewQuestion = ReviewQuestion::create($issue);
                $reviewQuestionId = $reviewQuestion->id;
                foreach ($answer[$key] as $key1 => $value1) {
                    $arr = array();
                    $arr['title'] = $value1['answer'];
                    $arr['value'] = $value1['value'];
                    $arr['review_question_id'] = $reviewQuestionId;
                    $arr['sort'] = $reviewquestion->items()->count() + 1;
                    $reviewQuestion = ReviewQuestionItem::create($arr);
                }

            }


        }
        } catch (Exception $e) {
            DB::rollBack();
            Log::error($e);
            return $this->response->errorInternal('创建失败');
        }
        DB::commit();
        return $this->response->created();

    }

    public function storeExcellent(ReviewQuestionnaireStoreRequest $request, Production $production,ReviewQuestionnaire $reviewquestionnaire,ReviewQuestion $reviewquestion) {
        $payload = $request->all();
        $user = Auth::guard('api')->user();
        $array[] = ['user_id',$user->id];
   //     $array[] = ['type',1];
        $arr = empty(DepartmentPrincipal::where($array)->first());
        if($arr){
            return $this->response->errorInternal('创建失败');
        }
       // if($request->has('name','评优团视频评分任务')){
     //       $array['name'] = '评优团视频评分任务';
      //  }
        $array['creator_id'] = $user->id;
        DB::beginTransaction();
        try {
            if (!empty($array['creator_id'])) {
//
              $users =ReviewItemAnswer::getUsers();

                if(isset($users)){

                    foreach($users as $key => $val){
                        $moduleuser = new ModuleUser;
                        $moduleuser->user_id = $val['user_id'];
                        $moduleuser->moduleable_id = $reviewquestionnaire->id;
                        $moduleuser->moduleable_type = 'reviewquestionnaire';
                        $moduleuser->type = 1;  //1  参与人
                        $modeluseradd = $moduleuser->save();

                    }
                }

                $reviewquestionnairemodel = new ReviewQuestionnaire;

                $reviewquestionnairemodel->name = '评优团视频评分任务-视频评分';
                $reviewquestionnairemodel->creator_id = $array['creator_id'];
                //  $now = now()->toDateTimeString();
                $number = date("w",time());  //当时是周几
                $number = $number == 0 ? 7 : $number; //如遇周末,将0换成7
                $diff_day = $number - 8; //求到周一差几天
                $deadline = date("Y-m-d 00:00:00",time() - ($diff_day * 60 * 60 * 24));
                $reviewquestionnairemodel->deadline = $deadline;
                $reviewquestionnairemodel->excellent_sum = $payload['excellent_sum'];
                $reviewquestionnairemodel->reviewable_id = $reviewquestionnaire->id;
                $reviewquestionnairemodel->excellent = $payload['excellent'];
                $reviewquestionnairemodel->reviewable_type = 'production';
                $reviewquestionnairemodel->auth_type = '2';
                $reviewquestionnaireadd = $reviewquestionnairemodel->save();

                if($reviewquestionnaireadd == true){
                    foreach($users as $key => $val){
                        $reviewuser = new ReviewUser;
                        $reviewuser->user_id = $val['user_id'];
                        $reviewuser->reviewquestionnaire_id = $reviewquestionnairemodel->id;
                        $reviewuseradd = $reviewuser->save();
                    }

                }
                $issues =ReviewItemAnswer::getIssue();
                $answer =ReviewItemAnswer::getAnswer();
                foreach ($issues as $key => $value){
                    $issue['title'] = $value['titles'];
                    $issue['review_id'] = $reviewquestionnairemodel->id;
                    $issue['sort'] = $reviewquestionnaire->questions()->count() + 1;;
                    $reviewQuestion = ReviewQuestion::create($issue);
                    $reviewQuestionId = $reviewQuestion->id;
                    foreach ($answer[$key] as $key1 => $value1) {
                        $arr = array();
                        $arr['title'] = $value1['answer'];
                        $arr['value'] = $value1['value'];
                        $arr['review_question_id'] = $reviewQuestionId;
                        $arr['sort'] = $reviewquestion->items()->count() + 1;
                        $reviewQuestion = ReviewQuestionItem::create($arr);
                    }

                }
            }
        } catch (Exception $e) {
          //  dd($e);
            DB::rollBack();
            Log::error($e);
            return $this->response->errorInternal('创建失败');
        }
        DB::commit();
        return $this->response->item($reviewquestionnairemodel, new ReviewQuestionnaireShowTransformer());

    }

}
