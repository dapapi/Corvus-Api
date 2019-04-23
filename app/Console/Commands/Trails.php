<?php

namespace App\Console\Commands;

use App\Events\TaskMessageEvent;
use App\Events\TrailMessageEvent;
use App\Models\Trail;
use App\OperateLogMethod;
use App\Repositories\HttpRepository;
use App\TriggerPoint\TrailTrigreePoint;
use App\User;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;


class Trails extends Command
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
    protected $signature = 'Trails';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Trails description';

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
            Log::error("线索。。。登录异常");
            Log::error($e);
        }

//        Log::info("系统用户登录成功");
        $body = $this->httpRepository->jar->getBody();
        $access_token = json_decode($body,true)['access_token'];
        $authorization = "Bearer ".$access_token;

            //获取今天时间
            $dataDay = date('YmdHi');//当前时间
            $Log = "加入公海池".$dataDay."\n";
            Log::info($Log);
        $now = Carbon::now();

        //获取今天时间
//        $dataDay = date('YmdHi');//当前时间
//        $trails = DB::table('trails')
        //对线索进行查询
        $trails = (new Trail())->setTable("trails")->from("trails")
        ->join('projects', function ($join) {
            $join->on('projects.trail_id', '=', 'trails.id');
        })
            ->join('approval_form_business as afb', function ($join) {
                $join->on('afb.form_instance_number', '=', 'projects.project_number');
            })
            ->where('afb.form_status', "<>",231)
            ->where('trails.progress_status',Trail::STATUS_UNCONFIRMED)
            ->where('take_type',null)->where('pool_type',null)
            ->select('trails.id','trails.created_at','trails.type','trails.title','trails.principal_id','afb.form_status')->get();
        $receive = ['receive'=>1];
        foreach ($trails as $value) {
//            Log::info("检测线索【".$value->title."】");
            //查询跟进时间
            $fllow_update_at = DB::table("operate_logs")
                ->where('logable_id', $value->id)
                ->where('method', OperateLogMethod::FOLLOW_UP)
                ->orderBy('created_at', 'desc')
                ->limit(1)
                ->value("created_at");
            //最后跟进时间
            $last_update_at = null;
//            Log::info($fllow_update_at);

            if ($fllow_update_at) {
                $last_update_at = Carbon::createFromTimeString($fllow_update_at);
            } else {
                $last_update_at = Carbon::createFromTimeString($value->created_at);
            }
//            Log::info($last_update_at);
//            Log::info($now->diffInMinutes($last_update_at));
            //进入公海池前一天提醒
            if ($now->diffInMinutes($last_update_at) >= 16 * 24 * 60) { #进入公海池前一天提醒
                if ($value->receive !== 1) {
                    $num = DB::table('trails')->where('id', $value->id)->update($receive);
                    Log::info("发送线索即将进入公海池提醒,线索【" . $value->title."】将要进入公海池");
                    //提醒
                    $user = User::find(config("app.schdule_user_id"));
                    $meta['created'] = $last_update_at->toDateTimeString();//跟进时间
                    try{
                        event(new TrailMessageEvent($value, TrailTrigreePoint::REMIND_TRAIL_TO_SEAS, $authorization, $user, $meta));
                    }catch (\Exception $exception){
                        dump("消息发送失败");
                        Log::error("线索【{$value->title}】进入公海池,消息发送失败");
                    }
                }
            }

            if ($now->diffInMinutes($last_update_at) >= 15 * 24 * 60) {
//                Log::info("线索进入公海池【".$value->title."】");
                if ($value->type == 4) {
                    $type = 3;
                } else {
                    $type = $value->type;
                }
//                        $operateInfo = DB::table("operate_logs")->where('logable_id',$value->id)->where('method',4)->get()->toArray();
//
//                        if(empty($operateInfo)){

                $array = ['receive' => 1, 'pool_type' => $type, 'principal_id' => '', 'take_type' => 1];
                $num = DB::table('trails')->where('id', $value->id)->update($array);
//                        }

            }

        }
//        Log::info("线索执行结束");


    }
}
