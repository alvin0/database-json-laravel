<?php

namespace DatabaseJson\Commands\Migrate;

use DatabaseJson\Core\Helpers\Config;
use DatabaseJson\Core\Helpers\Data;
use DatabaseJson\DatabaseJson;
use File;
use Illuminate\Console\Command;
use Illuminate\Filesystem\Filesystem;
use Str;

class MigrateCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'databasejson:migrate {--fresh : remove all table and up}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Run all migrate';

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        if ($this->option('fresh')) {
            File::deleteDirectory(config('databasejson.path'));
            $this->info('Database Json | Remove all database json success!');
        }
        $this->runAllMigrateUP();
    }

    /**
     * Execute all migrate up.
     *
     * @return mixed
     */
    public function runAllMigrateUP()
    {
        $count = 0;
        foreach (glob(app_path(config('databasejson.migrations_path', 'DatabaseJson') . '/Migrations/*.php')) as $class) {
            $class = $this->getMigrationName($class);
            $checkMigrate = $this->addMigrateToTableMigrates($class);
            if ($checkMigrate) {
                $count++;

                $migration = $this->resolve($class);
                $migration->up();

                $this->info('Database Json | ' . $class . ' run success!');
            }
        }
        if ($count == 0) {
            $this->info('Database Json | Nothing to migrate');
        }
    }

    /**
     * Resolve a migration instance from a file.
     *
     * @param  string  $file
     * @return object
     */
    public function resolve($file)
    {
        $files = new Filesystem();
        $files->requireOnce(app_path(config('databasejson.migrations_path', 'DatabaseJson') . '/Migrations/' . $file . '.php'));
        $class = Str::studly(implode('_', array_slice(explode('_', $file), 4)));
        $nameSpaceMigrate = '\App\\' . config('databasejson.migrations_path', 'DatabaseJson') . '\Migrations\\' . $class;

        return new $nameSpaceMigrate;
    }

    /**
     * Get the date prefix for the migration.
     *
     * @return string
     */
    protected function getDatePrefix()
    {
        return date('Y_m_d_His');
    }

    /**
     * Get the full path to the migration.
     *
     * @param  string  $name
     * @param  string  $path
     * @return string
     */
    protected function getPath($name, $path)
    {
        return $path . '/' . $this->getDatePrefix() . '_' . $name . '.php';
    }

    /**
     * Get the class name of a migration name.
     *
     * @param  string  $name
     * @return string
     */
    protected function getClassName($name)
    {
        return Str::studly($name);
    }

    /**
     * Get the name of the migration.
     *
     * @param  string  $path
     * @return string
     */
    public function getMigrationName($path)
    {
        return str_replace('.php', '', basename($path));
    }

    public function createTableMigrate()
    {
        $table = 'migrates';
        if (Data::table($table)->exists() && Config::table($table)->exists()) {
            return $table;
        }

        DatabaseJson::create($table, array(
            'name' => 'string',
            'created_at' => 'string',
            'updated_at' => 'string',
        ));
        return $table;
    }

    public function addMigrateToTableMigrates($name)
    {
        $this->createTableMigrate();
        $migrate = DatabaseJson::table('migrates')->where('name', '=', $name)->find();

        if ($migrate->id == null) {
            $migrate = DatabaseJson::table('migrates');
            $migrate->name = $name;
            $migrate->save();
            return true;
        }
        return false;
    }
}
