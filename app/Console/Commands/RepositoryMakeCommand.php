<?php

namespace App\Console\Commands;

use Illuminate\Console\GeneratorCommand;

class RepositoryMakeCommand extends GeneratorCommand
{
    protected $name = 'make:repository';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create a new repository class';

    /**
     * @var string
     */
    protected $type = 'Repository';

    /**
     * Get the stub file for the generator.
     *
     * @return string
     */
    protected function getStub() {
        return __DIR__.'/stubs/repository.stub';
    }

    /**
     * @param string $rootNamespace
     * @return string
     */
    protected function getDefaultNamespace($rootNamespace) {
        return $rootNamespace.'\Repositories';
    }
}
