<?php

namespace App\Console\Commands;

use App\Events\CalendarMessageEvent;
use App\Models\Schedule;
use App\Repositories\HttpRepository;
use App\Repositories\MessageRepository;
use App\TriggerPoint\CalendarTriggerPoint;
use App\User;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ScheduleRemind extends Command
{

    private $httpRepository;
    private $header = [
        "Accept"=>"application/vnd.Corvus.v1+json",
        "Content-Type"  =>  "application/x-www-form-urlencoded"
    ];
    private $params;
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'schedule:remind';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = '日程提醒任务';
    private $messageRepository;

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct(HttpRepository $httpRepository)
    {
        parent::__construct();
        $this->httpRepository = $httpRepository;
        $this->params = [
            'token_type' => 'bearer',
            "username"=>config("app.schdule_user_name","李乐"),
            "password"=>config("app.schdule_password","123456"),
            "grant_type"    =>  "password",
            "client_id" =>2,
            "client_secret"     =>  "B7l68XEz38cHE8VqTZPzyYnSBgo17eaCRyuLtpul",
            "scope" =>  "*"
        ];
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        try{
            $res = $this->httpRepository->request("post",'oauth/token',$this->header,$this->params);
            if (!$res){
                echo "登录失败";
                Log::error("直客到期检测登录失败...");
                return;
            }
        }catch (\Exception $e){
            Log::error("定时任务。。。登录异常");
            Log::error($e);
        }
//        Log::info("系统用户登录成功");
        $body = $this->httpRepository->jar->getBody();
        $access_token = json_decode($body,true)['access_token'];
        $authorization = "Bearer ".$access_token;
        $now = Carbon::now();
        //查询开始时间大于当前时间的日程
        $schdules = Schedule::where("start_at",'>',$now->toDateTimeString())->select('remind','id','title','creator_id','start_at')->get();
        foreach ($schdules->toArray() as $schdule){
            $remid_time = Carbon::createFromTimeString($schdule['start_at']);
            $flag = false; //是否发消息标志
            switch ($schdule['remind']){
                case Schedule::REMIND_CURR://日程发生时
                    if ($remid_time->diffInMinutes($now) == 0){
                        $flag = true;
                    }
                    break;
                case Schedule::REMIND_FIVE_MINUTES://5分钟前
                    if ($remid_time->diffInMinutes($now) == 5){
                        $flag = true;
                    }
                    break;
                case Schedule::REMIND_TEN_MINUTES://10分钟前
                    if ($remid_time->diffInMinutes($now) == 10){
                        $flag = true;
                    }
                    break;
                case Schedule::REMIND_THIRTY_MINUTES://30分钟前
                    if ($remid_time->diffInMinutes($now) == 30){
                        $flag = true;
                    }
                    break;
                case Schedule::REMIND_ONE_HOURS://一小时前
                    if ($remid_time->diffInMinutes($now) == 60){
                        $flag = true;
                    }
                    break;
                case Schedule::REMIND_TWO_HOURS://两小时前
                    if ($remid_time->diffInMinutes($now) == 2*60){
                        $flag = true;
                    }
                    break;
                case Schedule::REMIND_ONE_DAY://一天前
                    if ($remid_time->diffInMinutes($now) == 1*24*60){
                        $flag = true;
                    }
                    break;
                case Schedule::REMIND_TWO_DAY://两天前
                    if ($remid_time->diffInMinutes($now) == 2*24*60){
                        $flag = true;
                    }
                    break;
            }
//            Log::info($flag);
//            dump($flag);
            if($flag){
                $user = User::find(config("app.schdule_user_id"));
                //发消息
                $schdule_obj = Schedule::find($schdule['id']);
                Log::info("发送日程提醒".$schdule_obj->title);
                event(new CalendarMessageEvent($schdule_obj,CalendarTriggerPoint::REMIND_SCHEDULE,$authorization,$user));
            }
        }
//        Log::info("日程提醒检测结束");

    }
}
