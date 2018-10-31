<?php

namespace App\Http\Controllers;

use App\Events\OperateLogEvent;
use App\Models\OperateEntity;
use App\Models\Task;
use App\OperateLogLevel;
use App\OperateLogMethod;
use App\User;
use Carbon\Carbon;
use Illuminate\Http\Request;

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
        $user = User::where(self::NAME, 'wyjson')->first();
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

        $operate = new OperateEntity([
            'obj' => $task,
            'title' => '描述',
            'start' => 'hah',
            'end' => '123',
            'method' => OperateLogMethod::UPDATE,
            'level' => OperateLogLevel::MIDDLE
        ]);

        event(new OperateLogEvent([
            $operate,
        ]));
        return $this->response->array([
            'success' => true,
            'message' => 'hello operate log!'
        ]);
    }

}
