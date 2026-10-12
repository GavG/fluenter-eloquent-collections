# Fluenter Eloquent Collections

Fluenter Eloquent Collections adds convenient relationship access and bulk persistence methods to Laravel Eloquent collections. Opt your models in with Laravel's `CollectedBy` attribute, then work with related models and updates directly from the collection.

## Requirements

- PHP 8.2 or later
- Laravel 11 or 12 (`illuminate/database` and `illuminate/support`)

## Installation

```bash
composer require gavg/fluenter-eloquent-collections
```

## Enable the collection

Add the `CollectedBy` attribute to each model whose query results should use `FluenterCollection`:

```php
<?php

namespace App\Models;

use GavG\FluenterEloquentCollections\FluenterCollection;
use Illuminate\Database\Eloquent\Attributes\CollectedBy;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[CollectedBy(FluenterCollection::class)]
class Post extends Model
{
    public function author(): BelongsTo
    {
        return $this->belongsTo(Author::class);
    }
}
```

Apply the attribute to any related model whose own query results should also have fluent relation access.

## Access loaded relationships

Use `pluckRelation()` to gather a loaded relationship from the models in a collection:

```php
$posts = Post::with('author')->get();
$authors = $posts->pluckRelation('author');
```

For to-one relationships, the result contains the related models. For to-many relationships, it flattens the related collections. Related models are de-duplicated by their primary key, and the result uses the related model's configured collection class when available.

Only already-loaded relationships are included. Unloaded relationships are skipped; `pluckRelation()` does not lazy-load them. Eager-load the relationship first, for example with `with('author')`.

Relationship collections can also be accessed as properties. The relation's name and its plural form are recognized:

```php
$authors = $posts->author;
$authors = $posts->authors;
```

This works across chained collections too:

```php
$authors = Tag::with('posts.author')->get()->posts->authors;
```

Property access uses the same loaded-relation behavior as `pluckRelation()`.

## Save changes across a collection

Modify models and call `bulkSaveDirty()` to persist dirty models together:

```php
$posts = Post::all();

$posts->each(function (Post $post) {
    $post->published = true;
});

$posts->bulkSaveDirty();
```

An optional callback can make the changes as part of the bulkSaveDirty operation:

```php
$posts->bulkSaveDirty(function (Post $post) {
    $post->published = true;
});
```

Use `update()` to fill each model with the given attributes and persist the dirty models:

```php
$posts->update(['published' => true]);
```

Both methods return the same collection. Only existing models with dirty attributes are included in the database write. New models are left untouched. Changed model state is synchronized so Eloquent's `wasChanged()` can report the saved attributes.

### Bulk persistence behavior

Existing models are updated together with one parameterized `UPDATE` statement that writes their dirty attributes. New models are not inserted. These methods do not call each model's individual `save()` method, so per-model saving and saved events are not dispatched. Use individual model saves when inserting models or when lifecycle events and per-model persistence hooks are required. Do not dirty virtual or non-writable attributes before calling `save()` or `update()`.

## Development

Run the test suite with:

```bash
./dev/test.sh
```