<?php

namespace Vanry\Scout\Engines;

use Laravel\Scout\Builder;
use TeamTNT\TNTSearch\TNTSearch;
use Laravel\Scout\Engines\Engine;
use TeamTNT\TNTSearch\Exceptions\IndexNotFoundException;

class TNTSearchEngine extends Engine
{
    /**
     * @var TNTSearch
     */
    protected $tnt;

    /**
     * @var Builder
     */
    protected $builder;

    /**
     * Create a new engine instance.
     *
     * @param TNTSearch $tnt
     */
    public function __construct(TNTSearch $tnt)
    {
        $this->tnt = $tnt;
    }

    /**
     * Update the given model in the index.
     *
     * @param \Illuminate\Database\Eloquent\Collection $models
     *
     * @return void
     */
    public function update($models)
    {
        $model = $models->first();

        $this->initIndex($model);

        $index = $this->tnt->getIndex();

        $index->setPrimaryKey($model->getKeyName());
        $index->setTokenizer($this->tnt->tokenizer);
        $index->setStopWords($this->tnt->config['stopwords'] ?? []);

        $index->indexBeginTransaction();

        $models->each(function ($model) use ($index) {
            $array = $model->toSearchableArray();

            if (empty($array)) {
                return;
            }

            if ($model->getKey()) {
                $index->update($model->getKey(), $array);
            } else {
                $index->insert($array);
            }
        });

        $index->indexEndTransaction();
    }

    /**
     * Remove the given model from the index.
     *
     * @param \Illuminate\Database\Eloquent\Collection $models
     *
     * @return void
     */
    public function delete($models)
    {
        $this->initIndex($models->first());

        $models->each(function ($model) {
            $this->tnt->getIndex()->delete($model->getKey());
        });
    }

    /**
     * Perform the given search on the engine.
     *
     * @param Builder $builder
     *
     * @return mixed
     */
    public function search(Builder $builder)
    {
        try {
            return $this->performSearch($builder);
        } catch (IndexNotFoundException $e) {
            $this->initIndex($builder->model);
        }
    }

    /**
     * Perform the given search on the engine.
     *
     * @param Builder $builder
     * @param int     $perPage
     * @param int     $page
     *
     * @return mixed
     */
    public function paginate(Builder $builder, $perPage, $page)
    {
        $results = $this->performSearch($builder);

        if ($builder->limit) {
            $results['hits'] = $builder->limit;
        }

        $chunks = array_chunk($results['ids'], $perPage);

        if (! empty($chunks)) {
            if (array_key_exists($page - 1, $chunks)) {
                $results['ids'] = $chunks[$page - 1];
            } else {
                $results['ids'] = end($chunks);
            }
        }

        return $results;
    }

    /**
     * Perform the given search on the engine.
     *
     * @param Builder $builder
     *
     * @return mixed
     */
    protected function performSearch(Builder $builder, array $options = [])
    {
        $this->builder = $builder;

        $index = $builder->index ?: $builder->model->searchableAs();

        $this->tnt->selectIndex("{$index}.index");

        $this->tnt->asYouType = $builder->model->asYouType ?: false;

        if ($builder->callback) {
            return call_user_func($builder->callback, $this->tnt, $builder->query, $options);
        }

        $limit = $builder->limit ?: 10000;

        return ($this->tnt->config['searchBoolean'] ?? false)
            ? $this->tnt->searchBoolean($builder->query, $limit)
            : $this->tnt->search($builder->query, $limit);
    }

    /**
     * Map the given results to instances of the given model.
     *
     * @param Builder $builder
     * @param mixed $results
     * @param \Illuminate\Database\Eloquent\Model $model
     *
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function map(Builder $builder, $results, $model)
    {
        if (count($results['ids']) === 0) {
            return $model->newCollection();
        }

        $keys = collect($results['ids'])->values()->all();

        $models = $model->whereIn($model->getQualifiedKeyName(), $keys)->get()->keyBy($model->getKeyName());

        $fieldsWheres = array_keys($this->builder->wheres);

        return collect($results['ids'])->map(function ($hit) use ($models) {
            return $models->has($hit) ? $models[$hit] : null;
        })->filter(function ($model) use ($fieldsWheres) {
            return ! is_null($model) && array_reduce($fieldsWheres, function ($carry, $item) use ($model) {
                return $carry && $model[$item] == $this->builder->wheres[$item];
            }, true);
        });
    }

    /**
     * Pluck and return the primary keys of the given results.
     *
     * @param mixed $results
     * @return \Illuminate\Support\Collection
     */
    public function mapIds($results)
    {
        return collect($results['ids'])->values();
    }

    /**
     * Get the total count from a raw result returned by the engine.
     *
     * @param mixed $results
     *
     * @return int
     */
    public function getTotalCount($results)
    {
        return $results['hits'];
    }

    public function initIndex($model)
    {
        if (! file_exists($this->tnt->config['storage'])) {
            mkdir($this->tnt->config['storage'], 0777, true);
        }

        $indexPath = $this->getIndexPath($model);

        $indexName = basename($indexPath);

        if (! file_exists($indexPath)) {
            $indexer = $this->tnt->createIndex($indexName);
            $indexer->setDatabaseHandle($model->getConnection()->getPdo());
            $indexer->setPrimaryKey($model->getKeyName());
        }

        $this->tnt->selectIndex($indexName);
    }

    public function flush($model)
    {
        $indexPath = $this->getIndexPath($model);

        if (file_exists($indexPath)) {
            unlink($indexPath);
        }
    }

    protected function getIndexPath($model)
    {
        return sprintf('%s/%s.index', $this->tnt->config['storage'], $model->searchableAs());
    }
}
