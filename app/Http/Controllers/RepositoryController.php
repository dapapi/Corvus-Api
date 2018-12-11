<?php
namespace App\Http\Controllers;

/**
 * Created by PhpStorm.
 * User: wy
 * Date: 2018/11/19
 * Time: 下午2:14
 */

use App\Http\Requests\RepositoryRequest;
use App\Http\Transformers\AnnouncementTransformer;
use App\Http\Requests\AnnouncementUpdateRequest;
use App\Models\Announcement;
use App\Models\Repository;
use App\Models\AnnouncementScope;
use App\Repositories\AffixRepository;
use Illuminate\Http\Request;
use App\Events\OperateLogEvent;
use App\Models\OperateEntity;
use App\OperateLogMethod;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class RepositoryController extends Controller
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
        $userId = $user->id;
        $pageSize = $request->get('page_size', config('app.page_size'));
        $stars = Repository::createDesc()->paginate($pageSize);
        return $this->response->paginator($stars, new AnnouncementTransformer());
    }
    public function show(Request $request,Announcement $announcement)
    {


        return $this->response->item($announcement, new AnnouncementTransformer());

    }
    public function store(RepositoryRequest $request,Repository $repository)
    {
        $payload = $request->all();
        $user = Auth::guard('api')->user();
        unset($payload['status']);
        unset($payload['type']);
        $payload['creator_id'] = $user->id;//发布人
        if ($payload['creator_id']) {

            DB::beginTransaction();
            try {
                dd($payload);
                $star = Repository::create($payload);


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
    public function remove(Announcement $announcement)
    {
        DB::beginTransaction();
        try {
            $announcement->delete();
            // 操作日志
            $operate = new OperateEntity([
                'obj' => $announcement,
                'title' => null,
                'start' => null,
                'end' => null,
                'method' => OperateLogMethod::DELETE,
            ]);
            event(new OperateLogEvent([
                $operate,
            ]));
        } catch (Exception $e) {
            dd($e);
            DB::rollBack();
            Log::error($e);
            return $this->response->errorInternal('删除失败');
        }
        DB::commit();
    }
    public function edit(AnnouncementUpdateRequest $request, Announcement $announcement)
    {
        $payload = $request->all();
        $array = [];
        $arrayOperateLog = [];
        if ($request->has('title')) {
            $array['title'] = $payload['title'];
            if ($array['title'] != $announcement->title) {
                $operateNickname = new OperateEntity([
                    'obj' => $announcement,
                    'title' => '标题',
                    'start' => $announcement->title,
                    'end' => $array['title'],
                    'method' => OperateLogMethod::UPDATE,
                ]);
                $arrayOperateLog[] = $operateNickname;
            } else {
                unset($array['title']);
            }
        }
        if ($request->has('scope')) {
            $scope = $payload['scope'];
            $len = count($payload['scope']);
            if($len >= 2){
                $arr = array();

                foreach($scope as $key => $value){
                    $arr['scope'][$key] = hashid_decode($value);
                }
                $array['scope'] = implode(',',$arr['scope']);
            }else{
                $array['scope'] = hashid_decode(array_values($payload['scope'])[0]);
            }
            if ($array['scope'] != $announcement->scope) {
                $operateNickname = new OperateEntity([
                    'obj' => $announcement,
                    'title' => '公告范围',
                    'start' => $announcement->title,
                    'end' => $array['scope'],
                    'method' => OperateLogMethod::UPDATE,
                ]);
                $arrayOperateLog[] = $operateNickname;
            } else {
                unset($array['scope']);
            }
        }
        if ($request->has('classify')) {
            $array['classify'] = $payload['classify'];
            if ($array['classify'] != $announcement->classify) {
                $operateNickname = new OperateEntity([
                    'obj' => $announcement,
                    'title' => '分类',
                    'start' => $announcement->classify,
                    'end' => $array['classify'],
                    'method' => OperateLogMethod::UPDATE,
                ]);
                $arrayOperateLog[] = $operateNickname;
            } else {
                unset($array['classify']);
            }
        }
        if ($request->has('desc')) {
            $array['desc'] = $payload['desc'];
            if ($array['desc'] != $announcement->desc) {
                $operateNickname = new OperateEntity([
                    'obj' => $announcement,
                    'title' => '公告内容',
                    'start' => $announcement->desc,
                    'end' => $array['desc'],
                    'method' => OperateLogMethod::UPDATE,
                ]);
                $arrayOperateLog[] = $operateNickname;
            } else {
                unset($array['desc']);
            }
        }
        if ($request->has('accessory')) {
            $array['accessory'] = $payload['accessory'];
            if ($array['accessory'] != $announcement->accessory) {
                $operateNickname = new OperateEntity([
                    'obj' => $announcement,
                    'title' => '公告附件修改',
                    'start' => $announcement->accessory,
                    'end' => $array['accessory'],
                    'method' => OperateLogMethod::UPDATE,
                ]);
                $arrayOperateLog[] = $operateNickname;
            } else {
                unset($array['accessory']);
            }
        }
        if ($request->has('stick')) {
            $array['stick'] = $payload['stick'];
            if ($array['stick'] != $announcement->stick) {
                $operateNickname = new OperateEntity([
                    'obj' => $announcement,
                    'title' => '是否制顶',
                    'start' => $announcement->stick,
                    'end' => $array['stick'],
                    'method' => OperateLogMethod::UPDATE,
                ]);
                $arrayOperateLog[] = $operateNickname;
            } else {
                unset($array['stick']);
            }
        }
        DB::beginTransaction();
        try {
            if (count($array) == 0)
                return $this->response->noContent();
            $announcement->update($array);
            if(!empty($scope)){
               $announdelete =  AnnouncementScope::where('announcement_id',$announcement->id)->delete();
                if($announdelete){
            foreach($scope as $key => $value){
                $arr['announcement_id'] = $announcement->id;
                $arr['department_id'] = hashid_decode($value);
                $data = AnnouncementScope::create($arr);
             }
            }
            }
            // 操作日志
            event(new OperateLogEvent($arrayOperateLog));
        } catch (Exception $e) {
            DB::rollBack();
            Log::error($e);
            return $this->response->errorInternal('修改失败');
        }
        DB::commit();

        return $this->response->accepted();

    }

}