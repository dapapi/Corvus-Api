<?php
namespace App\Http\Controllers;

/**
 * Created by PhpStorm.
 * User: wy
 * Date: 2018/11/19
 * Time: 下午2:14
 */

use App\Http\Requests\ReportEditIssuesRequest;
use App\Http\Requests\ReportStoreRequest;
use App\Http\Requests\ReportAllRequest;
use App\Http\Requests\IssuesRequest;
use App\Http\Transformers\ReportTransformer;
use App\Http\Transformers\IssuesTransformer;
use App\Models\Report;
use App\Models\Issues;
use App\Models\IssuesTN;
use App\Models\IssuesTU;
use App\Models\ReportTN;
use App\Models\ReportTU;
use App\Repositories\AffixRepository;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ReportController extends Controller
{
    protected $affixRepository;

    public function __construct(AffixRepository $affixRepository)
    {
        $this->affixRepository = $affixRepository;
    }

    public function index(Request $request)
    {

        $payload = $request->all();

        $pageSize = $request->get('page_size', config('app.page_size'));

        $stars = Report::createDesc()->paginate($pageSize);

        return $this->response->paginator($stars, new ReportTransformer());
    }
    public function all(ReportAllRequest $request){

        $isAll = $request->get('template_name',false);

        if(empty($isAll)){
            return $this->response->errorInternal('参数不正确');
        }
        if($isAll == '0')
        {
            return $this->response->errorInternal('参数不能为零');
        }
        $getbulletinlist = Report::where('template_name',$isAll)->get();
        return $this->response->collection($getbulletinlist, new ReportTransformer($isAll));

    }

    public function store(ReportStoreRequest $request)
    {
        $payload = $request->all();

        $user = Auth::guard('api')->user();
        unset($payload['status']);
        unset($payload['type']);
        $payload['creator_id'] = $user->id;//创建人

        if ($payload['creator_id']) {
            DB::beginTransaction();

        try {

              $arr = report::where('template_name',$payload['template_name'])->get();

               if(!empty($arr[0])){
                   return $this->response->errorInternal('用户名已存在');

               }

             $star = Report::create($payload);

            if(!empty($payload['department_id'])){
            $arr1 = explode(',',$payload['department_id']);
            unset($payload['department_id']);
            $len = count($arr1);
            $pay = [];
            for($i=0;$i < $len;$i++){

                $pay['report_template_name_id'] = $star->id;
                $pay['department_id'] = hashid_decode($arr1[$i]);
                $st1= ReportTN::create($pay);
            }
            }
            if(!empty($payload['member'])) {
                $arr2 = explode(',', $payload['member']);
                unset($payload['member']);
                $len = count($arr2);
                $pay = [];
                for ($i = 0; $i < $len; $i++) {

                    $pay['report_template_name_id'] = $star->id;
                    $pay['user_id'] = hashid_decode($arr2[$i]);
                    $st2 = ReportTU::create($pay);
                }
            }

         }catch (\Exception $e) {
            DB::rollBack();
            Log::error($e);
           return $this->response->errorInternal('创建失败');
        }
        DB::commit();
        }else{
            return $this->response->errorInternal('创建失败');
        }

    }
    public function edit(ReportStoreRequest $request,Report $report){
        $payload = $request->all();
        $isAll = Report::where('template_name',$payload['template_name'])->first();
        if($isAll){
            if($isAll->id != $report->id){
            return $this->response->errorBadRequest('修改失败');
            }
        }
        if ($request->has('template_name')) {
            $array['template_name'] = $payload['template_name'];//姓名
            if ($array['template_name'] != $report->template_name) {
//                $operateName = new OperateEntity([
//                    'obj' => $star,
//                    'title' => '名称',
//                    'start' => $star->name,
//                    'end' => $array['name'],
//                    'method' => OperateLogMethod::UPDATE,
//                ]);
//                $arrayOperateLog[] = $operateName;
            }
        }

        DB::beginTransaction();
        try {
            if (count($array) == 0)
                return $this->response->noContent();

            $report->update($payload);

            // 操作日志
           // event(new OperateLogEvent($arrayOperateLog));
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error($e);
            return $this->response->errorInternal('修改失败');
        }
        DB::commit();

        return $this->response->accepted();

    }
    public function delete(Request $request)
    {
        $isAll = $request->get('all', false);
//
        $payload = hashid_decode($isAll);
        $payload = report::find($payload)->delete();
//       // print_r($payload);
//        $star = report::destroy($payload);
      //  $post = report::find(2)->delete();
        if(!$payload){
            return $this->response->errorInternal('删除失败');
        }else{
            return $this->response->noContent();
        }
   }
    public function index_issues(Request $request)
    {

        $user = $request->get('id', false);
       // $user = Auth::guard('api')->user();
        if($request->has('type')){
            $pageSize = $request->get('page_size', config('app.page_size'));
            $stars = Issues::where('accessory',hashid_decode($user))->where('type',$request->type)->createDesc()->paginate($pageSize);
        }else{
        //$arr = DB::table('report_template_user')->where('user_id',$user->id)->get(['report_template_name_id']);
        $pageSize = $request->get('page_size', config('app.page_size'));
        $stars = Issues::where('accessory',hashid_decode($user))->createDesc()->paginate($pageSize);
    }
        return $this->response->paginator($stars, new IssuesTransformer());
    }
    public function store_issues(Request $request)
    {
        $payload = $request->all();
        $user = Auth::guard('api')->user();
        $payload['creator_id'] = $user->id;
        $accessory = hashid_decode($payload['accessory']);
        $payload['accessory'] = $accessory;
        if ($payload['creator_id']) {
            DB::beginTransaction();

            try {

                $arr = Issues::where('issues',$payload['issues'])->get();
                if(!empty($arr[0])){
                    return $this->response->errorInternal('问题已存在');

                }
                $star = Issues::create($payload);
                if(!empty($payload['department_id'])){
                $arr1 = explode(',',$payload['department_id']);
                unset($payload['department_id']);
                $len = count($arr1);
                $pay = [];
                for($i=0;$i < $len;$i++){

                    $pay['issues_template_name_id'] = $star->id;
                    $pay['department_id'] = hashid_decode($arr1[$i]);
                    $st1= IssuesTN::create($pay);
                }}
                if(!empty($payload['member_id'])){
                $arr2 = explode(',',$payload['member_id']);
                unset($payload['member_Id']);
                $len = count($arr2);
                $pay = [];
                for($i=0;$i < $len;$i++){

                    $pay['issues_template_name_id'] = $star->id;
                    $pay['user_id'] = hashid_decode($arr2[$i]);
                    $st2= IssuesTU::create($pay);
                }}

            }catch (Exception $e) {
                DB::rollBack();
                Log::error($e);
                return $this->response->errorInternal('创建失败');
            }
            DB::commit();
        }else{
            return $this->response->errorInternal('创建失败');
        }

    }
    public function edit_issues(IssuesRequest $request,Issues $Issues)
    {
        $payload = $request->all();
        $arr = Issues::where('issues',$payload['issues'])->first();
        if(!empty($arr)) {
            if ($arr->id !=$Issues->id ) {
            return $this->response->errorInternal('修改失败');
         }
        }
        if ($request->has('issues')) {
            $array['issues'] = $payload['issues'];//姓名
            if ($array['issues'] != $Issues->issues) {
//                $operateName = new OperateEntity([
//                    'obj' => $star,
//                    'title' => '名称',
//                    'start' => $star->name,
//                    'end' => $array['name'],
//                    'method' => OperateLogMethod::UPDATE,
//                ]);
//                $arrayOperateLog[] = $operateName;
            }
        }

        DB::beginTransaction();
        try {
            if (count($array) == 0)
                return $this->response->noContent();

            $Issues->update($payload);
            // 操作日志
            // event(new OperateLogEvent($arrayOperateLog));
        } catch (Exception $e) {
            DB::rollBack();
            Log::error($e);
            return $this->response->errorInternal('修改失败');
        }
        DB::commit();

        return $this->response->accepted();

    }
    public function edit1_issues(Request $request)
    {
        $payload = $request->all();
        if(!$request->has('id')){
            return $this->response->errorInternal('修改失败');
        }
        $id = hashid_decode($payload['id']);
        unset($payload['id']);

        if($payload['operation']=='bottom'){
            DB::beginTransaction();
            try {
           $othertime = Issues::find(hashid_decode($payload['other_id']))->updated_at->format('Y-m-d H:i:s');
           $ottime   =  Issues::find($id)->updated_at->format('Y-m-d H:i:s');
           $temp = $ottime;
             if( $othertime < $ottime){

                 Issues::where('id',$id)->update(['updated_at'=>$othertime]);
                 Issues::where('id',hashid_decode($payload['other_id']))->update(['updated_at'=>$temp]);

           unset($payload['other_id']);
             }
            }catch (Exception $e) {
                DB::rollBack();
                Log::error($e);
                return $this->response->errorInternal('修改失败');
            }
            DB::commit();
        }else if($payload['operation']=='top'){
            DB::beginTransaction();

            try {
            $othertime = Issues::find(hashid_decode($payload['other_id']))->updated_at->format('Y-m-d H:i:s');
            $ottime =  Issues::find($id)->updated_at->format('Y-m-d H:i:s');
            $temp = $ottime ;
                if( $othertime > $ottime) {
                    Issues::where('id',$id)->update(['updated_at' => $othertime]);
                    Issues::where('id',hashid_decode($payload['other_id']))->update(['updated_at' => $temp]);

                }

                unset($payload['other_id']);
            }catch (Exception $e) {
                DB::rollBack();
                Log::error($e);
                return $this->response->errorInternal('创建失败');
            }
            DB::commit();

      }

//        DB::beginTransaction();
//        try{
//            if (count($payload) == 0)
//                return $this->response->noContent();
//            print_r($payload);
//            $star->update($payload);
//            // 操作日志
//
//        } catch (Exception $e) {
//            DB::rollBack();
//            Log::error($e);
//
//        }
//        DB::commit();

        return $this->response->accepted();

    }
    public function delete_issues(Request $request)
    {
        $isAll = $request->get('all', false);
        $payload = hashid_decode($isAll);
        $payload = issues::find($payload)->delete();
        if(!$payload){
            return $this->response->errorInternal('删除失败');
        }else{
            return $this->response->noContent();
        }
    }
}