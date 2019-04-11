<?php

namespace App\Console\Commands;

use App\Models\Blogger;
use App\Models\Client;
use App\Models\DataDictionarie;
use App\Models\Star;
use App\Models\Trail;
use App\Repositories\RoleUserRepository;
use App\Repositories\ScopeRepository;
use App\User;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class powerCache extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'power:cache';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = '生成权限缓存';

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
        $user_list = User::select('id')->get();

        $star_list = Star::select('id')->get();
        $this->starPowerCache($star_list,$user_list);

        $blogger_list = Blogger::select('id')->get();
        $this->starPowerCache($blogger_list,$user_list);

        $trail_list = Trail::select('id')->get();
        $this->starPowerCache($trail_list,$user_list);

        $client_list = Client::select('id')->get();
        $this->starPowerCache($client_list,$user_list);

        $task_list = Star::select('id')->get();
        $this->starPowerCache($task_list,$user_list);

        $project_list = \App\Models\Project::select('id')->get();
        $this->starPowerCache($project_list,$user_list);
    }

    /**
     * 生成艺人权限缓存
     * @param $user_list
     */
    public function starPowerCache($model_list,$user_list)
    {
        $scopeRepository = new ScopeRepository();
        $api_list = DataDictionarie::where('parent_id',5)->select('code','val')->get();
        $star_list = Star::select('id')->get();
        foreach ($model_list as $model){
            foreach ($user_list as $user){
                foreach ($api_list as $api){
                    $key = "power:{$model->getMorphClass()}:data_id:{$model->id}:user_id:{$user->id}:api_method:{$api->code}:api_uri:{$api->val}";
                    $role_list = RoleUserRepository::getRoleList($user->id);
                    try{
                        $scopeRepository->checkPower($api->val,$api->code,$role_list,$model);
                        Cache::put($key,true,Carbon::now()->addHour(1));
                        Log::info([$key,Cache::get($key)]);
                    }catch (\Exception $exception){
                        Cache::put($key,false,Carbon::now()->addHour(1));
                        Log::info([$key,Cache::get($key)]);
                    }

                }
            }
        }
    }
}
