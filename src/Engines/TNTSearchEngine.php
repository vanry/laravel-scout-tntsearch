<?php

namespace Vanry\Scout\Engines;

use Illuminate\Database\Eloquent\Builder as Query;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\LazyCollection;
use Laravel\Scout\Builder;
use Laravel\Scout\Engines\Engine;
use TeamTNT\TNTSearch\Exceptions\IndexNotFoundException;
use TeamTNT\TNTSearch\TNTSearch;

class TNTSearchEngine extends Engine
{
    const LIMIT = 10000;

    /**
     * The TNTSearch instance.
     *
     * @var \TeamTNT\TNTSearch\TNTSearch
     */
    protected $tnt;

    /**
     * The Scout Builder
     *
     * @var \Laravel\Scout\Builder
     */
    protected $builder;

    /**
     * Create a new engine instance.
     *
     * @param \TeamTNT\TNTSearch\TNTSearch $tnt
     */
    public function __construct(TNTSearch $tnt)
    {
        $this->tnt = $tnt;
    }

    /**
     * Update the given model in the index.
     *
     * @param  \Illuminate\Database\Eloquent\Collection  $models
     * @return void
     */
    public function update($models)
    {
        $model = $models->first();

        $index = $this->initIndex($model->searchableAs());

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
     * @param  \Illuminate\Database\Eloquent\Collection  $models
     * @return void
     */
    public function delete($models)
    {
        $index = $this->initIndex($models->first()->searchableAs());

        $models->each(function ($model) use ($index) {
            $index->delete($model->getKey());
        });
    }

    /**
     * Delete a search index.
     *
     * @param  string  $name
     * @return mixed
     */
    public function deleteIndex($name)
    {
        $indexPath = $this->indexPath($name);

        if (file_exists($indexPath)) {
            unlink($indexPath);
        }
    }

    /**
     * Perform the given search on the engine.
     *
     * @param  \Laravel\Scout\Builder  $builder
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
     * @param  \Laravel\Scout\Builder $builder
     * @param  int  $perPage
     * @param  int  $page
     * @return mixed
     */
    public function paginate(Builder $builder, $perPage, $page)
    {
        $results = $this->search($builder);

        $results['hits'] = $this->getFilteredTotalCount($builder, $results);

        if ($builder->limit) {
            $results['hits'] = min($results['hits'], $builder->limit);
        }

        $chunks = array_chunk($results['ids'], $perPage);

        $results['ids'] = $chunks[$page - 1] ?? [];

        return $results;
    }

    protected function getFilteredTotalCount(Builder $builder, $results)
    {
        $this->builder = $builder;

        $model = $builder->model;

        $query = $model->whereIn($model->getQualifiedKeyName(), $results['ids']);

        if ($this->usesSoftDelete($model) && config('scout.soft_delete')) {
            $query = $this->handleSoftDelete($query);
        }

        return $this->applyWheres($query)->count();
    }

    /**
     * Perform the given search on the engine.
     *
     * @param  Builder  $builder
     * @return mixed
     */
    protected function performSearch(Builder $builder, array $options = [])
    {
        $index = $builder->index ?: $builder->model->searchableAs();

        $this->tnt->selectIndex("{$index}.index");

        if ($builder->callback) {
            return call_user_func($builder->callback, $this->tnt, $builder->query, $options);
        }

        return $this->tnt->search($builder->query, $builder->limit ?: static::LIMIT);
    }

    /**
     * Map the given results to instances of the given model.
     *
     * @param  \Laravel\Scout\Builder  $builder
     * @param  mixed  $results
     * @param  \Illuminate\Database\Eloquent\Model  $model
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function map(Builder $builder, $results, $model)
    {
        return (empty($results['ids'])) ? $model->newCollection() : $this->mapModels($builder, $results, $model);
    }

    /**
     * Map the given results to instances of the given model via a lazy collection.
     *
     * @param  \Laravel\Scout\Builder  $builder
     * @param  mixed  $results
     * @param  \Illuminate\Database\Eloquent\Model  $model
     * @return \Illuminate\Support\LazyCollection
     */
    public function lazyMap(Builder $builder, $results, $model)
    {
        return (empty($results['ids']))
            ? LazyCollection::make()
            : $this->mapModels($builder, $results, $model, function (Query $query) {
                return $query->cursor();
            });
    }

    /**
     * Map eloquent models with search results.
     *
     * @param  \Laravel\Scout\Builder  $builder
     * @param  mixed  $results
     * @param  \Illuminate\Database\Eloquent\Model  $model
     * @param  callable  $callback
     * @return \Illuminate\Database\Eloquent\Collection
     */
    protected function mapModels(Builder $builder, $results, $model, callable $callback = null)
    {
        $this->builder = $builder;

        $query = $model->whereIn($model->getQualifiedKeyName(), $results['ids']);

        if ($this->usesSoftDelete($model) && config('scout.soft_delete')) {
            $query = $this->handleSoftDelete($query);
        }

        $query = $this->applyWheres($query);

        $query = $this->applyOrders($query);

        $models = is_null($callback) ? $query->get() : $callback($query);

        return empty($this->builder->orders) ? $models->sortBy(function ($model) use ($results) {
            return array_search($model->getKey(), $results['ids']);
        })->values() : $models;
    }

    /**
     * Determine if the given model uses soft deletes.
     *
     * @param  \Illuminate\Database\Eloquent\Model  $model
     * @return bool
     */
    protected function usesSoftDelete($model)
    {
        return in_array(SoftDeletes::class, class_uses_recursive($model));
    }

    protected function handleSoftDelete(Query $query)
    {
        if (! array_key_exists('__soft_deleted', $this->builder->wheres)) {
            $query->withTrashed();
        } elseif ($this->builder->wheres['__soft_deleted'] == 1) {
            $query->onlyTrashed();
        }

        return $query;
    }

    protected function applyWheres(Query $query)
    {
        unset($this->builder->wheres['__soft_deleted']);

        return $query->where($this->builder->wheres);
    }

    protected function applyOrders(Query $query)
    {
        return array_reduce($this->builder->orders, function ($query, $order) {
            return $query->orderBy($order['column'], $order['direction']);
        }, $query);
    }

    /**
     * Pluck and return the primary keys of the given results.
     *
     * @param  mixed $results
     * @return \Illuminate\Support\Collection
     */
    public function mapIds($results)
    {
        return collect($results['ids'])->values();
    }

    /**
     * Get the total count from a raw result returned by the engine.
     *
     * @param  mixed  $results
     * @return int
     */
    public function getTotalCount($results)
    {
        return $results['hits'];
    }

    protected function initIndex($name)
    {
        if (! file_exists($this->indexPath($name))) {
            $this->createIndex($name);
        }

        $this->tnt->selectIndex($this->indexName($name));

        return $this->tnt->getIndex();
    }

    protected function indexName($name)
    {
        return "{$name}.index";
    }

    protected function indexPath($name)
    {
        return $this->tnt->config['storage'].$this->indexName($name);
    }

    public function createIndex($name, array $options = [])
    {
        if (! file_exists($this->tnt->config['storage'])) {
            mkdir($this->tnt->config['storage'], 0755, true);

            file_put_contents($this->tnt->config['storage'].'.gitignore', "*\n!.gitignore\n");
        }

        $this->tnt->createIndex($this->indexName($name), $options['disableOutput'] ?? false);
    }

    /**
     * Flush all of the model's records from the engine.
     *
     * @param  \Illuminate\Database\Eloquent\Model  $model
     * @return void
     */
    public function flush($model)
    {
        $this->deleteIndex($model->searchableAs());
    }
}
