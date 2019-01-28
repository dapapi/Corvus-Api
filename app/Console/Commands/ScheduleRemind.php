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

        $res = $this->httpRepository->request("post",'oauth/token',$this->header,$this->params);
        if (!$res){
            echo "登录失败";
            Log::error("登录失败...");
            return;
        }
        $body = $this->httpRepository->jar->getBody();
        $access_token = json_decode($body,true)['access_token'];
        $authorization = "Bearer ".$access_token;
        $now = Carbon::now();
        echo $now->toDateTimeString();
        //日程提醒
        $schdules = Schedule::where("start_at",'>',$now->toDateTimeString())->select('remind','id','title','creator_id','start_at')->get();
        foreach ($schdules->toArray() as $schdule){
//            echo $schdule['title'];
            $remid_time = Carbon::createFromTimeString($schdule['start_at']);
            echo $schdule['title'].": ".$remid_time->diffInMinutes($now)."\n";
            $flag = false; //是否发消息标志
            echo $schdule['remind'];
            switch ($schdule['remind']){
                case Schedule::REMIND_CURR://日程发生时
                    if ($remid_time->diffInMinutes($now) == 0){
                        $flag = true;
                    }
                    break;
                case Schedule::REMIND_FIVE_MINUTES://5分钟前
                    if ($remid_time->diffInMinutes($now) == 5){
                        echo "asdad";
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
            if($flag){
                Log::info("发送消息");
                $user = User::find(config("app.schdule_user_id"));
                //发消息
                $schdule_obj = Schedule::find($schdule['id']);
                event(new CalendarMessageEvent($schdule_obj,CalendarTriggerPoint::REMIND_SCHEDULE,$authorization,$user));
            }
        }

    }
}
