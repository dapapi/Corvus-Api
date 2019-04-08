<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Jobs\ProjectImplode as Implode;

class ProjectImplode extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'command:name';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

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
        Project::orderBy('id')->chunk(10, function ($projects) {
            foreach ($projects as $project) {
                dispatch(new Implode($project));
            }
        });
    }
}
