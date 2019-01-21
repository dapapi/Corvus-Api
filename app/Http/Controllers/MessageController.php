<?php

namespace App\Http\Controllers;

use App\Http\Transformers\MessageTransform;
use App\Models\DataDictionarie;
use App\Models\Message;
use App\Models\MessageState;
use App\ModuleableType;
use App\Repositories\MessageRepository;
use Carbon\Carbon;
use function GuzzleHttp\Psr7\copy_to_string;
use Illuminate\Http\Request;
use Illuminate\Routing\Route;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;

class MessageController extends Controller
{
    public function index(Request $request)
    {
        $module = $request->get('module',null);
        $state = $request->get('state',null);
        $arr = [];
        if($module != null){
            $arr[] = ['m.module', $module];
        }
        if($state != null){
            $arr[]  =   ['ms.state',$state];
        }
        $user = Auth::guard('api')->user();
        $arr[] = ['ms.user_id',$user->id];
        DB::connection()->enableQueryLog();
        $result = (new Message())->setTable("m")->from('messages as m')
            ->leftJoin('message_states as ms','ms.message_id','m.id')
            ->leftJoin('message_datas as md','md.message_id','ms.message_id')
            ->orderBy('m.created_at','desc')
            ->select(
                'm.id','m.module','m.title as message_title','m.link','m.created_at','m.subheading',
                'ms.user_id','md.title','md.value','ms.state'
                )
            ->where($arr)
            ->get();
        $sql = DB::getQueryLog();
        $list = [];
        $no_read = 0;//未读消息数量
        foreach ($result->toArray() as $value){
            $value['id']    =   hashid_encode($value['id']);
            $value['created'] = Carbon::parse($value['created_at'])->format('Y-m-d');
            if(!isset($list[$value['created']])){
                if($value['state'] == MessageState::UN_READ){
                    $no_read++;
                }
                $list[$value['created']][] = [
                    'message_id' => $value['id'],
                    'message_title'=> $value['message_title'],
                    'message_subheading'    =>  $value['subheading'],
                    'link'=> $value['link'],
                    'created_at' => $value['created_at'],
                    'created' => Carbon::parse($value['created_at'])->format('Y-m-d'),
                    'dayofweek' => Carbon::parse($value['created_at'])->dayOfWeek,
                    'module'   =>   $value['module'],
                    'state' =>  $value['state'],
                    'body'=>[['title'=>$value['title'],'value'=>$value['value']]],
                ];
            }else{
                $message_key = array_search($value['id'],array_column($list[$value['created']],'message_id'));
                if($message_key === false){
                    if($value['state'] == MessageState::UN_READ){
                        $no_read++;
                    }
                    $list[$value['created']][] = [
                        'message_id' => $value['id'],
                        'message_title'=> $value['message_title'],
                        'message_subheading'    =>  $value['subheading'],
                        'link'=> $value['link'],
                        'created_at' => $value['created_at'],
                        'created' => Carbon::parse($value['created_at'])->format('Y-m-d'),
                        'dayofweek' => Carbon::parse($value['created_at'])->dayOfWeek,
                        'module'   =>   $value['module'],
                        'state' =>  $value['state'],
                        'body'=>[['title'=>$value['title'],'value'=>$value['value']]],
                    ];
                }else{
                    $list[$value['created']][$message_key]['body'][] = ['title'=>$value['title'],'value'=>$value['value']];
                }

            }

        }
        return [
            'no_read'=>$no_read,
            'data'=>$list
        ];
    }
    //设置已读未读状态
    public function changeSate(Request $request){
        $message_id = $request->get('message_id',null);
        $all_read = $request->get('all','no');
        $module = $request->get("module",null);
        $user = Auth::guard('api')->user();
//        (new Message())->where('module',$module)->recive()->where('user_id',$user->id) ->update(['ms.state'=>MessageState::HAS_READ]);
        if($all_read=="yes" && $module != null && is_numeric($module)){
            $message = new Message();
            $message->timestamps = false;//禁用时间戳自动维护
            $message->setTable("m")->from("messages as m")
                ->leftJoin("message_states as ms",'ms.message_id','m.id')
                ->where([['ms.user_id',$user->id],['m.module',$module],['ms.state',MessageState::UN_READ]])
                ->update(['ms.state'=>MessageState::HAS_READ,"ms.updated_at"=>Carbon::now(),"updated_by"=>$user->name]);
        }
        if($message_id != null && $all_read == "no"){
            try{
                MessageState::where(['message_id' => hashid_decode($message_id),'user_id'=>$user->id])->update(['state'=>MessageState::HAS_READ,'updated_by'=>$user->name]);
            }catch (\Exception $e){
                $this->response()->errorInternal("修改失败");
            }
        }

        return $this->response()->noContent();
    }
    public function getModules(Request $request)
    {
        $user = Auth::guard('api')->user();
//        //获取消息模块
//        $subquery = DB::table(DB::raw('message_states as ms'))
//            ->leftJoin('messages as m',function ($join){
//                $join->on('m.id','ms.message_id')
//                    ->where('ms.state',MessageState::UN_READ);
//            })
//            ->where('ms.user_id',$user->id)
//            ->select("m.id",'m.module',DB::raw("count('DISTINCT ms.message_id') as un_read"),"m.title","m.created_at","ms.state")
//            ->orderBy("m.created_at","desc")
//            ->groupBy('m.module');
//
//        $result = (new DataDictionarie())->setTable('dd')->from('data_dictionaries as dd')
//            ->leftJoin(DB::raw("({$subquery->toSql()}) as m"),'m.module','dd.id')
//            ->mergeBindings($subquery)
//            ->where('dd.parent_id',206)
//            ->orderBy("m.created_at","desc")
//            ->groupBy('dd.id')
//            ->get(['dd.id','dd.val','dd.name','m.un_read',"m.title","m.created_at","m.state","m.id as m_id"]);
//        return $result;
        $messageRepository = new MessageRepository();
        $modules = $messageRepository->getModules();
        foreach ($modules as &$module){
            //获取某块对应的用户未读消息
            $un_read = $messageRepository->getUnMessageNum($user->id,$module['id']);
            //获取模块对应的用户最新消息
            $lastMessage = $messageRepository->getLastNewsByModule($module['id'],$user->id);
            $module['unread'] = $un_read;
            $module['laset_mesage'] = $lastMessage;
        }
        return $modules;
    }

