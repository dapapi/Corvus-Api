<?php

namespace App\Http\Controllers;

use App\Http\Requests\ReviewQuestionnaireStoreRequest;
use App\Http\Requests\ReviewUpdateRequest;
use App\Http\Transformers\ReviewQuestionnaireTransformer;
use App\Http\Transformers\ReviewQuestionnaireShowTransformer;
use App\Models\DepartmentUser;
use App\Models\Production;
use App\Models\ModuleUser;
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
        $pageSize = $request->get('page_size', config('app.page_size'));
        $reviews = ReviewQuestionnaire::where('id',$reviewquestionnaire->id)->createDesc()->paginate($pageSize);
        return $this->response->paginator($reviews, new ReviewQuestionnaireShowTransformer());
    }

    public function store(ReviewQuestionnaireStoreRequest $request, Production $production) {
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
        }
        } catch (Exception $e) {
            DB::rollBack();
            Log::error($e);
            return $this->response->errorInternal('创建失败');
        }
        DB::commit();
        return $this->response->created();

    }

    public function delete(Review $review) {
        try {
            $review->delete();
        } catch (Exception $exception) {
            $bag = new MessageBag();
            $bag->add('system', '删除失败');
            return redirect()->back()->withInput()->with('errors', $bag);
        }
        Session::put([
            'type' => MessageStatus::SUCCESS,
            'message' => '删除成功',
        ]);
        return redirect()->back();
    }

    public function edit(Review $review) {
        return view('reviews.update')->with('review', $review);
    }

    public function update(ReviewUpdateRequest $request, Review $review) {
        $payload = $request->all();

        try {
            $review->update($payload);
        } catch (Exception $exception) {
            $bag = new MessageBag();
            $bag->add('system', '修改失败');
            return redirect()->back()->withInput()->with('errors', $bag);
        }

        Session::put([
            'type' => MessageStatus::SUCCESS,
            'message' => '修改成功',
        ]);
        return redirect('/' . $review->reviewable_type . 's/' . hashid_encode($review->reviewable_id));
    }
}
