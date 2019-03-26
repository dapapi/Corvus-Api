<?php

namespace App\Http\Controllers;

use App\Events\OperateLogEvent;
use App\Helper\Common;
use App\Models\OperateEntity;
use App\Models\Task;
use App\OperateLogMethod;
use App\User;
use Carbon\Carbon;

class TestController extends Controller
{
    const NAME = 'name';

    public function hello()
    {
        return $this->response->array([
            'success' => true,
            'message' => 'hello Corvus CRM'
        ]);
    }

    public function signin()
    {
        $user = User::where(self::NAME, 'cxy')->first();
        $token = $user->createToken('web api')->accessToken;

        return $this->response->array(['token_type' => 'Bearer', 'access_token' => $token]);
        
    }

    public function testArray()
    {
        $ids = [1, 2, 2, 3, 4];
        $ids = array_unique($ids);//去重
        dd($ids);
        foreach ($ids as $key => &$id) {
            if ($id == 2) {
                array_splice($ids, $key, 1);
            } else {
                $id = hashid_encode($id);
            }
        }
        unset($id);
        dd($ids);
    }

    public function date()
    {
        $now = Carbon::now();
        dd($now->toDateTimeString());
    }

    public function operateLog()
    {
        $task = Task::find(1);
        //修改
        $operate = new OperateEntity([
            'obj' => $task,
            'title' => '描述',
            'start' => '这个项目大家都关注一下啊',
            'end' => '项目开始了',
            'method' => OperateLogMethod::UPDATE,
        ]);
        //创建任务
        $operate = new OperateEntity([
            'obj' => $task,
            'title' => null,
            'start' => null,
            'end' => null,
            'method' => OperateLogMethod::CREATE,
        ]);

        event(new OperateLogEvent([
            $operate,
        ]));
        return $this->response->array([
            'success' => true,
            'message' => 'hello operate log!'
        ]);
    }

    public function arrayIf()
    {
        $participantIds = ['1'];
        if (count($participantIds)) {
            dd('ok');
        }
        dd('no');
    }

    public function department()
    {
        $arr = Common::getChildDepartment(149);
        return $this->response->array($arr);
    }

    public function pdepartment()
    {
        $id = Common::getDepartmentPrincipal(255, 1);
        dd(1);
    }

}
