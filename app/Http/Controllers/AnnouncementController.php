<?php
namespace App\Http\Controllers;
/**
 * Created by PhpStorm.
 * User: wy
 * Date: 2018/11/19
 * Time: 下午2:14
 */

use App\Events\AnnouncementMessageEvent;
use App\ModuleableType;

use App\AffixType;
use App\Http\Requests\AccessoryStoreRequest;
use App\Http\Transformers\AnnouncementTransformer;
use App\Http\Transformers\DepartmentTransformer;
use App\Http\Requests\AnnouncementClassifyUpdateRequest;
use App\Http\Transformers\AnnouncementClassifyTransformer;
use App\Http\Transformers\AnnouncementListTransformer;
use App\Http\Requests\AnnouncementUpdateRequest;
use App\Models\Announcement;
use App\Models\Department;
use App\Models\DepartmentUser;
use App\Models\AnnouncementClassify;
use App\Models\AnnouncementScope;
use App\Models\OperateLog;

use App\Repositories\AffixRepository;
use App\TriggerPoint\AnnouncementTriggerPoint;
use Illuminate\Http\Request;
use App\Events\OperateLogEvent;
use App\Repositories\OperateLogRepository;
use App\Models\OperateEntity;
use App\OperateLogMethod;
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
        $user = Auth::guard('api')->user();
        $status = empty($payload['status'])?1:$payload['status'];
        $readflag = empty($payload['readflag'])?2:$payload['readflag'];
        $userId = $user->id;
        $department = DepartmentUser::where('user_id',$userId)->get(['department_id'])->toarray();
        $pageSize = $request->get('page_size', config('app.page_size'));
        if(empty($department)){
            $ar = '';
            $stars = Announcement::where('id',$ar)->createDesc()->paginate(0);
        }else{
              if($status == 1 || $status == 2){
                $len = count($department);
                $array = array();
                for ($i=0;$i<$len;$i++){
                    $announcement_id = DB::select('SELECT T3.announcement_id FROM  (SELECT T2.id as department_id FROM ( SELECT @r AS _id, (SELECT @r := department_pid FROM 
              departments WHERE id = _id) AS department_pid, @l := @l + 1 AS lvl FROM (SELECT @r := ?, @l := 0) vars, departments h WHERE @r <> 0 ) T1 JOIN departments T2 ON T1._id = T2.id 
              ORDER BY T1.lvl DESC) T4 JOIN announcement_scope T3 ON T4.department_id = T3.department_id', [$department[$i]['department_id']]);
                    $array[$i] = $announcement_id;
                    $arr = array_merge($array[$i]);
                }
                $ar =array();
                foreach ($arr as $key => $value)
                {
                    $ar[$key] = $value->announcement_id;
                }
                $query =   Announcement::whereIn('announcement.id',$ar)
                      ->leftJoin('operate_logs',function($join){
                          $join->on('announcement.id','operate_logs.logable_id')
                              ->where('logable_type',ModuleableType::ANNOUNCEMENT)
                              ->where('operate_logs.method',OperateLogMethod::LOOK);
                      });
                if($status == 1){
                    if($readflag == 2){
                        $queryId =   Announcement::whereIn('announcement.id',$ar)
                            ->leftJoin('operate_logs',function($join) use($readflag,$userId){
                                $join->on('announcement.id','operate_logs.logable_id')
                                    ->where('logable_type',ModuleableType::ANNOUNCEMENT)
                                    ->where('operate_logs.method',OperateLogMethod::LOOK);
                            })
                            ->where('operate_logs.status',1)
                            ->where('operate_logs.user_id',$userId)
                            ->groupBy('announcement.id')
                            ->select('announcement.id');
                        $stars = $query->whereNotIn('announcement.id',$queryId)->groupBy('announcement.id')
                            ->createDesc()->select('announcement.id','announcement.title','announcement.scope','announcement.classify','announcement.desc','announcement.readflag'
                                ,'announcement.is_accessory','announcement.creator_id','announcement.stick','announcement.created_at'
                                ,'announcement.updated_at')
                         ->paginate($pageSize);
//                    $sql_with_bindings = str_replace_array('?', $stars->getBindings(), $stars->toSql());
//                    dd($sql_with_bindings);


                    }else{
                    $stars = $query->where('operate_logs.status',$readflag)->where('operate_logs.user_id',$userId)->groupBy('announcement.id')
                    ->createDesc()->select('announcement.id','announcement.title','announcement.scope','announcement.classify','announcement.desc','announcement.readflag'
                        ,'announcement.is_accessory','announcement.creator_id','announcement.stick','announcement.created_at'
                            ,'announcement.updated_at')
                        ->paginate($pageSize);
                    }
//                $sql_with_bindings = str_replace_array('?', $stars->getBindings(), $stars->toSql());
//        dd($sql_with_bindings);
                }else{
                    if($readflag == 2){
                        $starsId = Announcement::leftJoin('operate_logs',function($join){
                            $join->on('announcement.id','operate_logs.logable_id')
                                ->where('logable_type',ModuleableType::ANNOUNCEMENT)
                                ->where('operate_logs.method',OperateLogMethod::LOOK);
                        })->where('operate_logs.status',1)->where('operate_logs.user_id',$userId)->where('announcement.creator_id',$userId)->groupBy('announcement.id')->select('announcement.id');
                        $stars = Announcement::leftJoin('operate_logs',function($join){
                            $join->on('announcement.id','operate_logs.logable_id')
                                ->where('logable_type',ModuleableType::ANNOUNCEMENT)
                                ->where('operate_logs.method',OperateLogMethod::LOOK);
                        })->whereNotIn('announcement.id',$starsId)->where('announcement.creator_id',$userId)->groupBy('announcement.id')
                        ->createDesc()->select('announcement.id','announcement.title','announcement.scope','announcement.classify','announcement.desc','announcement.readflag'
                                ,'announcement.is_accessory','announcement.creator_id','announcement.stick','announcement.created_at'

                                ,'announcement.updated_at')
                            ->paginate($pageSize);

                        //->where('operate_logs.user_id','<>',$userId)
//                        $sql_with_bindings = str_replace_array('?', $stars->getBindings(), $stars->toSql());
//                        dd($sql_with_bindings);
                    }else{
                    $stars = Announcement::leftJoin('operate_logs',function($join){
                        $join->on('announcement.id','operate_logs.logable_id')
                            ->where('logable_type',ModuleableType::ANNOUNCEMENT)
                            ->where('operate_logs.method',OperateLogMethod::LOOK);
                    })->where('operate_logs.status',$readflag)->where('operate_logs.user_id',$userId)->groupBy('announcement.id')->where('announcement.creator_id',$userId)
                        ->createDesc()->select('announcement.id','announcement.title','announcement.scope','announcement.classify','announcement.desc','announcement.readflag'
                            ,'announcement.is_accessory','announcement.creator_id','announcement.stick','announcement.created_at'
                            ,'announcement.updated_at')->paginate($pageSize);
//                         $sql_with_bindings = str_replace_array('?', $stars->getBindings(), $stars->toSql());
//        dd($sql_with_bindings);
                    }
                }
              }else{
                  $stars = null;
              }
        }
        return $this->response->paginator($stars, new AnnouncementListTransformer());
    }
