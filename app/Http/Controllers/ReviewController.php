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
use App\Http\Transformers\ReviewTransformer;
use App\Models\BulletinReview;
use App\Models\Report;
use App\Models\ReportTemplateUser;
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
        $arr1 = Report::wherein('id',$arr)->where('template_name','like','%'.'日报'.'%')->get(['id','template_name']);
        if(!empty($arr1)){
            $stars = BulletinReview::where('status',$status)->createDesc()->paginate($pageSize);
        }else{
        $stars = BulletinReview::where('status',$status)->createDesc()->paginate($pageSize);
          }
        return $this->response->paginator($stars, new ReviewTransformer());
    }


}