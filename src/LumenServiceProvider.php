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
        $this->app->configure('scout');
        $this->app->configure('tntsearch');

        $this->mergeConfigFrom(__DIR__.'/../config/tntsearch.php', 'tntsearch');

        $this->app->instance('path.config', $this->app->configPath());
        $this->app->singleton(TokenizerInterface::class, $this->getConfig()['tokenizer']);

        if ($this->app->runningInConsole()) {
            $this->commands(ImportCommand::class);
        }
    }

    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot()
    {
        $this->app->make('view');

        parent::boot();
    }
}
