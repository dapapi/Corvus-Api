<?php

namespace App\Http\Controllers;

use App\Http\Requests\ReviewQuestionnaireStoreRequest;
use App\Http\Requests\ReviewUpdateRequest;
use App\Http\Transformers\ReviewQuestionnaireTransformer;
use App\Models\DepartmentUser;
use App\Models\Production;
use App\Models\ReviewQuestionItem;
use App\Models\ReviewQuestion;
use App\Models\ReviewQuestionnaire;
use Illuminate\Support\Facades\Auth;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ReviewQuestionItemController extends Controller
{

    public function up(ReviewQuestionnaire $reviewquestionnaire, ReviewQuestion $reviewquestion)
    {

//            try {
//                $reviewQuestionItem1 = $question->items()->where('sort', $sort)->first();
//                $reviewQuestionItem2 = $question->items()->where('sort', $sort - 1)->first();
//                $reviewQuestionItem1->sort = $sort - 1;
//                $reviewQuestionItem2->sort = $sort;
//                $reviewQuestionItem1->save();
//                $reviewQuestionItem2->save();
//            } catch (Exception $e) {
//            }
//        }
//        return redirect()->back();
    }

    public function down(Review $review, ReviewQuestion $question, $sort)
    {
        $max = ReviewQuestionItem::max('sort');
        if ($sort < $max) {
            try {
                $reviewQuestionItem1 = $question->items()->where('sort', $sort)->first();
                $reviewQuestionItem2 = $question->items()->where('sort', $sort + 1)->first();
                $reviewQuestionItem1->sort = $sort + 1;
                $reviewQuestionItem2->sort = $sort;
                $reviewQuestionItem1->save();
                $reviewQuestionItem2->save();
            } catch (Exception $e) {
            }
        }
        return redirect()->back();
    }

    public function update(Request $request, ReviewQuestionnaire $reviewquestionnaire, ReviewQuestion $reviewquestion, ReviewQuestionItem $reviewquestionitem)
    {
        $payload = $request->all();
        if (isset($payload['name']) && $payload['name'] != '' && $payload['name'] != $reviewquestionitem->title) {
            try {
                $reviewquestionitem->title = $payload['name'];
                $reviewquestionitem->save();
            } catch (Exception $e) {
                return $this->response->errorBadRequest('修改失败');
            }
            return $this->response->accepted();
        }
        return $this->response->errorBadRequest('修改失败');
    }
    
    public function updateValue(Request $request, ReviewQuestionnaire $reviewquestionnaire, ReviewQuestion $reviewquestion, ReviewQuestionItem $reviewquestionitem)
    {
        $payload = $request->all();
        if (isset($payload['value']) && $payload['value'] != '' && $payload['value'] != $reviewquestionitem->value) {
            try {

                $reviewquestionitem->value = $payload['value'];

                $reviewquestionitem->save();

            } catch (Exception $e) {
                return $this->response->errorBadRequest('修改失败');
            }
            return $this->response->accepted();
        }
        return $this->response->errorBadRequest('修改失败');
    }
    
    public function store(Request $request,ReviewQuestionnaire $reviewquestionnaire, ReviewQuestion $reviewquestion)
    {
        $payload = $request->all();
        if (isset($payload['name']) && $payload['name'] != '') {
            try {

                ReviewQuestionItem::create(['review_question_id' => $reviewquestion->id,'title' => $payload['name'],'value' =>  $payload['value'], 'sort' => $reviewquestion->items()->count() + 1]);
            } catch (Exception $e) {
                return $this->response->errorBadRequest('添加失败');
            }
            return $this->response->accepted();
        }
        return $this->response->errorBadRequest('添加失败');
    }

    public function delete(Review $review, ReviewQuestion $question, ReviewQuestionItem $questionItem)
    {
        try {
            $questionItems = $question->items()->where('sort', '>', $questionItem->sort)->orderBy('sort', 'asc')->get();
            foreach ($questionItems as $qT) {
                $qT->sort -= 1;
                $qT->save();
            }

            $questionItem->delete();
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
