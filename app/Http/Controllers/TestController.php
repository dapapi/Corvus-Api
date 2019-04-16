<?php

namespace App\Http\Controllers;

use App\Events\OperateLogEvent;
use App\Models\OperateEntity;
use App\Models\Task;
use App\OperateLogMethod;
use App\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\ICS;


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

    public function task()
    {
        $path = base_path();
        $a = '123';
        $path= $path.'/ics/'.$a.'.ics';    //此处可以使用变量名组成的字符串来动态创建文件

        if (!file_exists($path)){
            file_put_contents($path, '');
        }
        $data = DB::table('schedules')->select('title','materials.name','start_at','end_at','desc','remind')
            ->join('materials', function ($join) {
                $join->on('materials.id', '=', 'schedules.material_id');
            })->where('schedules.creator_id',9)->get()->toArray();
        $dataArr = json_decode(json_encode($data), true);

        $ics_props = array(
            'BEGIN:VCALENDAR'."\r\n",
            'VERSION:2.0'."\r\n",
            'PRODID:-//hacksw/handcal//NONSGML v1.0//EN'."\r\n",
            'CALSCALE:GREGORIAN'."\r\n"
            //'BEGIN:VEVENT'."\r\n"
        );
        $path = base_path();
        $filename = $path.'/ics/'."2.ics";
        $res = file_put_contents($filename,$ics_props,FILE_APPEND);

        foreach ($dataArr as $value){
            if($value['remind'] ==1){
                $remind = '';
            }elseif ($value['remind'] ==2){
                $remind = 'PT0S';
            }elseif ($value['remind'] ==3){
                $remind = '-PT5M';
            }elseif ($value['remind'] ==4){
                $remind = '-PT10M';
            }elseif ($value['remind'] ==5){
                $remind = '-PT15M';
            }elseif ($value['remind'] ==6){
                $remind = '-PT30M';
            }elseif ($value['remind'] ==7){
                $remind = '-PT1H';
            }elseif ($value['remind'] ==8){
                $remind = '-PT2H';
            }elseif ($value['remind'] ==9){
                $remind = '-P1D';
            }elseif ($value['remind'] ==10){
                $remind = '-P2D';
            }

            $ics = new ICSController( array(
                'location' => $value['name'],
                'description' => $value['desc'],
                'dtstart' => $value['start_at'],
                'dtend' => $value['end_at'],
                'summary' => $value['title'],
                'trigger' => $remind
            ));
             $ics->to_string();
        }

        $ics_props = array(
            'END:VCALENDAR'."\r\n"
        );
        $path = base_path();
        $filename = $path.'/ics/'."2.ics";
        $res = file_put_contents($filename,$ics_props,FILE_APPEND);


//        $ics = new ICSController(array(
//            'location' => 123,
//            'description' => 456,
//            'dtstart' => '2019-03-25 08:42:17',
//            'dtend' => '2019-03-25 08:42:17',
//            'summary' => 888,
//            'trigger' => '-P2D'
//        ));
//        echo $ics->to_string();
    }

}
