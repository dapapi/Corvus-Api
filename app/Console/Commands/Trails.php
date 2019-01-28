<?php

namespace App\Console\Commands;

use App\Events\TaskMessageEvent;
use App\Models\Trail;
use App\Repositories\HttpRepository;
use App\TriggerPoint\TrailTrigreePoint;
use App\User;
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
    private $params = [
        'token_type' => 'bearer',
        "username"=>"李乐",
        "password"=>123456,
        "grant_type"    =>  "password",
        "client_id" =>2,
        "client_secret"     =>  "B7l68XEz38cHE8VqTZPzyYnSBgo17eaCRyuLtpul",
        "scope" =>  "*"
    ];
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

        //获取今天时间
        $dataDay = date('YmdHi');//当前时间
        $Log = "加入公海池".$dataDay."\n";
        Log::info($Log);


        //获取今天时间
        $dataDay = date('YmdHi');//当前时间
        $trails = DB::table('trails')//
        ->join('projects', function ($join) {
            $join->on('projects.trail_id', '=', 'trails.id');
        })
            ->join('approval_form_business as afb', function ($join) {
                $join->on('afb.form_instance_number', '=', 'projects.project_number');
            })
            ->where('afb.form_status', 231)->where('take_type',null)->where('pool_type',null)
            ->select('*')->get()->toArray();

        $receive = ['receive'=>1];
        foreach ($trails as $value){
            //查询跟进时间
            $operateInfo = DB::table("operate_logs")->select('created_at')->where('logable_id',$value->id)->where('method',4)->orderBy('created_at','desc')->limit(1)->get()->toArray();
            if(!empty($operateInfo)){
                $created_at = $operateInfo[0]->created_at;
                //创建时间+14天 提醒
                $created = date('YmdHi',strtotime("$created_at +1 day"));//跟进时间
            }else{
                $created = date('YmdHi',strtotime("$value->created_at +1 day"));//创建时间
            }

            //创建时间+15天 入公海池
            $created1 = date('YmdHi',strtotime("$value->created_at"));//入公海池时间
            //创建时间大于等于当前时间
            if($created <= $dataDay){
                if($value->receive!==1){
                    $num = DB::table('trails')->where('id',$value->id)->update($receive);
                    //提醒
                    $trails = Trail::find($value['id']);
                    $user = User::find(11);
                    $meta['created'] = $created;//跟进时间
                    event(new TaskMessageEvent($trails,TrailTrigreePoint::REMIND_TRAIL_TO_SEAS,$authorization,$user,$meta));
                }
            }
            if($created1 <= $dataDay){
                if($value->type ==4 ){
                    $type = 3;
                }else{
                    $type = $value->type;
                }
//                        $operateInfo = DB::table("operate_logs")->where('logable_id',$value->id)->where('method',4)->get()->toArray();
//
//                        if(empty($operateInfo)){

                $array = ['receive'=>1,'pool_type'=>$type,'principal_id'=>'','take_type'=>1];
                $num = DB::table('trails')->where('id',$value->id)->update($array);
//                        }

            }

        }


    }
}
