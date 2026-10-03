<?php

declare(strict_types=1);

namespace GavG\FluenterEloquentCollections\Tests\Fixtures;

use Illuminate\Database\Eloquent\Attributes\CollectedBy;
use Illuminate\Database\Eloquent\Model;

#[CollectedBy(TagCollection::class)]
class Tag extends Model
{
    protected $guarded = [];
}
