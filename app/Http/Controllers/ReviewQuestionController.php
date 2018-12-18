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
            return $this->response->errorBadRequest('用户不能查看本问劵');
        }

        $pageSize = $request->get('page_size', config('app.page_size'));
        $questions = ReviewQuestion::where('review_id',$reviewquestionnaire->id)->createDesc()->paginate($pageSize);
       // $questions = $reviewquestionnaire->questions()->get()->paginate($pageSize);
        return $this->response->paginator($questions, new ReviewQuestionTransformer());
    }



    public function up(Review $review, $sort)
    {
        if ($sort > 1) {
            try {
                $reviewQuestion1 = $review->questions()->where('sort', $sort)->first();
                $reviewQuestion2 = $review->questions()->where('sort', $sort - 1)->first();
                $reviewQuestion1->sort = $sort - 1;
                $reviewQuestion2->sort = $sort;
                $reviewQuestion1->save();
                $reviewQuestion2->save();
            } catch (Exception $e) {
            }
        }
        return redirect()->back();
    }

    public function down(Review $review, $sort)
    {
        $max = ReviewQuestion::max('sort');
        if ($sort < $max) {
            try {
                $reviewQuestion1 = $review->questions()->where('sort', $sort)->first();
                $reviewQuestion2 = $review->questions()->where('sort', $sort + 1)->first();
                $reviewQuestion1->sort = $sort + 1;
                $reviewQuestion2->sort = $sort;
                $reviewQuestion1->save();
                $reviewQuestion2->save();
            } catch (Exception $e) {
            }
        }
        return redirect()->back();
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
        $payload = $request->all();
        if (isset($payload['sort']) && $payload['sort'] != '') {
            $array = json_decode($payload['sort'])->data;
            for ($i = 0; $i < count($array); $i++) {
                try {
                    $postImage = ReviewQuestion::findOrFail($array[$i]);
                    $postImage->sort = $i + 1;
                    $postImage->save();
                } catch (Exception $e) {
                    return response()->json(['success' => false, 'message' => '排序失败']);
                }
            }
        }
        return response()->json(['success' => true, 'message' => '排序成功']);
    }

    public function update(Request $request, Review $review, ReviewQuestion $question)
    {
        $payload = $request->all();
        if (isset($payload['name']) && $payload['name'] != '' && $payload['name'] != $question->title) {
            try {
                $question->title = $payload['name'];
                $question->save();
            } catch (Exception $e) {
                return response()->json(['success' => false, 'message' => '修改失败']);
            }
            return response()->json(['success' => true, 'message' => '修改成功']);
        }
        return response()->json(['success' => false, 'message' => '修改失败']);
    }

    public function create(Review $review)
    {
        return view('reviews.questions.add')->with('review', $review);
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
        // 传id
//        $questionId = hashid_decode($request->get('question'));
        // 路由带对象
//        $questionId = $question->id;
        $title = $request->get('item');
        $value = $request->get('value');

//        $question = ReviewQuestion::where('id', $questionId)->first();
//        dd($question);
        $amount = $question->items()->count();
        if ($question->type == ReviewQuestionType::TEXT || $question->type == ReviewQuestionType::RATING) {
            // 加返回信息
            return response()->json(['success' => false, 'message' => '添加失败']);
        } elseif ($question->type == ReviewQuestionType::RADIO || ReviewQuestionType::CHECKBOX) {
            try {
                $item = $question->items()->create(['title' => $title, 'sort' => $amount + 1, 'value' => $value]);
            } catch (Exception $exception) {
                return response()->json(['success' => false, 'message' => '添加失败']);
            }
            $data = [
                'success' => true,
                'message' => '添加成功',
                'data'    => [
                    'title' => $item->title,
                    'sort'  => $item->sort,
                    'value' => $item->value,
                ]
            ];
            return response()->json($data);
        } else {
            return response()->json(['success' => false, 'message' => '非法操作']);
        }
        
    }

    public function delete(Review $review, ReviewQuestion $question)
    {
        try {
            $questions = $review->questions()->where('sort', '>', $question->sort)->orderBy('sort', 'asc')->get();
            foreach ($questions as $q) {
                $q->sort -= 1;
                $q->save();
            }

            $question->delete();
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
}
