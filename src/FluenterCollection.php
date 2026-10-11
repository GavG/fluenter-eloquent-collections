<?php

declare(strict_types=1);

namespace GavG\FluenterEloquentCollections;

use Illuminate\Support\Str;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;

class FluenterCollection extends Collection
{
    public function __get($key)
    {
        if ($this->first()?->isRelation($key)) {
            return $this->pluckRelation($key);
        }

        $singular = Str::singular($key);

        if ($this->first()?->isRelation($singular)) {
            return $this->pluckRelation($singular);
        }

        return parent::__get($key);
    }

    public function pluckRelation(string $relation)
    {
        $results = [];

        foreach ($this as $model) {
            if (! $model->relationLoaded($relation)) {
                continue;
            }

            $related = $model->getRelation($relation);

            if ($related instanceof self) {
                foreach ($related as $item) {
                    $results[$item->getKey()] = $item;
                }

                continue;
            }

            if ($related instanceof Model) {
                $results[$related->getKey()] = $related;
            }
        }

        return array_first($results)?->newCollection($results) ?? new static([]);
    }

    public function save(?callable $callback = null): static
    {
        return $this->saveWithModelCallback($callback);
    }

    protected function saveWithModelCallback(?callable $model_callback = null): static
    {
        $updates = [];
        $columns = [];

        foreach ($this as $model) {
            if ($model_callback) {
                $model_callback($model);
            }

            if (! $model->exists || ! $model->isDirty()) {
                continue;
            }

            if ($model->usesTimestamps()) {
                $model->updateTimestamps();
            }

            $updates[] = [
                'model' => $model,
                'key' => $model->getRawOriginal($model->getKeyName()) ?? $model->getKey(),
                'attributes' => $model->getDirty(),
            ];

            foreach (array_keys($model->getDirty()) as $column) {
                $columns[$column] = true;
            }

            $model->syncChanges()->syncOriginal();
        }

        if ($updates === []) {
            return $this;
        }

        BulkUpdateQuery::execute(
            model: array_first($updates)['model'],
            updates: $updates,
            columns: $columns,
        );

        return $this;
    }

    public function update(array $attributes): static
    {
        return $this->saveWithModelCallback(
            model_callback: fn ($model) => $model->fill($attributes),
        );
    }
}