    public function MobileGetMessage(Request $request)
    {
        $user = Auth::guard('api')->user();
        $message = DB::table("data_dictionaries as dd")
            ->leftJoin("messages as m", 'm.module', 'dd.id')
            ->leftJoin("message_datas as md", 'md.message_id', "m.id")
            ->leftJoin("message_states as ms", 'ms.message_id', 'm.id')
            ->where('parent_id', 206)
            ->where('ms.user_id', $user->id)
            ->select("dd.name as module_name", "m.id as message_id", "m.title as message_title", "m.link", "m.created_at", "md.title", "md.value", "ms.state")
            ->get()->toArray();
        $res = [];
        return $message;
//        return [
//            [
//                'module_name'   =>  '任务助手',
//                'message'   =>  [
//                    [
//                        'message_id'    => 12345,
//                        "link"  =>  'http://xxx.com/ccc',
//                        "created_at"    =>  "2019-01-19 14:10:48",
//                        "message_title" =>  '哈哈哈',
//                        "body" => [
//                                    [
//                                        "title"=> "任务名称",
//                                        "value"=>"一个任务1"
//                                    ],
//                                    [
//                                        "title"=>"负责人",
//                                        "value"=>"校林峰",
//                                    ]
//                                ]
//                    ],
//                    [
//                        'message_id'    => 12346,
//                        "link"  =>  'http://xxx.com/ccc',
//                        "created_at"    =>  "2019-01-19 14:10:48",
//                        "message_title" =>  "哈哈哈哈",
//                        "body" => [
//                            [
//                                "title"=> "任务名称",
//                                "value"=>"一个任务1"
//                            ],
//                            [
//                                "title"=>"负责人",
//                                "value"=>"校林峰",
//                            ]
//                        ]
//                    ],
//
//                ]
//            ],
//            [
//                'module_name'   =>  '艺人助手',
//                'message'   =>  [
//                    [
//                        'message_id'    => 12345,
//                        "link"  =>  'http://xxx.com/ccc',
//                        "created_at"    =>  "2019-01-19 14:10:48",
//                        "message_title" =>  '哈哈哈',
//                        "body" => [
//                            [
//                                "title"=> "任务名称",
//                                "value"=>"一个任务1"
//                            ],
//                            [
//                                "title"=>"负责人",
//                                "value"=>"校林峰",
//                            ]
//                        ]
//                    ],
//                    [
//                        'message_id'    => 12346,
//                        "link"  =>  'http://xxx.com/ccc',
//                        "created_at"    =>  "2019-01-19 14:10:48",
//                        "message_title" =>  "哈哈哈哈",
//                        "body" => [
//                            [
//                                "title"=> "任务名称",
//                                "value"=>"一个任务1"
//                            ],
//                            [
//                                "title"=>"负责人",
//                                "value"=>"校林峰",
//                            ]
//                        ]
//                    ],
//
//                ]
//            ],
//
//        ];
    }

}
