<?php
namespace App\Http\Controllers;

/**
 * Created by PhpStorm.
 * User: wy
 * Date: 2018/11/19
 * Time: 下午2:14
 */

use App\Http\Requests\ReportEditIssuesRequest;
use App\Http\Requests\ReviewUpdateRequest;
use App\Http\Requests\ReviewAllRequest;
use App\Http\Requests\ReportStoreRequest;
use App\Http\Requests\ReportAllRequest;
use App\Http\Transformers\ReviewTitleTransformer;
use App\Models\DepartmentUser;
use App\Http\Transformers\ReviewTransformer;
use App\Models\BulletinReview;
use App\Models\Report;
use App\Models\Review;
use App\Models\BulletinReviewTitle;
use App\Models\ReportTemplateUser;
use App\Models\OperateEntity;
use App\Repositories\AffixRepository;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ReviewController extends Controller
{
    protected $affixRepository;

    public function __construct(AffixRepository $affixRepository)
    {
        $this->affixRepository = $affixRepository;
    }

    public function index(Request $request)
    {
        $payload = $request->all();
        $status = $request->get('status')?$request->get('status'):1;
        $pageSize = $request->get('page_size', config('app.page_size'));
        $search = $request->get('search');   //搜索框
        $user = Auth::guard('api')->user();
        $arr = ReportTemplateUser::where('user_id',$user->id)->get(['report_template_name_id']);
        if(!empty($search)){
            $arr1 = Report::wherein('id',$arr)->where('template_name','like','%'.$search.'%')->get(['id']);
         }else{
            $arr1 = Report::wherein('id',$arr)->get(['id']);
        }
        if(!empty($arr1)){
            $stars = BulletinReview::wherein('template_id',$arr1->toarray())->where('status',$status)->createDesc()->paginate($pageSize);
        }else{
            $stars = BulletinReview::where('template_id','99')->where('status',$status)->createDesc()->paginate($pageSize);
          }
        return $this->response->paginator($stars, new ReviewTransformer());
    }
    public function myTemplate(ReviewAllRequest $request)
    {
        $payload = $request->all();
        $user = Auth::guard('api')->user();
        $status = $request->get('status')?$request->get('status'):1;
        if(!empty($status)){
            $array['status'] = $status;
        }
        $search = $request->get('search');
        if(!empty($search)){
            $array[] = ['title','like','%'.$search.'%'];
        }
        $array['template_id'] = hashid_decode($payload['template_id']);
        $array['member'] = $user->id;//创建人
        $array[] = ['created_at','>=', $payload['start_time']];
        $array[] = ['created_at','<=', $payload['end_time']];
        $pageSize = $request->get('page_size', config('app.page_size'));
         $str = BulletinReview::where($array)->createDesc()->paginate($pageSize);
//        $payload = $request->all();
//        $status = $request->get('status')?$request->get('status'):1;
//        $pageSize = $request->get('page_size', config('app.page_size'));
//        $search = $request->get('search');   //搜索框
//        $user = Auth::guard('api')->user();
//        $arr = ReportTemplateUser::where('user_id',$user->id)->get(['report_template_name_id']);
//        $arr1 = Report::wherein('id',$arr)->where('template_name','like','%'.'日报'.'%')->get(['id','template_name']);
//        if(!empty($arr1)){
//            $stars = BulletinReview::where('status',$status)->createDesc()->paginate($pageSize);
//
//        }else{
//            $stars = BulletinReview::where('status',$status)->createDesc()->paginate($pageSize);
//        }
        return $this->response->paginator($str, new ReviewTransformer());
    }
    public function memberTemplate(ReviewAllRequest $request)
    {
        $payload = $request->all();
        $user = Auth::guard('api')->user();
        $userId = $user->id;
        $department = DepartmentUser::where('user_id',$userId)->get(['department_id'])->toarray();
        $array = array();
        if(count($department) >= 2){

            foreach ($department as $key => $value){
               $users[$key] = DepartmentUser::where($value)->get(['user_id'])->toarray();
              foreach($users[$key] as $key => $value){
               $array[$key] = $value['user_id'];
              }

            }
        }
        $status = $request->get('status')?$request->get('status'):1;
        if(!empty($status)){
            $arraydate['status'] = $status;
        }
        $search = $request->get('search');
        if(!empty($search)){
            $arraydate[] = ['title','like','%'.$search.'%'];
        }

        $arraydate['template_id'] = hashid_decode($payload['template_id']);
        $arraydate[] = ['created_at','>=', $payload['start_time']];
        $arraydate[] = ['created_at','<=', $payload['end_time']];
        $arraydatemember['member'] = array_unique($array);//成员
        $pageSize = $request->get('page_size', config('app.page_size'));
        $str = BulletinReview::where($arraydate)->wherein('member',$arraydatemember['member'])->createDesc()->paginate($pageSize);
//        $payload = $request->all();
//        $status = $request->get('status')?$request->get('status'):1;
//        $pageSize = $request->get('page_size', config('app.page_size'));
//        $search = $request->get('search');   //搜索框
//        $user = Auth::guard('api')->user();
//        $arr = ReportTemplateUser::where('user_id',$user->id)->get(['report_template_name_id']);
//        $arr1 = Report::wherein('id',$arr)->where('template_name','like','%'.'日报'.'%')->get(['id','template_name']);
//        if(!empty($arr1)){
//            $stars = BulletinReview::where('status',$status)->createDesc()->paginate($pageSize);
//
//        }else{
//            $stars = BulletinReview::where('status',$status)->createDesc()->paginate($pageSize);
//        }
        return $this->response->paginator($str, new ReviewTransformer());
    }
    public function statistics(ReviewAllRequest $request)
    {
        $payload = $request->all();
        $arraydate['template_id'] = hashid_decode($payload['template_id']);
        $arraydate[] = ['created_at','>=', $payload['start_time']];
        $arraydate[] = ['created_at','<=', $payload['end_time']];
        $pageSize = $request->get('page_size', config('app.page_size'));
        $str = BulletinReview::where($arraydate)->createDesc()->paginate($pageSize);

        return $this->response->paginator($str, new ReviewTransformer());
    }
    public function show(Request $request,review $review)
    {


        $reviewdata = BulletinReviewTitle::where('bulletin_review_id',$review->id)->first();
        // 操作日志
//        $operate = new OperateEntity([
//            'obj' => $blogger,
//            'title' => null,
//            'start' => null,
//            'end' => null,
//            'method' => OperateLogMethod::LOOK,/Users/wy/Code/php/Corvus-Api/app/Http/Transformers/ReportTransformer.php
//        ]);
//        event(new OperateLogEvent([
//            $operate,
//        ]));
        return $this->response->item($reviewdata, new ReviewTitleTransformer());
    }

    public function edit(ReviewUpdateRequest $request,Review $review)
    {
        $payload = $request->all();
        DB::beginTransaction();
        try {
            $bulletion = $review->update($payload);
            $bulletion_title = BulletinReviewTitle::where('bulletin_review_id',$review->id)->first()->update($payload);
//            // 操作日志
//            $operate = new OperateEntity([
//                'obj' => $blogger,
//                'title' => null,
//                'start' => null,
//                'end' => null,
//                'method' => OperateLogMethod::CREATE,
//            ]);
//            event(new OperateLogEvent([
//                $operate,
//            ]));
        } catch (Exception $e) {

            DB::rollBack();
            Log::error($e);
            return $this->response->errorInternal('修改失败');
        }
        DB::commit();

       // return $this->response->item(Blogger::find($blogger->id), new BloggerTransformer());

    }

    public function editAnswer(ReviewUpdateAnswerRequest $request,Review $review)
    {
        $payload = $request->all();
        DB::beginTransaction();
        try {
            $bulletion = $review->update($payload);
            $bulletion_title = BulletinReviewTitle::where('bulletin_review_id',$review->id)->first()->update($payload);
//            // 操作日志
//            $operate = new OperateEntity([
//                'obj' => $blogger,
//                'title' => null,
//                'start' => null,
//                'end' => null,
//                'method' => OperateLogMethod::CREATE,
//            ]);
//            event(new OperateLogEvent([
//                $operate,
//            ]));
        } catch (Exception $e) {

            DB::rollBack();
            Log::error($e);
            return $this->response->errorInternal('修改失败');
        }
        DB::commit();

        // return $this->response->item(Blogger::find($blogger->id), new BloggerTransformer());

    }

}