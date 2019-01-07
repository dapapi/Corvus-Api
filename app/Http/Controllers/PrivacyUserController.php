<?php

namespace App\Http\Controllers;

use App\Models\Blogger;
use App\Models\Project;
use Illuminate\Http\Request;
use App\ModuleableType;
use App\Repositories\PrivacyUserRepository;
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

    public function store(Request $request,$model)
    {
        $payload = $request->all();
        $user = Auth::guard('api')->user();
        $array['user_id'] = $user->id;
        if ($model instanceof Blogger && $model->id) {
            $array['moduleable_id'] = $model->id;
            $array['moduleable_type'] = ModuleableType::BLOGGER;
            $this->privacyUserRepository->is_creator($array,$model);
        }else if($model instanceof Project && $model->id){
            $array['moduleable_id'] = $model->id;
            $array['moduleable_type'] = ModuleableType::PROJECT;
            $this->privacyUserRepository->is_creator($array,$model);
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
