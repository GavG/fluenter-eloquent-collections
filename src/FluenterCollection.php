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
}
