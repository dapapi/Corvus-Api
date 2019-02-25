<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Console\GeneratorCommand;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;

class Entity extends GeneratorCommand
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
//    public function handle()
//    {
//
//        dd($this->getDefaultNamespace());
//        $this->files->makeDirectory()
//
//        $this->files->put($path, $this->buildClass($name));
//
//        $this->info($this->type.' created successfully.');

//    }
    /**
     * @param string $rootNamespace
     * @return string
     */
    protected function getDefaultNamespace($rootNamespace) {
        return $rootNamespace.'\Entity';
    }

    /**
     * Build the class with the given name.
     *
     * @param  string  $name
     * @return string
     */
    protected function buildClass($name)
    {
        $stub = $this->files->get($this->getStub());
        $columns = Schema::getColumnListing($this->option('table'));
        $columns_notes = DB::getDoctrineSchemaManager()->listTableDetails($this->option('table'));
        $content = "";
        foreach ($columns as $column){
            $content .= "    /**\n";
            $content .= "     *@desc ".$columns_notes->getColumn($column)->getComment()."\n";
            $content .= "     */\n";
            $content .= '   public $'.$column.";\n\n";
        }

        foreach ($columns as $column){
            $content .= "   public function get_".$column."()\n";
            $content .= "   {\n";
            $content .= "       return \$this->{$column};\n";
            $content .= "   }\n";
        }


        $class_content = str_replace("@content",$content,$stub);

        return $this->replaceNamespace($class_content, $name)->replaceClass($class_content, $name);
    }
}
