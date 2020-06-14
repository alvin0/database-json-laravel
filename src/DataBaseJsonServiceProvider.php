<?php

namespace DatabaseJson;

use Illuminate\Support\ServiceProvider;

class DataBaseJsonServiceProvider extends ServiceProvider
{

    /**
     * Register config file here
     * alias => path
     */
    private $configFile = [
        'databasejson' => 'config/databasejson.php',
    ];

    /**
     * Register services.
     *
     * @return void
     */
    public function register()
    {
        foreach ($this->configFile as $alias => $path) {
            $this->mergeConfigFrom(__DIR__ . "/" . $path, $alias);
        }
    }

    /**
     * Bootstrap services.
     *
     * @return void
     */
    public function boot()
    {
        $this->commands([
            'DatabaseJson\Commands\Model\ModelMakeCommand',
            'DatabaseJson\Commands\Migrate\MigrateCommand',
            'DatabaseJson\Commands\Migrate\MigrationMakeCommand',
        ]);
    }
}
