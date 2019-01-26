<?php

namespace App\Console\Commands;

use App\Models\Client;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class ClientProtected extends Command
{
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
        Log::info("直客到期检查");
        $now = Carbon::now();
        //获取保护截止日期在当前时间之后的直客
        $clients = Client::where('grade',Client::GRADE_NORMAL)->where('protected_client_time','>',$now->toDateTimeString())->get();
        foreach ($clients as $client){
            $protected_client_time = Carbon::createFromTimeString($client->protected_client_time);
            if ($protected_client_time->diffInDays($now) == 5){
                //发消息

            }
        }
    }
}
