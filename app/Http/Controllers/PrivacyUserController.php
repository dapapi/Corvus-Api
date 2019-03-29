<?php

namespace App\Http\Controllers;

use App\Http\Requests\PrivacyUserStoreRequest;
use App\Models\Blogger;
use App\Models\PrivacyUser;
use App\Models\Project;
use Carbon\Carbon;
use Illuminate\Http\Request;
use App\ModuleableType;
use App\Repositories\PrivacyUserRepository;
use App\Http\Transformers\PrivacyUserTransformer;
use App\Models\OperateEntity;
use App\OperateLogMethod;
use Exception;
use App\Events\OperateLogEvent;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
class privacyUserController extends Controller
{
    protected $privacyUserRepository;

    public function __construct(PrivacyUserRepository $privacyUserRepository)
    {
        $this->privacyUserRepository = $privacyUserRepository;
    }
    public function detail(Request $request)
    {
        $payload = $request->all();
        $user = Auth::guard('api')->user();

        if($request->has('project_id')){
            $array['moduleable_id'] = hashid_decode($payload['project_id']);
            $array['moduleable_type'] = ModuleableType::PROJECT;
        }
        if($request->has('blogger_id')){
            $array['moduleable_id'] = hashid_decode($payload['blogger_id']);
            $array['moduleable_type'] = ModuleableType::BLOGGER;
        }
        if($request->has('star_id')){
            $array['moduleable_id'] = hashid_decode($payload['star_id']);
            $array['moduleable_type'] = ModuleableType::STAR;
        }
        $privacyuser = $this->privacyUserRepository->getPrivacy($array,$request,$payload);
        foreach ($privacyuser as $key => $val){

                if($val->moduleable_field =='hatch_end_at'){
                   unset($privacyuser[$key]);
                }

        }
        return $this->response->collection($privacyuser, new PrivacyUserTransformer(false));

    }
    public function edit(Request $request,$model)
    {
        $payload = $request->all();
        $user = Auth::guard('api')->user();
        $array['user_id'] = $user->id;
        if ($model instanceof Blogger && $model->id) {
            $array['moduleable_id'] = $model->id;
            $array['moduleable_type'] = ModuleableType::BLOGGER;
            $thisnull = $this->privacyUserRepository->is_creator($array,$model);
            if(!$thisnull) {
                return $this->response->errorForbidden("你不能修改");
            }

        }else if($model instanceof Project && $model->id){
            $array['moduleable_id'] = $model->id;
            $array['moduleable_type'] = ModuleableType::PROJECT;
            $thisnull = $this->privacyUserRepository->is_creator($array,$model);
            if(!$thisnull) {
                return $this->response->errorForbidden("你不能修改");
            }
        }else if($model instanceof Star && $model->id){
            $array['moduleable_id'] = $model->id;
            $array['moduleable_type'] = ModuleableType::Star;
            $thisnull = $this->privacyUserRepository->is_creator($array,$model);
            if(!$thisnull) {
                return $this->response->errorForbidden("你不能修改");
            }
        }
        unset( $array['user_id']);
        DB::beginTransaction();
        try {
            $this->privacyUserRepository->updatePrivacy($array,$request,$payload);
//           // 操作日志
//            $operate = new OperateEntity([
//                'obj' =>  $blogger,
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
            return $this->response->errorInternal('创建失败');
        }
        DB::commit();

    }
    public function store(Request $request,$model)
    {
        $moduleable_type = $model->getMorphClass();
        $moduleable_id = $model->id;
        $payload = $request->all();
        unset($payload['_url']);
        $data = [];
        $user = Auth::guard('api')->user();
        $thisnull = $this->privacyUserRepository->is_creator(["user_id"=>$user->id],$model);
        if(!$thisnull) {
            return $this->response->errorForbidden("你不能添加");
        }
//        $data[] = ['moduleable_id' => $moduleable_id,"moduleable_type"=>$moduleable_type,"user_id"=>$user->id];//将创建人加入
        foreach ($payload as $moduleable_field => $users){
            $users = array_unique($users);
            foreach ($users as $user_id){
                $user_id = hashid_decode($user_id);
                $data[] = [
                    'moduleable_id' => $moduleable_id,
                    "moduleable_type"=>$moduleable_type,
                    "user_id"=>$user_id,
                    "moduleable_field"=>$moduleable_field,
                    "created_at"    =>  Carbon::now()->toDateTimeString(),
                ];

            }
        }
        DB::beginTransaction();
        try{
            PrivacyUser::where('moduleable_type',$moduleable_type)->delete();//删除所有的关于该模型的数据
            DB::table("privacy_users")->insert($data);
            DB::commit();
            return $this->response()->created();
        }catch (Exception $exception){
            Log::error($exception);
            DB::rollBack();
            return $this->response()->errorInternal("创建失败");
        }



//        $user = Auth::guard('api')->user();
//        $array['user_id'] = $user->id;
//        if ($model instanceof Blogger && $model->id) {
//            $array['moduleable_id'] = $model->id;
//            $array['moduleable_type'] = ModuleableType::BLOGGER;
//            $thisnull = $this->privacyUserRepository->is_creator($array,$model);
//            if(!$thisnull) {
//                return $this->response->errorForbidden("你不能添加");
//            }
//
//        }else if($model instanceof Project && $model->id){
//            $array['moduleable_id'] = $model->id;
//            $array['moduleable_type'] = ModuleableType::PROJECT;
//            $thisnull = $this->privacyUserRepository->is_creator($array,$model);
//            if(!$thisnull) {
//               return $this->response->errorForbidden("你不能添加");
//            }
//        }else if($model instanceof Star && $model->id){
//            $array['moduleable_id'] = $model->id;
//            $array['moduleable_type'] = ModuleableType::Star;
//            $thisnull = $this->privacyUserRepository->is_creator($array,$model);
//            if(!$thisnull) {
//                return $this->response->errorForbidden("你不能添加");
//            }
//        }
//        DB::beginTransaction();
//        try {
//            $this->privacyUserRepository->addPrivacy($array,$request,$payload);
//           // 操作日志
//            $operate = new OperateEntity([
//                'obj' =>  $model,
//                'title' => null,
//                'start' => null,
//                'end' => null,
//                'method' => OperateLogMethod::ADD_PRIVACY,
//            ]);
//            event(new OperateLogEvent([
//                $operate,
//            ]));
//        } catch (Exception $e) {
//            DB::rollBack();
//            Log::error($e);
//            return $this->response->errorInternal('创建失败');
//        }
//        DB::commit();

    }






}
