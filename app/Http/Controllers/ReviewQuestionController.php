<?php

namespace App\Http\Controllers;

use App\Http\Requests\ReviewQuestionStoreRequest;
use App\Http\Requests\ReviewQuestionStoreAnswerRequest;
use App\Http\Transformers\ReviewQuestionTransformer;
use App\Models\ReviewQuestion;
use App\Models\ReviewQuestionItem;
use App\Models\ReviewQuestionnaire;
use App\Models\ReviewUser;
use App\Models\ReviewAnswer;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Exception;

class ReviewQuestionController extends Controller
{
    public function index(Request $request,ReviewQuestionnaire $reviewquestionnaire)
    {
        $payload = $request->all();
        $user = Auth::guard('api')->user();
        $array['user_id'] = $user->id;
        $array['reviewquestionnaire_id'] = $reviewquestionnaire->id;
        $selectuser = ReviewUser::where($array)->get()->toarray();
        if(empty($selectuser)){
            return $this->response->errorBadRequest('用户不能查看本问卷');
        }
        $due = $reviewquestionnaire->deadline;
        if($due < now()->toDateTimeString()){
            return $this->response->errorBadRequest('问卷已过期');
        }
        $pageSize = $request->get('page_size', config('app.page_size'));
        $questions = ReviewQuestion::where('review_id',$reviewquestionnaire->id)->createDesc()->paginate($pageSize);
       // $questions = $reviewquestionnaire->questions()->get()->paginate($pageSize);
        return $this->response->paginator($questions, new ReviewQuestionTransformer());
    }



    public function up(Review $review, $sort)
    {

    }

    public function down(Review $review, $sort)
    {

    }

    /**
     * 异步方法暂时没有用到
     * @deprecated
     * @param Request $request
     * @param Review $review
     * @param ReviewQuestion $question
     * @return \Illuminate\Http\JsonResponse
     */
    public function sort(Request $request, Review $review, ReviewQuestion $question)
    {

    }

    public function update(Request $request, Review $review, ReviewQuestion $question)
    {

    }

    public function create(Review $review)
    {

    }

    public function store(ReviewQuestionStoreRequest $request, ReviewQuestionnaire $reviewquestionnaire)
    {

        $payload = $request->all();
        try {
            $payload['review_id'] = $reviewquestionnaire->id;
            $payload['sort'] = $reviewquestionnaire->questions()->count() + 1;
            $reviewQuestion = ReviewQuestion::create($payload);



        } catch (Exception $e) {
            DB::rollBack();
            Log::error($e);
            return $this->response->errorInternal('创建失败');
        }
        DB::commit();
        return $this->response->created();

    }
    //  保存问劵
    public function storeAnswer(ReviewQuestionStoreAnswerRequest $request, ReviewQuestionnaire $reviewquestionnaire)
    {

        $payload = $request->all();
        try {
            $payload['review_id'] = $reviewquestionnaire->id;
            $is_review_question_item_id = $request->has('review_question_item');
            $user = Auth::guard('api')->user();
            $payload['user_id'] = $user->id;
            if($is_review_question_item_id){
                foreach($payload['review_question_item'] as $key => $value){
                    $payload['review_question_id'] = hashid_decode($key);
                    $payload['review_question_item_id'] = hashid_decode($value);
                    $val = ReviewQuestionItem::where('id',hashid_decode($value))->get(['value'])->toArray()[0]['value'];
                    $payload['content'] = $val;
                    unset($payload['review_question_item']);
                    $reviewQuestion = ReviewAnswer::create($payload);
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
    public function appendItem(QuestionItemRequest $request, Review $review, ReviewQuestion $question) {

    }

    public function delete(Review $review, ReviewQuestion $question)
    {

    }
}
