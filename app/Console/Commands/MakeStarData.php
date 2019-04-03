<?php

namespace App\Console\Commands;

use App\Models\Star;
use App\OperateLogMethod;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class MakeStarData extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'make:stardata';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = '生成艺人数据';

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

        //查找博主参与人并补充完整
        $bloggers = Star::chunk(10,function($stars){
            foreach ($stars as $star){
                $last_updated_user = $star->operateLogs()->where('method', OperateLogMethod::UPDATE)->orderBy('operate_logs.created_at', 'desc')->first();
                $last_follow_up_user = $star->operateLogs()->where('method', OperateLogMethod::FOLLOW_UP)->orderBy('created_at', 'desc')->first();
                $data = [
                    'last_updated_user_id'    =>  $last_updated_user ? $last_updated_user->user->id : null,
                    'last_updated_at'   =>  $star->last_updated_at,
                    'last_follow_up_at' =>  $star->last_follow_up_at ? $star->last_follow_up_at : $star->created_at,
                    'last_updated_user' => $last_updated_user ? $last_updated_user->user->name : null,
                    'last_follow_up_user_id'    =>  $last_follow_up_user ? $last_follow_up_user->user->id : null,
                    'last_follow_up_user'   => $last_follow_up_user ? $last_follow_up_user->user->name : null,
                ];
                $star->update($data);
            }

        });



    }
}