//    public function generateTree($array,$pi){
//        $items = array();
//        foreach($array as $value){
//            $items[$value['id']] = $value;
//        }
//        $tree = array();
//        foreach($items as $key => $value){
//            if(isset($items[$item['pid']])){
//                $items[$item['pid']]['son'][] = &$items[$key];
//            }else{
//                $tree[] = &$items[$key];
//            }
//        } return $tree;
//    }
    public function addClassify(AnnouncementClassifyUpdateRequest $request)
    {
        $payload = $request->all();
        DB::beginTransaction();
        try {
            $name = AnnouncementClassify::where('name',$payload['name'])->get()->toArray();

            if(!$name){
                $Classify = AnnouncementClassify::create($payload);
            }else{
                $Classify = null;
            }

        }catch (\Exception $e) {
            DB::rollBack();
            Log::error($e);
            return $this->response->errorInternal('创建失败');
        }
        DB::commit();

        if($Classify != null){
            return $this->response->item($Classify, new AnnouncementClassifyTransformer());
        }else{
            return $this->response->errorInternal('数据有重复');
        }

    }
    public function deleteClassify(Request $request,AnnouncementClassify $announcementClassify)
    {
        DB::beginTransaction();
        try {
            $id = $announcementClassify->id;
            Announcement::where('classify',$id)->delete();
            $announcementClassify->delete();

        }catch (\Exception $e) {
            DB::rollBack();
            Log::error($e);
            return $this->response->errorInternal('删除失败');
        }
        DB::commit();
    }
    public function updateClassify(AnnouncementClassifyUpdateRequest $request,AnnouncementClassify $announcementClassify)
    {
        $payload = $request->all();
        DB::beginTransaction();
        try {
      $announcementClassify->update($payload);

        }catch (\Exception $e) {
            DB::rollBack();
            Log::error($e);
            return $this->response->errorInternal('修改失败');
        }
        DB::commit();
    }
    public function getClassify(Request $request)
    {
        $classify =AnnouncementClassify::where('id','>',0)->get();
        return $this->response->collection($classify,new AnnouncementClassifyTransformer());
    }
    public function show(Request $request,Announcement $announcement)
    {

        // 操作日志
        $operate = new OperateEntity([
            'obj' => $announcement,
            'title' => null,
            'start' => null,
            'end' => null,
            'method' => OperateLogMethod::LOOK,
        ]);
        event(new OperateLogEvent([
            $operate,
        ]));
        return $this->response->item($announcement, new AnnouncementTransformer());

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
                $scope = $payload['scope'];
                $len = count($scope);
                if($len >= 2){
                    $array = array();
                    foreach($scope as $key => $value){
                        $array['scope'][$key] = hashid_decode($value);
                    }
                    $payload['scope'] = $array['scope'];
                    $payload['scope'] = implode(',',$payload['scope']);
                }else{
                    $payload['scope'] = hashid_decode(array_values($payload['scope'])[0]);
                }
                $payload['classify'] = hashid_decode($payload['classify']);
            }
            DB::beginTransaction();
            try {
                $star = Announcement::create($payload);

                if ($request->has('affix')) {
                    foreach ($payload['affix'] as $affix) {
                        $this->affixRepository->addAffix($user, $star, $affix['title'], $affix['url'], $affix['size'], AffixType::DEFAULT);
                    }
                }

                foreach($scope as $key => $value){
                    $arr['announcement_id'] = $star->id;
                    $arr['department_id'] = hashid_decode($value);
                    $data = AnnouncementScope::create($arr);
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


        //公告创建成功发送消息

        try{
            $authorization = $request->header()['authorization'][0];
            event(new AnnouncementMessageEvent($star,AnnouncementTriggerPoint::CREATE,$authorization,$user));
        }catch (\Exception $exception){
            Log::error("创建公告消息发送失败[$star->title]");
            Log::error($exception);
        }

        return $this->response->item($star, new AnnouncementTransformer());

    }
    public function remove(Request $request,Announcement $announcement)
    {
        DB::beginTransaction();
        try {
            $user = Auth::guard('api')->user();
            if($user->id == $announcement->creator_id){
                $announcement->delete();
            }

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
            DB::rollBack();
            Log::error($e);
            return $this->response->errorInternal('删除失败');
        }
        DB::commit();

        //删除公告成功发送消息
        try{
            $authorization = $request->header()['authorization'][0];
            event(new AnnouncementMessageEvent($announcement,AnnouncementTriggerPoint::CREATE,$authorization,$user));
        }catch (\Exception $exception){
            Log::error("修改公告消息发送失败[$announcement->title]");

            Log::error($exception);
        }
    }
    public function edit(AnnouncementUpdateRequest $request, Announcement $announcement)
    {
        $payload = $request->all();
        $user = Auth::guard('api')->user();
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
        if ($request->has('accessory_name')) {
            $array['accessory_name'] = $payload['accessory_name'];
            if ($array['accessory_name'] != $announcement->accessory_name) {
                $operateNickname = new OperateEntity([
                    'obj' => $announcement,
                    'title' => '是否制顶',
                    'start' => $announcement->accessory_name,
                    'end' => $array['accessory_name'],
                    'method' => OperateLogMethod::UPDATE,
                ]);
                $arrayOperateLog[] = $operateNickname;
            } else {
                unset($array['accessory_name']);
            }
        }
        DB::beginTransaction();
        try {

            if ($request->has('affix')) {
                foreach ($payload['affix'] as $affix) {
                    $this->affixRepository->addAffix($user, $announcement, $affix['title'], $affix['url'], $affix['size'], AffixType::DEFAULT);
                }
            }
//            if (count($array) == 0)
//                return $this->response->noContent();
            $announcement->update($array);
            if(!empty($scope)){
               $announdelete =  AnnouncementScope::where('announcement_id',$announcement->id)->delete();
                if($announdelete){
            foreach($scope as $key => $value){
                $arr['announcement_id'] = $announcement->id;
                $arr['department_id'] = hashid_decode($value);
                 AnnouncementScope::create($arr);
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

        //修改公告成功发送消息

        try{
            $authorization = $request->header()['authorization'][0];
            event(new AnnouncementMessageEvent($announcement,AnnouncementTriggerPoint::CREATE,$authorization,$user));
        }catch (\Exception $exception){
            Log::error("修改公告消息发送失败[$announcement->title]");
            Log::error($exception);
        }

        return $this->response->accepted();

    }
    public function departmentsLists(Request $request)
    {
        $department = Department::get();
        foreach ($department as $key => $val)
        {
            $val['id'] = hashid_encode($val['id']);
        }
       // dd($this->response->item($department, new DepartmentTransformer()));

        return $department;
    }
    public function editReadflag(Request $request, Announcement $announcement)
    {
        $payload = $request->all();
        $user = Auth::guard('api')->user();
        $array = [];
        $arrayOperateLog = [];
        if ($request->has('readflag')) {

            $array['readflag'] = $payload['readflag'];
            if ($array['readflag'] != empty($announcement->look)? 0 :1) {

            } else {
                unset($array['readflag']);
            }
        }
        DB::beginTransaction();
        try {
            if($array['readflag'] ==  0){
                $announcement->look->update(['status' => 2]);
                $operate = new OperateEntity([
                    'obj' => $announcement,
                    'title' => '已读状态',
                    'start' => 1,
                    'end' => $array['readflag'],
                    'method' => OperateLogMethod::UPDATE,
                ]);
                event(new OperateLogEvent([
                    $operate,
                ]));
            }
            if($array['readflag'] ==  1){
                // 操作日志
                $operate = new OperateEntity([
                    'obj' => $announcement,
                    'title' => null,
                    'start' => null,
                    'end' => null,
                    'method' => OperateLogMethod::LOOK,
                ]);
                event(new OperateLogEvent([
                    $operate,
                ]));
            }
            // 操作日志
          //  event(new OperateLogEvent($arrayOperateLog));
        } catch (Exception $e) {
//            dd($e);
            DB::rollBack();
            Log::error($e);
            return $this->response->errorInternal('修改失败');
        }
        DB::commit();

        return $this->response->accepted();



    }
}