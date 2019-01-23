<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;

class Entity extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'make:entity {name} {--table=}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = '创建实体';

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
     * Get the stub file for the generator.
     *
     * @return string
     */
    protected function getStub() {
        return __DIR__.'/stubs/entity.stub';
    }
    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        $columns = Schema::getColumnListing($this->option('table'));
        $columns_notes = DB::getDoctrineSchemaManager()->listTableDetails($this->option('table'));
        $content = "";
        foreach ($columns as $column){
            $content .= "    /**\n";
            $content .= "     *@desc ".$columns_notes->getColumn($column)->getComment()."\n";
            $content .= "     */\n";
            $content .= '   $private '.$column."\n\n";
        }
        dd($this->getStub());
        $class_file = file_get_contents($this->getStub());

        $class_file = str_replace("@content",$content,$class_file);
        $path = __DIR__."../../Entity/";
        Storage::disk("local")->put($path.$this->argument("name")."php",$class_file);

    }
}
