<?php

namespace App\Console\Commands;


use Illuminate\Console\GeneratorCommand;

class TriggerPoint extends GeneratorCommand
{
//    /**
//     * The name and signature of the console command.
//     *
//     * @var string
//     */
//    protected $signature = 'make:triggerPoint';
    protected $name = 'make:triggerPoint';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = '创建触发点常量类';



    /**
     * Get the stub file for the generator.
     *
     * @return string
     */
    protected function getStub() {
        return __DIR__.'/stubs/triggerPoint.stub';
    }

    /**
     * @param string $rootNamespace
     * @return string
     */
    protected function getDefaultNamespace($rootNamespace) {
        return $rootNamespace.'\triggerPoint';
    }
}
