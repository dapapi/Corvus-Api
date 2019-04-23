<?php

namespace App\Console\Commands;

use App\Helper\Generator;
use App\Models\Blogger;
use App\Models\Client;
use App\Models\Star;
use App\SignContractStatus;
use Illuminate\Console\Command;

class genderStarCode extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'make:starCode';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = '为没有编码的已签约的艺人生成编码(在集成用友之前老数据处理)';

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
        $genderator = new Generator();
        //生成艺人编码
        $star_list = Star::where('sign_contract_status',SignContractStatus::ALREADY_SIGN_CONTRACT)
            ->whereRaw('accode is null')
            ->get(['id']);
        foreach ($star_list as $star){
            $star->accode = $genderator->generatorCode('ty',4,false);
            $star->save();
        }
        //生博主编码
        $blogger_list = Blogger::where('sign_contract_status',SignContractStatus::ALREADY_SIGN_CONTRACT)
            ->whereRaw('accode is null')
            ->get(['id']);
        foreach ($blogger_list as $blogger){
            $blogger->accode = $genderator->generatorCode('cy',4,false);
            $blogger->save();
        }
        //生成客户编码
        $client_list = Client::whereRaw('cuscode is null')->get(['id']);
        foreach ($client_list as $client){
            $client->cuscode = $genderator->generatorCode('kh',5,false);
            $client->save();
        }
        //生成项目编码
        $project_list = \App\Models\Project::join('approval_form_instances as afi','afi.form_instance_number','projects.project_number')
            ->where("form_status",232)->get();

        foreach ($project_list as $project){
            $project->project_code = $genderator->generatorCode("xm",4,true);
            $project->project_enflag = 2;
            $project->save();
        }

    }
}
