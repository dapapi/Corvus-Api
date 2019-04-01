<?php

namespace App\Console\Commands;

use App\Models\Star;
use Carbon\Carbon;
use Illuminate\Console\Command;

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
        $stars = Star::all();
        foreach ($stars as $star){
            if (!$star->created_at){
                $star->created_at = Carbon::now()->toDateTimeString();
            }
            if (!$star->updated_at){
                $star->updated_at = Carbon::now()->toDateTimeString();
            }
            if (!$star->last_updated_user){
                $star->last_updated_user = "李乐";
            }
            if (!$star->last_updated_user_id){
                $star->last_updated_user_id = 11;
            }
            if (!$star->last_updated_at){
                $star->last_updated_at =  Carbon::now()->toDateTimeString();
            }
            if (!$star->last_follow_up_at){
                $star->last_follow_up_at =  Carbon::now()->toDateTimeString();
            }
            if (!$star->contract_start_date){
                $star->contract_start_date =  Carbon::now()->toDateTimeString();
            }
            $star->save();
        }


    }
}
