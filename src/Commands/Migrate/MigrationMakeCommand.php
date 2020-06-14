<?php

namespace DatabaseJson\Commands\Migrate;

use Illuminate\Console\Command;
use Illuminate\Support\Str;

class MigrationMakeCommand extends Command
{
    /**
     * The console command signature.
     *
     * @var string
     */
    protected $signature = 'databasejson:migration {name : The name of the migration}
        {--update : The table to be updated }
        {--table= : The table to migrate}
        {--force : Create the class even if the migrate already exists}
        ';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Database Json | Create a new migration file';

    /**
     * The migration creator instance.
     *
     * @var DatabaseJson\Commands\Migrate\MigrationCreator
     */
    protected $creator;

    /**
     * Create a new migration install command instance.
     *
     * @param  DatabaseJson\Commands\Migrate\MigrationCreator  $creator
     * @return void
     */
    public function __construct(MigrationCreator $creator)
    {
        parent::__construct();

        $this->creator = $creator;
    }

    /**
     * Execute the console command.
     *
     * @return void
     */
    public function handle()
    {
        // It's possible for the developer to specify the tables to modify in this
        // schema operation. The developer may also specify if this table needs
        // to be freshly created so we can create the appropriate migrations.
        $table = $name = Str::snake(trim($this->input->getArgument('name')));
        if ($this->option('table')) {
            $table = Str::snake(Str::pluralStudly(class_basename($this->option('table'))));
        }
        $table = Str::plural($table, 2);

        $create = true;

        if ($this->option('update')) {
            $create = false;
        }

        $path = app_path(config('databasejson.migrations_path', 'DatabaseJson'));

        // Now we are ready to write the migration out to disk. Once we've written
        // the migration out, we will dump-autoload for the entire framework to
        // make sure that the migrations are registered by the class loaders.
        $this->writeMigration($name, $path, $table, $create);

    }

    /**
     * Write the migration file to disk.
     *
     * @param  string  $name
     * @param  string  $table
     * @param  bool  $create
     * @return string
     */
    protected function writeMigration($name, $path, $table, $create)
    {
        $file = $this->creator->create(
            $name, $path, $table, $create
        );

        $this->line("<info>Database Json | Created Migration:</info> {$file}");
    }

    /**
     * Get migration path (either specified by '--path' option or default location).
     *
     * @return string
     */
    protected function getMigrationPath()
    {
        if (!is_null($targetPath = $this->input->getOption('path'))) {
            return !$this->usingRealPath()
            ? $this->laravel->basePath() . '/' . $targetPath
            : $targetPath;
        }

        return $this->laravel->databasePath() . DIRECTORY_SEPARATOR . 'migrations';
    }

    /**
     * Determine if the given path(s) are pre-resolved "real" paths.
     *
     * @return bool
     */
    protected function usingRealPath()
    {
        return $this->input->hasOption('realpath') && $this->option('realpath');
    }

}
