<?php

namespace App\Console\Commands;

use Illuminate\Console\GeneratorCommand;

class TransformerMakeCommand extends GeneratorCommand
{
    /**
     * @var string
     */
    protected $name = 'make:transformer';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create a new transformer class';

    /**
     * @var string
     */
    protected $type = 'Transformer';

    /**
     * @return string
     */
    protected function getStub()
    {
        return __DIR__.'/stubs/transformer.stub';
    }

    /**
     * @param string $rootNamespace
     * @return string
     */
    protected function getDefaultNamespace($rootNamespace) {
        return $rootNamespace.'\Http\Transformers';
    }
}
