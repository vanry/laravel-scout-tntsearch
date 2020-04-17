<?php

namespace Vanry\Scout\Engines;

use Laravel\Scout\Builder;
use TeamTNT\TNTSearch\TNTSearch;
use Laravel\Scout\Engines\Engine;
use Illuminate\Support\Facades\DB;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Builder as Query;
use TeamTNT\TNTSearch\Exceptions\IndexNotFoundException;

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
     * @param  \Illuminate\Database\Eloquent\Collection  $models
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
     * @param  \Laravel\Scout\Builder $builder
     * @param  mixed $results
     * @param  \Illuminate\Database\Eloquent\Model $model
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function map(Builder $builder, $results, $model)
    {
        return (is_null($results['ids']) || count($results['ids']) === 0)
            ? $model->newCollection()
            : $this->mapModels($builder, $results, $model);
    }

    /**
     * Map eloquent models with search results.
     *
     * @param  \Laravel\Scout\Builder $builder
     * @param  mixed $results
     * @param  \Illuminate\Database\Eloquent\Model $model
     * @return \Illuminate\Database\Eloquent\Collection
     */
    protected function mapModels(Builder $builder, $results, $model)
    {
        $this->builder = $builder;

        $query = $model->whereIn(
            $model->getQualifiedKeyName(),
            collect($results['ids'])->values()->all()
        );

        if ($this->usesSoftDelete($model) && config('scout.soft_delete')) {
            $query = $this->handleSoftDelete($query);
        }

        $query = $this->applyWheres($query);

        $query = $this->applyOrders($query, $results['ids']);

        return $query->get();
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

    protected function applyOrders(Query $query, array $ids)
    {
        if (empty($this->builder->orders)) {
            return $query->orderByRaw(
                sprintf('field(%s%s,%s)',
                    DB::getTablePrefix(),
                    $query->getModel()->getQualifiedKeyName(),
                    implode(',', $ids)
                )
            );
        }

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

    /**
     * Flush all of the model's records from the engine.
     *
     * @param  \Illuminate\Database\Eloquent\Model  $model
     * @return void
     */
    public function flush($model)
    {
        $indexPath = $this->indexPath($model);

        if (file_exists($indexPath)) {
            unlink($indexPath);
        }
    }
}
