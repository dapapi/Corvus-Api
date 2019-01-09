<?php

namespace App\Http\Controllers;

use App\Models\Blogger;
use App\Models\Project;
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
        $privacyuser = $this->privacyUserRepository->getPrivacy($array,$request,$payload);
        return $this->response->item($privacyuser, new PrivacyUserTransformer());

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
//            if(!$thisnull) {
//                return $this->response->errorForbidden("你不能修改");
//            }

        }else if($model instanceof Project && $model->id){
            $array['moduleable_id'] = $model->id;
            $array['moduleable_type'] = ModuleableType::PROJECT;
            $thisnull = $this->privacyUserRepository->is_creator($array,$model);
//            if(!$thisnull) {
//                return $this->response->errorForbidden("你不能修改");
//            }
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
        $payload = $request->all();
        $user = Auth::guard('api')->user();
        $array['user_id'] = $user->id;
        if ($model instanceof Blogger && $model->id) {
            $array['moduleable_id'] = $model->id;
            $array['moduleable_type'] = ModuleableType::BLOGGER;
            $thisnull = $this->privacyUserRepository->is_creator($array,$model);
            if(!$thisnull) {
                return $this->response->errorForbidden("你不能添加");
            }

        }else if($model instanceof Project && $model->id){
            $array['moduleable_id'] = $model->id;
            $array['moduleable_type'] = ModuleableType::PROJECT;
//            $thisnull = $this->privacyUserRepository->is_creator($array,$model);
//            if(!$thisnull) {
//               return $this->response->errorForbidden("你不能添加");
//            }
        }
        DB::beginTransaction();
        try {
            $this->privacyUserRepository->addPrivacy($array,$request,$payload);
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








}