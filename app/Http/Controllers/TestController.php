<?php

namespace App\Http\Controllers;

use App\Events\OperateLogEvent;
use App\Helper\Common;
use App\Jobs\ProjectImplode;
use App\Models\OperateEntity;
use App\Models\Project;
use App\Models\Star;
use App\Models\Task;
use App\ModuleableType;
use App\ModuleUserType;
use App\OperateLogMethod;
use App\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

use League\Fractal;
use League\Fractal\Manager;
use League\Fractal\Pagination\IlluminatePaginatorAdapter;

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
        foreach ($ids as $key => &$id) {
            if ($id == 2) {
                array_splice($ids, $key, 1);
            } else {
                $id = hashid_encode($id);
            }
        }
        unset($id);
    }

    public function date()
    {
        $now = Carbon::now();
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
        }
    }

    public function department()
    {
        $arr = Common::getChildDepartment(149);
        return $this->response->array($arr);
    }

    public function users(Request $request)
    {
        # 权限|我负责的|我参与的|自定义筛选|整理数据格式
        # 我参与的
        $query = Project::getConditionSql();
//        DB::table('module_users')->where('user_id', $id)->where('moduleable_type', ModuleableType::PROJECT)->pluck('moduleable_id')->toArray();
//        $user = Auth::guard('api')->user();

        $paginator = DB::table('project_implode')->select('*')->orWhereRaw(DB::raw("1 = 1 $query"))->paginate();
        $projects = $paginator->getCollection();
        $resource = new Fractal\Resource\Collection($projects, function ($item) {
            # 单独处理
            $stars = [];
            if ($item->stars) {

                $arr["stars"] = explode(',',$item->stars);
                $arr["star_ids"] = explode(',',$item->star_ids);
                foreach ($arr['stars'] as $key1 => $val1) {
                    $stars[] = [
                        'id' => hashid_encode($arr['star_ids'][$key1]),
                        'name' => $val1
                    ];
                }
            }
            $bloggers = [];
            if ($item->bloggers) {
                $arr["blogger_ids"] = explode(',',$item->blogger_ids);
                $arr["bloggers"] = explode(',', $item->bloggers);
                foreach ($arr['bloggers'] as $key2 => $val2) {
                    $bloggers[] = [
                        'id' => hashid_encode($arr['blogger_ids'][$key2]),
                        'name' => $val2
                    ];
                }
            }
            $expectations = array_merge($stars, $bloggers);
            return [
                "id" => hashid_encode($item->id),
                "title" => $item->project_name,
                "type" => $item->project_type,
                "priority" => $item->project_priority,
                "start_at" => $item->project_start_at,
                "end_at" => $item->project_end_at,
                "created_at" => $item->project_store_at,
                "status" => $item->project_status,
                "last_follow_up_at" => $item->last_follow_up_at,
                "last_updated_at" => $item->last_follow_up_at,
                "principal" => [
                    'data' => [
                        'id' => hashid_encode($item->principal_id),
                        "name" => $item->principal,
                        "department" => [
                            "id" => hashid_encode($item->department_id),
                            "name" => $item->department,
                        ]
                    ]
                ],
                "creator" => [
                    'data' => [
                        'id' => hashid_encode($item->creator_id),
                        "name" => $item->creator,
                    ]
                ],
                "trail" => [
                    "data" => [
                        "resource_type" => $item->resource_type,
                        "fee" => $item->trail_fee,
                        "cooperation_type" => $item->cooperation_type,
                        "status" => $item->trail_status,
                        "expectations" => [
                            "data" => $expectations
                        ],
                    ]
                ],
                "sign_at" => $item->sign_at,
                "launch_at" => $item->launch_at,
                "platforms" => $item->platforms,
                "show_type" => $item->show_type,
                "guest_type" => $item->guest_type,
                "record_at" => $item->record_at,
                "movie_type" => $item->movie_type,
                "theme" => $item->theme,
                "team_info" => $item->team_info,
                "follow_up" => $item->follow_up,
                "walk_through_at" => $item->walk_through_at,
                "walk_through_location" => $item->walk_through_location,
                "walk_through_feedback" => $item->walk_through_feedback,
                "follow_up_result" => $item->follow_up_result,
                "agreement_fee" => $item->agreement_fee,
                "multi_channel" => $item->multi_channel,

                "client" => $item->client,
                "last_follow_up_user_id" => $item->last_follow_up_user_id,
                "last_follow_up_user_name" => $item->last_follow_up_user_name,

                "projected_expenditure" => $item->projected_expenditure,
                "expenditure" => $item->expenditure,
                "revenue" => $item->revenue,
            ];
        });
        $data = $resource->setPaginator(new IlluminatePaginatorAdapter($paginator));
        $manager = new Manager();
        return response($manager->createData($data)->toArray());
    }

    public function test()
    {
        Project::orderBy('id')->chunk(10, function ($projects) {
            foreach ($projects as $project) {
                dispatch(new ProjectImplode($project));
            }
        });

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
