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

    public function save(): static
    {
        return $this->saveWithModelCallback();
    }

    protected function saveWithModelCallback(callable|null $model_callback = null): static
    {
        /** @var array<int, array<int, Model>> $changed_records */
        $changed_records = [];
        $changed_fields = [];

        foreach ($this as $model) {
            if ($model_callback) {
                $model_callback($model);
            }

            if ($model->isDirty()) {
                $dirty = $model->getDirty();
                $changed_fields += array_fill_keys(array_keys($dirty), true);
                $changed_records[$model->getKey()] = $model->getAttributes();
                $model->syncChanges()->syncOriginal();
            }
        }

        if (!empty($changed_records)) {
            $model = array_first($this->items);

            $model->newModelQuery()->upsert(
                values: $changed_records,
                uniqueBy: $model->getKeyName(),
                update: array_keys($changed_fields),
            );
        }

        return $this;
    }

    public function update(array $attributes): static
    {
        return $this->saveWithModelCallback(
            model_callback: fn ($model) => $model->fill($attributes),
        );
    }
}
