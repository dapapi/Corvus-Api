<?php
namespace App\Http\Controllers;

/**
 * Created by PhpStorm.
 * User: wy
 * Date: 2018/11/19
 * Time: 下午2:14
 */


use App\CommunicationStatus;
use App\Events\OperateLogEvent;
use Ap\Gender;
use App\Http\Requests\LaunchAllRequest;
use App\Http\Requests\LaunchStoreRequest;
use App\Http\Transformers\LaunchTransformer;
use App\Http\Transformers\IssuesTransformer;
use App\Http\Transformers\AnswerTransformer;
Use App\Http\Transformers\ReportTransformer;
use App\Models\Report;
use App\Models\Issues;
use App\Models\Answer;
use App\Models\ReportTemplateUser;
use App\OperateLogMethod;
use App\Repositories\AffixRepository;
use App\User;
use App\Whether;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;


class LaunchController extends Controller
{
    protected $affixRepository;

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
        $stars = Report::wherein('id',$arr)->createDesc()->paginate($pageSize);

        return $this->response->paginator($stars, new ReportTransformer());
    }
    public function all(LaunchAllRequest $request)
    {
        $isAll = $request->get('accessory',false);
//        $bloggers = Launch::createDesc()->get();
//        return $this->response->collection($bloggers, new LaunchTransformer($isAll));
//

        if(empty($isAll)){
            return $this->response->errorInternal('参数不正确');
        }
        if($isAll == '0')
        {
            return $this->response->errorInternal('参数不能为零');
        }

        $getbulletinlist = issues::where('accessory',hashid_decode($isAll))->createDesc()->get();


        return $this->response->collection($getbulletinlist, new IssuesTransformer($isAll));
    }
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


           foreach($payload['answer'] as $key => $value){
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



}