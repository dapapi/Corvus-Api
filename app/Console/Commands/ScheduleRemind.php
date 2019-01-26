<?php

namespace App\Console\Commands;

use App\Models\Schedule;
use App\Repositories\MessageRepository;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class ScheduleRemind extends Command
{
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
    public function __construct(MessageRepository $messageRepository)
    {
        parent::__construct();
        $this->messageRepository = $messageRepository;
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        $now = Carbon::now();

        //日程提醒
        $schdules = Schedule::where("start_at",'>',$now->toDateTimeString())->select('remind','id','creator_id','start_at')->get();
        foreach ($schdules as $schdule){
            $remid_time = Carbon::createFromTimeString($schdule->start_at);
            $flag = false; //是否发消息标志
            switch ($schdule->remind){
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
                    if ($remid_time->diffInHours($now) == 1){
                        $flag = true;
                    }
                    break;
                case Schedule::REMIND_TWO_HOURS://两小时前
                    if ($remid_time->diffInHours($now) == 2){
                        $flag = true;
                    }
                    break;
                case Schedule::REMIND_ONE_DAY://一天前
                    if ($remid_time->diffInDays($now) == 1){
                        $flag = true;
                    }
                    break;
                case Schedule::REMIND_TWO_DAY://两天前
                    if ($remid_time->diffInDays($now) == 2){
                        $flag = true;
                    }
                    break;
            }
            if($flag){
                //发消息

            }
        }

    }
}
