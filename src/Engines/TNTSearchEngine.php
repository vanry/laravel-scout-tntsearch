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

        $index = $this->initIndex($model);

        $index->setPrimaryKey($model->getKeyName());
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
        $index = $this->initIndex($models->first());

        $models->each(function ($model) use ($index) {
            $index->delete($model->getKey());
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
            $this->createIndex($builder->model);

            return $this->performSearch($builder);
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
        $results = $this->search($builder);

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
        $index = $builder->index ?: $builder->model->searchableAs();

        $this->tnt->selectIndex("{$index}.index");

        if ($builder->callback) {
            return call_user_func($builder->callback, $this->tnt, $builder->query, $options);
        }

        return $this->tnt->search($builder->query, $builder->limit ?: 10000);
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
        if (is_null($results['ids']) || count($results['ids']) === 0) {
            return $model->newCollection();
        }

        $keys = collect($results['ids'])->values()->all();

        $models = $model->whereIn($model->getQualifiedKeyName(), $keys)->get()->keyBy($model->getKeyName());

        $fieldsWheres = array_keys($builder->wheres);

        return collect($results['ids'])->map(function ($hit) use ($models) {
            return $models->has($hit) ? $models[$hit] : null;
        })->filter(function ($model) use ($fieldsWheres, $builder) {
            return ! is_null($model) && array_reduce($fieldsWheres, function ($carry, $item) use ($model, $builder) {
                return $carry && $model[$item] == $builder->wheres[$item];
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

    protected function initIndex($model)
    {
        if (! file_exists($this->indexPath($model))) {
            $this->createIndex($model);
        }

        $this->tnt->selectIndex($this->indexName($model));

        return $this->tnt->getIndex();
    }

    protected function indexName($model)
    {
        return $model->searchableAs().'.index';
    }

    protected function indexPath($model)
    {
        return $this->tnt->config['storage'].'/'.$this->indexName($model);
    }

    protected function createIndex($model)
    {
        if (! file_exists($this->tnt->config['storage'])) {
            mkdir($this->tnt->config['storage'], 0777, true);
        }

        $this->tnt->createIndex($this->indexName($model));
    }

    public function flush($model)
    {
        $indexPath = $this->indexPath($model);

        if (file_exists($indexPath)) {
            unlink($indexPath);
        }
    }
}
