<?php

namespace App\Console\Commands;

use App\Events\ClientMessageEvent;
use App\Models\Client;
use App\Repositories\HttpRepository;
use App\TriggerPoint\ClientTriggerPoint;
use App\User;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class ClientProtected extends Command
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
    protected $signature = 'client:protected';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = '直客保护到期前5天提醒任务';

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
        Log::info("直客到期检查");
        try{
            $res = $this->httpRepository->request("post",'oauth/token',$this->header,$this->params);
            if (!$res){
                echo "登录失败";
                Log::error("直客到期检测登录失败...");
                return;
            }
        }catch (\Exception $e){
            Log::error("直客保护。。。登录异常");
            Log::error($e);
        }

        $body = $this->httpRepository->jar->getBody();
        $access_token = json_decode($body,true)['access_token'];
        $authorization = "Bearer ".$access_token;
        Log::info("系统用户登录成功");

        $now = Carbon::now();
        //获取保护截止日期在当前时间之后的直客
        $clients = Client::where('grade',Client::GRADE_NORMAL)->where('protected_client_time','>',$now->toDateTimeString())->get();
        foreach ($clients as $client){
            $protected_client_time = Carbon::createFromTimeString($client->protected_client_time);
            if ($protected_client_time->diffInMinutes($now) == 5*24*60){
                $user = User::find(config("app.schdule_user_id"));
                //发消息
                event(new ClientMessageEvent($client,ClientTriggerPoint::NORMAL_PROTECTED_EXPIRE,$authorization,$user));
            }
        }
    }
}
