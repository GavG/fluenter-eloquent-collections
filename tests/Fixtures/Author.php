<?php

declare(strict_types=1);

namespace GavG\FluenterEloquentCollections\Tests\Fixtures;

use GavG\FluenterEloquentCollections\FluenterCollection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Attributes\CollectedBy;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[CollectedBy(FluenterCollection::class)]
class Author extends Model
{
    protected $guarded = [];

    public function posts(): HasMany
    {
        return $this->hasMany(Post::class);
    }
}
