<?php

namespace Vanry\Scout;

use Vanry\Scout\Console\ImportCommand;

class LumenServiceProvider extends TNTSearchScoutServiceProvider
{
    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
        $this->app->instance('path.config', $this->app->configPath());

        $this->loadConfig('scout');
        $this->loadConfig('tntsearch');

        $this->mergeConfigFrom(__DIR__.'/../config/tntsearch.php', 'tntsearch');

        if ($this->app->runningInConsole()) {
            $this->commands(ImportCommand::class);
        }
    }

    protected function loadConfig($name)
    {
        if (file_exists($this->app->configPath("{$name}.php")) && ! $this->app->has($name)) {
            $this->app->configure($name);
        }
    }
}
