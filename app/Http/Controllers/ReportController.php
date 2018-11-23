<?php
namespace App\Http\Controllers;

/**
 * Created by PhpStorm.
 * User: wy
 * Date: 2018/11/19
 * Time: 下午2:14
 */
use App\Http\Requests\ReportStoreRequest;
use App\Http\Requests\ReportAllRequest;
use App\Http\Transformers\ReportTransformer;
use App\Models\Report;
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
        $payload['creator_id'] = $user->id;

        if ($payload['creator_id']) {
            DB::beginTransaction();

        try {

              $arr = report::where('template_name',$payload['template_name'])->get();

               if(!empty($arr[0])){
                   return $this->response->errorInternal('用户名已存在');

               }

             $star = Report::create($payload);


            $arr1 = explode(',',$payload['department_id']);
            unset($payload['department_id']);
            $len = count($arr1);
            $pay = [];
            for($i=0;$i < $len;$i++){

                $pay['report_template_name_id'] = $star->id;
                $pay['department_id'] = hashid_decode($arr1[$i]);
                $st1= ReportTN::create($pay);
            }
            $arr2 = explode(',',$payload['member']);
            unset($payload['member']);
            $len = count($arr2);
            $pay = [];
            for($i=0;$i < $len;$i++){

                $pay['report_template_name_id'] = $star->id;
                $pay['user_id'] = hashid_decode($arr2[$i]);
                $st2= ReportTU::create($pay);
            }

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
//        DB::beginTransaction();
//        try {
//           // $request->delete();
////            // 操作日志
////            $operate = new OperateEntity([
////                'obj' => $star,
////                'title' => null,
////                'start' => null,
////                'end' => null,
////                'method' => OperateLogMethod::DELETE,
////            ]);
////            event(new OperateLogEvent([
////                $operate,
////            ]));
//        } catch (Exception $e) {
//            DB::rollBack();
//            Log::error($e);
//            return $this->response->errorInternal('删除失败');
//        }
//        DB::commit();
//        return $this->response->noContent();
   }
}