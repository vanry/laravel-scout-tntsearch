<?php

namespace Vanry\Scout\Console;

use InvalidArgumentException;
use Illuminate\Console\Command;
use TeamTNT\TNTSearch\TNTSearch;

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
     * @return void
     */
    public function handle()
    {
        $class = $this->argument('model');

        $model = new $class;

        $indexer = $this->initIndex($model);

        $indexer->query($this->getSql($model));

        $indexer->run();

        $this->info("All [{$class}] records have been imported.");
    }

    protected function initIndex($model)
    {
        $tnt = new TNTSearch;

        $tnt->loadConfig($this->getConfig($model));

        $tnt->setDatabaseHandle($model->getConnection()->getPdo());

        $indexer = $tnt->createIndex("{$model->searchableAs()}.index");

        $indexer->inMemory = false;

        $indexer->setPrimaryKey($model->getKeyName());

        return $indexer;
    }

    protected function getConfig($model)
    {
        $driver = $model->getConnectionName() ?: config('database.default');

        $config = config('tntsearch') + config("database.connections.{$driver}");

        if (! array_key_exists($config['default'], $config['tokenizers'])) {
            throw new InvalidArgumentException("Tokenizer [{$config['default']}] is not defined.");
        }

        return array_merge($config, ['tokenizer' => $config['tokenizers'][$config['default']]['driver']]);
    }

    protected function getSql($model)
    {
        $query = $model->newQuery();

        if ($fields = $this->getSearchableFields($model)) {
            $query->select($model->getKeyName())->addSelect($fields);
        }

        return $query->toSql();
    }

    protected function getSearchableFields($model)
    {
        $availableColumns = $model->getConnection()->getSchemaBuilder()->getColumnListing($model->getTable());

        $desiredColumns = array_keys($model->toSearchableArray());

        return array_intersect($desiredColumns, $availableColumns);
    }
}
