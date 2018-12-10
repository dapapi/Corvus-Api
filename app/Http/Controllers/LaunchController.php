<?php
namespace App\Http\Controllers;

/**
 * Created by PhpStorm.
 * User: wy
 * Date: 2018/11/19
 * Time: 下午2:14
 */



use Ap\Gender;
use App\Http\Requests\LaunchAllRequest;
use App\Http\Requests\LaunchStoreRequest;
use App\Http\Transformers\IssuesTransformer;
use App\Http\Transformers\AnswerTransformer;
use App\Http\Requests\DraftStoreRequest;
Use App\Http\Transformers\ReportTransformer;
Use App\Http\Transformers\DraftTransformer;
use App\Models\BulletinReviewTitle;
use App\Models\Report;
use App\Models\BulletinReview;
use App\Models\DraftIssuesAnswer;
use App\Models\Issues;
use App\Models\Draft;
use App\Models\Answer;
use App\Models\ReportTemplateUser;
use App\Models\BulletinReviewTitleIssuesAnswer;
use App\Repositories\AffixRepository;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;


class LaunchController extends Controller
{
    protected $affixRepository;
    protected $Approval = 1;
    public function __construct(AffixRepository $affixRepository)
    {
        $this->affixRepository = $affixRepository;
    }

    public function index(Request $request)
    {
        $payload = $request->all();
        $user = Auth::guard('api')->user();

        $arr = ReportTemplateUser::where('user_id',$user->id)->get(['report_template_name_id']);
        //$arr = DB::table('report_template_user')->where('user_id',$user->id)->get(['report_template_name_id']);
        $pageSize = $request->get('page_size', config('app.page_size'));
        foreach($arr->toarray() as $key => $value) {
            $num = $value['report_template_name_id'];
            $user = Report::find($num);
            if ($user) {
                $sally = $user->Status;
            } else {
                $sally = '';
            }
//            Report::find($num)->state = $sally;
        }
        $stars = Report::wherein('id',$arr)->createDesc()->paginate($pageSize);

        return $this->response->paginator($stars, new ReportTransformer());
    }
    public function all(LaunchAllRequest $request)
    {

        $All = $request->get('accessory',false);
        $payload = $request->all();
        $user = Auth::guard('api')->user();
        if(empty($All)){
            return $this->response->errorInternal('参数不正确');
        }
        if($All == '0')
        {
            return $this->response->errorInternal('参数不能为零');
        }

        $array[] = ['member',$user->id];
        $array[] = ['template_id',hashid_decode($All)];
        $array[] = ['created_at','>=', $payload['start_time']];
        $array[] = ['created_at','<=', $payload['end_time']];

//            if(empty(draft::where($array)->first())){
//
//            }else{
//                $arr = draft::where($array)->first()->Answer->toarray();
//            }
//empty($data)
        $data = Issues::where('accessory',hashid_decode($All))->get(['id']);
        print_r($data);
        if(count($data->toArray())!=0){

        foreach($data->toarray() as $key => $value) {

            $num = $value['id'];
           if(empty(draft::where($array)->first())){
               $isAll = '';
           }else{

            $user =  draft::where($array)->first()->Answer;
            if ($user) {
                $isAll = $user;


            } else {
                $isAll = '';
            }
          }
        }
        }else{

            $isAll = '';
        }
        $arr = Issues::where('accessory',hashid_decode($All))->get();
        foreach($arr->toarray() as $key => $value) {
            $num = $value['id'];
            $user = Issues::find($num);
            if ($user) {
                $sally = $user->Status;
            } else {
                $sally = '';
            }
//            Report::find($num)->state = $sally;
        }
        $getbulletinlist = issues::where('accessory',hashid_decode($All))->createDesc()->get();
        return $this->response->collection($getbulletinlist, new IssuesTransformer($isAll));

    }
//    public function allDraft(LaunchAllRequest $request)
//    {
//        $isAll = $request->get('accessory',false);
//        $payload = $request->all();
//        $user = Auth::guard('api')->user();
//        if(empty($isAll)){
//            return $this->response->errorInternal('参数不正确');
//        }
//        if($isAll == '0')
//        {
//            return $this->response->errorInternal('参数不能为零');
//        }
//        $array[] = ['member',$user->id];
//        $array[] = ['template_id',hashid_decode($isAll)];
//        $arr = draft::where($array)->first()->Answer->toarray();
//        return $this->response->collection($getbulletinlist, new IssuesTransformer($isAll));
//
//    }
    public function store(LaunchStoreRequest $request)
    {

        $payload = $request->all();
        $user = Auth::guard('api')->user();
        unset($payload['status']);
        unset($payload['type']);
        $payload['creator_id'] = $user->id;
        if($payload['answer']){

            DB::beginTransaction();
       try {

           $accessory = hashid_decode($payload['accessory']);
           unset($payload['accessory']);
//
//           foreach($payload['answer'] as $key => $value){
//               $payload['issues_id'] = hashid_decode($key);
//               $payload['answer'] = $value;
//               $star = Answer::create($payload);
//             }
           $template = Report::find($accessory);
           $paysload=new BulletinReview();
           $paysload->template_id=$template->id;
           $paysload->member = $user->id;
           if($template->frequency == 1){
             $data = date("Y年m月d日",time()).','.date("Y年m月d日",time());
           }else if($template->frequency == 2){
               $timestr = time();
               $now_day = date('w',$timestr);
               //获取一周的第一天，注意第一天应该是星期天
               $sunday_str = $timestr - ($now_day-1)*60*60*24;
               $sunday = date('m月d日', $sunday_str);
               //获取一周的最后一天，注意最后一天是星期六
               $strday_str = $timestr + (7-$now_day)*60*60*24;
               $strday = date('m月d日', $strday_str);
               $data = date('Y',time()).'年第'.date('W',time()).'周'.','.$sunday.'-'.$strday;

           }else if($template->frequency == 3){
               $now_month_first_date = date('m月1日');
               $now_month_last_date  = date('m月d日',strtotime(date('Y-m-1',strtotime('next month')).'-1 day'));
               $data = date('Y年m月',time()).','.$now_month_first_date.'-'.$now_month_last_date;

           }else if($template->frequency == 4){
               $season = ceil(date('n') /3);
               $data = date('Y年第').$season.'季度'.','.date('m月1日',mktime(0,0,0,($season - 1) *3 +1,1,date('Y'))).'-'.date('m月t日',mktime(0,0,0,$season * 3,1,date('Y')));

           }else if($template->frequency == 5){
               $data = date('Y年').','.date('1月1日').'-'.date('12月31日');
           }
           $paysload->title = $data;
           $paysload->status = $this->Approval;
           $bool = $paysload->save();
           $bulletionload=new BulletinReviewTitle();
           $bulletionload->bulletin_review_id=$paysload->id;
           $bulletionload->creator_id=$user->id;
           if($template->frequency == 1) {
               $data1 = $user->name.'的'.$template->template_name.'-'.date("Y年m月d日", time());
           }else if($template->frequency == 2){
               $timestr = time();
               $now_day = date('w',$timestr);
               //获取一周的第一天，注意第一天应该是星期天
               $sunday_str = $timestr - ($now_day-1)*60*60*24;
               $sunday = date('m月d日', $sunday_str);
               //获取一周的最后一天，注意最后一天是星期六
               $strday_str = $timestr + (7-$now_day)*60*60*24;
               $strday = date('m月d日', $strday_str);
               $data1 = $user->name.'的'.$template->template_name.'-第'.date('W',time()).'周'.','.$sunday.'-'.$strday;

           }else if($template->frequency == 3){
               $now_month_first_date = date('m月1日');
               $now_month_last_date  = date('m月d日',strtotime(date('Y-m-1',strtotime('next month')).'-1 day'));
               $data1 = $user->name.'的'.$template->template_name.'-'.date('m月',time()).','.$now_month_first_date.'-'.$now_month_last_date;

           }else if($template->frequency == 4){
               $season = ceil(date('n') /3);
               $data1 = $user->name.'的'.$template->template_name.'-'.date('第').$season.'季度'.','.date('m月1日',mktime(0,0,0,($season - 1) *3 +1,1,date('Y'))).'-'.date('m月t日',mktime(0,0,0,$season * 3,1,date('Y')));

           }else if($template->frequency == 5){
               $data1 = $user->name.'的'.$template->template_name.'-'.date('Y年').','.date('1月1日').'-'.date('12月31日');
           }
           $bulletionload->title=$data1;
           $bulletionload->status = $this->Approval;
           $booll = $bulletionload->save();
           $review_id = $bulletionload->id;
           foreach($payload['answer'] as $key => $value){
               $bulletionload=new BulletinReviewTitleIssuesAnswer();
               $bulletionload->bulletin_review_title_id=$review_id;
               $bulletionload->issues = Issues::find(hashid_decode($key))->issues;
               $bulletionload->type  = Issues::find(hashid_decode($key))->type;
               $bulletionload->answer = $value;
              $review = $bulletionload->save();
               $payload['reviewer_id'] = hashid_decode($payload['reviewer_id']);
               $payload['issues_id'] = hashid_decode($key);
               $payload['answer'] = $value;
               $star = Answer::create($payload);
           }
           }catch (Exception $e) {
            DB::rollBack();
            Log::error($e);
            return $this->response->errorInternal('创建失败');
        }
        DB::commit();
        }
//        DB::beginTransaction();
//       try {
            // 操作日志
//            $operate = new OperateEntity([
//                'obj' => $star,
//                'title' => null,
//                'start' => null,
//                'end' => null,
//                'method' => OperateLogMethod::CREATE,
//            ]);
//            event(new OperateLogEvent([
//                $operate,
//            ]));
//
//            if ($request->has('affix') && count($request->get('affix'))) {
//                $affixes = $request->get('affix');
//                foreach ($affixes as $affix) {
//                    try {
//                        $this->affixRepository->addAffix($user, null, null, $star, null, null, null, $affix['title'], $affix['url'], $affix['size'], $affix['type']);
//                        // 操作日志 ...
//                    } catch (Exception $e) {
//                    }
//                }
//            }
//        } catch (Exception $e) {
//            DB::rollBack();
//            Log::error($e);
//            return $this->response->errorInternal('创建失败');
//        }
//        DB::commit();

        return $this->response->noContent();
    }
    public function storeDraft(DraftStoreRequest $request,draft $draft,report $report)
    {

        $payload = $request->all();
        $user = Auth::guard('api')->user();
        $payload['member'] = $user->id;
        $payload['template_id'] = $report->id;
        $payload['reviewer_id'] = hashid_decode($payload['reviewer_id']);
            DB::beginTransaction();
            try {

            $draftload  =  draft::create($payload);
            $payloadissues['draft_id'] = $draftload->id;
            foreach($payload['answer'] as $key => $value){
                $payloadissues['issues_id'] = hashid_decode($key);
                $payloadissues['answer'] = $value;
                $draftissuesanswer = DraftIssuesAnswer::create($payloadissues);
             }


            } catch (Exception $e) {
                DB::rollBack();
                Log::error($e);
                return $this->response->errorInternal('创建失败');
            }
            DB::commit();
        }
    public function indexDraft(Request $request)
    {

        $payload = $request->all();
        $pageSize = $request->get('page_size', config('app.page_size'));

        $stars = Draft::createDesc()->paginate($pageSize);

        return $this->response->paginator($stars, new DraftTransformer());


    }
    public function deleteDraft(Request $request,draft $draft)
    {


        $status = $request->get('delete_id');
        DB::beginTransaction();
        try {
             if($status){
              draft::where('id',hashid_decode($status))->delete();
             }
        } catch (Exception $e) {
            DB::rollBack();
            Log::error($e);
            return $this->response->errorInternal('删除失败');
        }
        DB::commit();
    }


}