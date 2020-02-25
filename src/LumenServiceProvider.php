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
        $this->configure('scout');
        $this->configure('tntsearch');

        $this->app->instance('path.config', $this->app->configPath());

        if ($this->app->runningInConsole()) {
            $this->commands(ImportCommand::class);
        }
    }
}
