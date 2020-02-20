<?php

namespace Vanry\Scout\Console;

use Illuminate\Console\Command;
use TeamTNT\TNTSearch\TNTSearch;
use Illuminate\Support\Facades\Schema;
use Illuminate\Contracts\Events\Dispatcher;

class ImportCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'tntsearch:import {model}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Import the given model into the tntsearch index';

    /**
     * Execute the console command.
     *
     * @param \Illuminate\Contracts\Events\Dispatcher $events
     *
     * @return void
     */
    public function handle(Dispatcher $events)
    {
        $class = $this->argument('model');

        $model = new $class;

        $driver = $model->getConnectionName() ?: config('database.default');

        $config = config('tntsearch') + config("database.connections.$driver");

        $db = app('db')->connection($driver);

        $tnt = new TNTSearch;

        $tnt->loadConfig($config);

        $tnt->setDatabaseHandle($db->getPdo());

        $indexer = $tnt->createIndex("{$model->searchableAs()}.index");

        $indexer->setPrimaryKey($model->getKeyName());

        $availableColumns = Schema::connection($driver)->getColumnListing($model->getTable());

        $desiredColumns = array_keys($model->toSearchableArray());

        $fields = array_intersect($desiredColumns, $availableColumns);

        $query = $db->table($model->getTable());

        if ($fields) {
            $query->select($model->getKeyName())->addSelect($fields);
        }

        $indexer->query($query->toSql());

        $indexer->run();

        $this->info("All [{$class}] records have been imported.");
    }
}
