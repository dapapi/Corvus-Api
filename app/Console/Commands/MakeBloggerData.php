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
        $bloggers = Blogger::all();
        foreach ($bloggers as $blogger){
            DB::connection()->enableQueryLog();
            $publicity_list = $blogger->publicity()->get();

            if (!$publicity_list->isEmpty()){
                $blogger_publicity = [];
                foreach ($publicity_list as $publicity){
                    $temp['user_id'] = $publicity->id;
                    $temp['user_name'] = $publicity->name;
                    $deparment = DB::table("users")
                                            ->leftJoin("department_user","department_user.user_id","users.id")
                                            ->join("departments","departments.id",'department_user.department_id')
                                            ->where('users.id',$publicity->id)->first();
                    $temp['department_id'] = $deparment->id;
                    $temp['department_name'] = $deparment->name;
                    $blogger_publicity[] = $temp;
                }
                $blogger->publicity = json_encode($blogger_publicity);
                $blogger->save();
            }
        }
    }
}
