<?php

namespace App\Jobs;

use App\Repositories\UmengRepository;
use Illuminate\Bus\Queueable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Support\Facades\Log;

class SendUmengMsgToMobile implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;
    protected $UmenMessage;
    public $tries = 1;
    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct($UmenMessage)
    {
        Log::info("对列初始化");
        Log::info($UmenMessage);
        $this->UmenMessage = $UmenMessage;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
//        if ($this->UmenMessage){
            Log::info("消息");
            Log::info($this->UmenMessage);
            $send_to = $this->UmenMessage['send_to'];
            $title = $this->UmenMessage['title'];
            $tricker = $this->UmenMessage['tricker'];
            $text = $this->UmenMessage['text'];
            $module_data_id = $this->UmenMessage['module_data_id'];
            $module = $this->UmenMessage['module'];
            $description   =   $this->UmenMessage['description'];
            //推送消息
            (new UmengRepository())->sendMsgToMobile($send_to,$tricker,$title,$text,$description,$module,$module_data_id);
//        }

    }
}
