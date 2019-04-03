<?php

namespace App\Console\Commands;

use App\Models\Blogger;
use Doctrine\Common\Collections\Collection;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class MakeBloggerData extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'make:bloggerdata';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = '生成博主数据';

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
        $bloggers = Blogger::chunk(100,function($bloggerlist){
            foreach ($bloggerlist as $blogger){
                $data = [
                    'last_updated_user_id'    =>  $blogger->last_updated_user->id,
                    'last_updated_at'   =>  $blogger->last_updated_at,
                    'last_follow_up_at' =>  $blogger->last_follow_up_at,
                    'last_updated_user' => $blogger->last_updated_user->name,
                ];
                $blogger->save($data);
            }

        });

    }
}
