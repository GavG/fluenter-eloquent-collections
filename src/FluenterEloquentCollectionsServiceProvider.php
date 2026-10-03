<?php

declare(strict_types=1);

namespace GavG\FluenterEloquentCollections;

use Illuminate\Support\ServiceProvider;

class FluenterEloquentCollectionsServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        Macros::register();
    }
}
