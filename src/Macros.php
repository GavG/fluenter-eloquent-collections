<?php

declare(strict_types=1);

namespace GavG\FluenterEloquentCollections;

use Illuminate\Database\Eloquent\Collection as EloquentCollection;

class Macros
{
    public static function register(): void
    {
        self::registerPluckRelationMacro();
        self::registerUpdateMacro();
    }

    private static function registerPluckRelationMacro(): void
    {
        EloquentCollection::macro(
            name: 'pluckRelation',
            macro: function (string $relation): EloquentCollection {
            /** @var EloquentCollection $this */

            $results = [];

            foreach ($this->items as $model) {
                /** @var \Illuminate\Database\Eloquent\Model $model */
                if (! $model->relationLoaded($relation)) {
                    continue;
                }

                $related = $model->getRelation($relation);

                if ($related instanceof EloquentCollection) {
                    foreach ($related->items as $item) {
                        $results[$item->getKey()] = $item;
                    }
                } else {
                    $results[$related->getKey()] = $related;
                }
            }

            return empty($results)
                ? new EloquentCollection()
                : array_first($results)->newCollection()->make($results);
        });
    }

    private static function registerUpdateMacro(): void
    {
        /**
         * Update the collection of models silently without firing any model events.
         *
         * @param  array  $attributes
         * @return int The number of affected rows.
         */
        EloquentCollection::macro(
            name: 'update',
            macro: function (array $attributes) {
            /** @var EloquentCollection $this */
            if ($this->isEmpty()) {
                return 0;
            }

            $affectedRows = $this->toQuery()->update($attributes);

            // Sync memory state for consistency
            $this->each(function ($model) use ($attributes) {
                /** @var \Illuminate\Database\Eloquent\Model $model */
                $model->fill($attributes)->syncChanges()->syncOriginal();
            });

            return $affectedRows;
        });
    }
}
