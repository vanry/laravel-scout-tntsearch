<?php

namespace Vanry\Scout;

use Laravel\Scout\EngineManager;
use TeamTNT\TNTSearch\TNTSearch;
use Illuminate\Support\ServiceProvider;
use Vanry\Scout\Engines\TNTSearchEngine;

class TNTSearchScoutServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
        $this->mergeConfigFrom(__DIR__.'/../config/scout.php', 'scout');

        $this->app->singleton('tntsearch.tokenizer', function ($app) {
            return new TokenizerManager($app);
        });
    }

    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot()
    {
        $this->app[EngineManager::class]->extend('tntsearch', function ($app) {
            $tnt = new TNTSearch();

            $driver = config('database.default');
            $config = config('scout.tntsearch') + config("database.connections.{$driver}");

            $tnt->loadConfig($config);
            $tnt->setTokenizer(app('tntsearch.tokenizer')->driver());
            $tnt->setDatabaseHandle(app('db')->connection()->getPdo());

            $this->setFuzziness($tnt);
            $this->setAsYouType($tnt);

            return new TNTSearchEngine($tnt);
        });
    }

    protected function setFuzziness($tnt)
    {
        $tnt->fuzziness = config('scout.tntsearch.fuzziness', $tnt->fuzziness);
        $tnt->fuzzy_distance = config('scout.tntsearch.fuzzy.distance', $tnt->fuzzy_distance);
        $tnt->fuzzy_prefix_length = config('scout.tntsearch.fuzzy.prefix_length', $tnt->fuzzy_prefix_length);
        $tnt->fuzzy_max_expansions = config('scout.tntsearch.fuzzy.max_expansions', $tnt->fuzzy_max_expansions);
    }

    protected function setAsYouType($tnt)
    {
        $tnt->asYouType = config('scout.tntsearch.asYouType', $tnt->asYouType);
    }
}
