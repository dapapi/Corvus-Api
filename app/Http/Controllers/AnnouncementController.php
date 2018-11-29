<?php
namespace App\Http\Controllers;

/**
 * Created by PhpStorm.
 * User: wy
 * Date: 2018/11/19
 * Time: 下午2:14
 */

use App\Http\Requests\AccessoryStoreRequest;
use App\Http\Transformers\AnnouncementTransformer;
use App\Models\Announcement;
use App\Repositories\AffixRepository;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class AnnouncementController extends Controller
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

        $stars = Announcement::createDesc()->paginate($pageSize);
        return $this->response->paginator($stars, new AnnouncementTransformer());
    }
    public function show(Request $request,Announcement $announcement)
    {

        $reviewdata = Announcement::where('id',$announcement->id)->first();

        return $this->response->item($reviewdata, new AnnouncementTransformer());
    }
    public function store(AccessoryStoreRequest $request,Announcement $announcement)
    {
        $payload = $request->all();
        $user = Auth::guard('api')->user();
        unset($payload['status']);
        unset($payload['type']);
        $payload['creator_id'] = $user->id;//发布人
        if ($payload['creator_id']) {
            if(!empty($payload['scope']))
            {
                $payload['scope'] = hashid_decode($payload['scope']);
            }
            DB::beginTransaction();
            try {
                $star = Announcement::create($payload);
            }catch (\Exception $e) {
                dd($e);
                DB::rollBack();
                Log::error($e);
                return $this->response->errorInternal('创建失败');
            }
            DB::commit();
        }else{
            return $this->response->errorInternal('创建失败');
        }

    }
}