<?php

namespace App\Http\Controllers;

use App\Http\Transformers\DepartmentTransformer;
use App\Models\Department;
use App\Models\DepartmentUser;
use App\Models\OperateEntity;
use App\OperateLogMethod;
use App\Events\OperateLogEvent;


use App\User;
use Illuminate\Http\Request;

class DepartmentController extends Controller
{
    public function index(Request $request)
    {
        $depatments = Department::where('department_pid', 0)->get();
        return $this->response->collection($depatments, new DepartmentTransformer());
    }

    public function store(Request $request,Department $department,User $user,DepartmentUser $departmentUser)
    {
        $payload = $request->all();

        $user_id = $user->id;
        $payload['department_pid'] = $department->id;

        $array = [
            "department_id"=>$department->id,
            "user_id"=>$user_id,
            "type"=>Department::DEPARTMENT_HEAD_TYPE,
        ];
        $payload['department_pid'] = $department->id;

        try {
            $depar = DepartmentUser::create($array);
            $contact = Department::create($payload);
            // 操作日志
            $operate = new OperateEntity([
                'obj' => $department,
                'title' => null,
                'start' => null,
                'end' => null,
                'method' => OperateLogMethod::CREATE,
            ]);
            event(new OperateLogEvent([
                $operate,
            ]));

        } catch (\Exception $exception) {
            Log::error($exception);
            return $this->response->errorInternal('创建部门失败');
        }

        return $this->response->item($contact, new DepartmentTransformer());
    }
}
