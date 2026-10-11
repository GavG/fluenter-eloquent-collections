<?php

declare(strict_types=1);

namespace GavG\FluenterEloquentCollections\Tests\Fixtures;

use GavG\FluenterEloquentCollections\Tests\Fixtures\TagCollection;
use Illuminate\Database\Eloquent\Attributes\CollectedBy;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

#[CollectedBy(TagCollection::class)]
class Tag extends Model
{
    protected $guarded = [];

    public function posts(): BelongsToMany
    {
        return $this->belongsToMany(Post::class);
    }
}
