<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;



class Project extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'Project';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Project description';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        $dataDay = date('Y-m-d H:i:s');//当前时间
//        $Log = "延期脚本执行".$dataDay."\n";
//        Log::info($Log);
        //获取今天时间
        $dataDay = date('YmdHis');//当前时间
        $tasks = DB::table("tasks")->where('status',1)->get()->toArray();
        $data = json_decode(json_encode($tasks), true);
        foreach ($data as $value){
            $created = date('YmdHis',strtotime($value['end_at']));//截止时间
            if($created<=$dataDay){
                $snum = DB::table('tasks')
                    ->where('id',$value['id'])
                    ->update(['status'=>4]);
                $Log = "延期Id".$value['id']."\n";
//                Log::info($Log);
            }

        }
    }
}
