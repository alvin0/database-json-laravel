<?php

namespace DatabaseJson\Commands\Model;

use Illuminate\Console\GeneratorCommand;
use Illuminate\Support\Str;
use Symfony\Component\Console\Input\InputOption;

class ModelMakeCommand extends GeneratorCommand
{
    /**
     * The console command name.
     *
     * @var string
     */
    protected $name = 'databasejson:model';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create a new Database Json model class.';

    /**
     * The type of class being generated.
     *
     * @var string
     */
    protected $type = 'Database Json | Database Json class model';

    /**
     * Execute the console command.
     *
     * @return void
     */
    public function handle()
    {
        if (parent::handle() === false && !$this->option('force')) {
            return false;
        }
        if ($this->option('migrate')) {
            $table = Str::snake(Str::pluralStudly(class_basename($this->argument('name'))));
            $migrate = Str::studly(class_basename($this->argument('name')));
            $this->call('databasejson:migration', [
                'name' => "{$migrate}",
                '--table' => $this->qualifyClass($this->getNameInput()),
                '--force' => $this->option('force'),
            ]);
        }
    }

    /**
     * Get the stub file for the generator.
     *
     * @return string
     */
    protected function getStub()
    {
        $stub = null;

        $stub = $stub ?? '/stubs/model.stub';

        return __DIR__ . $stub;
    }

    /**
     * Get the default namespace for the class.
     *
     * @param  string $rootNamespace
     * @return string
     */
    protected function getDefaultNamespace($rootNamespace)
    {
        return $rootNamespace . '\\' . config('databasejson.models_path', 'DatabaseJson') . '\Models';
    }

    /**
     * Get the console command options.
     *
     * @return array
     */
    protected function getOptions()
    {
        return [
            ['force', '-f', InputOption::VALUE_NONE, 'Create the class even if the model already exists'],
            ['migrate', '-m', InputOption::VALUE_NONE, 'Generate a migrate for the given model.'],
        ];
    }

}
