<?php

declare(strict_types=1);

use GavG\FluenterEloquentCollections\Tests\Fixtures\Author;
use GavG\FluenterEloquentCollections\Tests\Fixtures\Post;
use GavG\FluenterEloquentCollections\Tests\Fixtures\Tag;
use GavG\FluenterEloquentCollections\Tests\Fixtures\TagCollection;
use Illuminate\Database\Eloquent\Collection;

it('plucks a loaded to-one relation as a unique collection of models', function () {
    $a = Author::create(['name' => 'A']);
    $b = Author::create(['name' => 'B']);
    Post::create(['title' => '1', 'author_id' => $a->id]);
    Post::create(['title' => '2', 'author_id' => $a->id]);
    Post::create(['title' => '3', 'author_id' => $b->id]);

    $authors = Post::with('author')->get()->pluckRelation('author');

    expect($authors)->toBeInstanceOf(Collection::class)
        ->and($authors)->toHaveCount(2)
        ->and($authors->every(fn ($m) => $m instanceof Author))->toBeTrue()
        ->and($authors->modelKeys())->toEqualCanonicalizing([$a->id, $b->id]);
});

it('flattens and de-duplicates a loaded to-many relation', function () {
    $t1 = Tag::create(['name' => 'x']);
    $t2 = Tag::create(['name' => 'y']);
    $t3 = Tag::create(['name' => 'z']);
    $p1 = Post::create(['title' => '1']);
    $p2 = Post::create(['title' => '2']);
    $p1->tags()->attach([$t1->id, $t2->id]);
    $p2->tags()->attach([$t2->id, $t3->id]);

    $tags = Post::with('tags')->get()->pluckRelation('tags');

    expect($tags)->toHaveCount(3)
        ->and($tags->modelKeys())->toEqualCanonicalizing([$t1->id, $t2->id, $t3->id]);
});

it('skips models whose relation is not loaded', function () {
    $a = Author::create(['name' => 'A']);
    Post::create(['title' => '1', 'author_id' => $a->id]);

    expect(Post::all()->pluckRelation('author'))->toBeEmpty();
});

it('returns an empty eloquent collection for an empty collection', function () {
    $result = (new Collection())->pluckRelation('author');

    expect($result)->toBeInstanceOf(Collection::class)->toBeEmpty();
});

it('returns an empty collection when to-many relations are empty', function () {
    Post::create(['title' => '1']);

    expect(Post::with('tags')->get()->pluckRelation('tags'))->toBeEmpty();
});

it('only includes models from loaded relations when mixed', function () {
    $a = Author::create(['name' => 'A']);
    Post::create(['title' => '1', 'author_id' => $a->id]);
    $loaded = Post::with('author')->first();
    $unloaded = Post::first();

    $result = (new Collection([$loaded, $unloaded]))->pluckRelation('author');

    expect($result)->toHaveCount(1);
});

it('uses the related model custom collection class', function () {
    $t1 = Tag::create(['name' => 'x']);
    $t2 = Tag::create(['name' => 'y']);
    $p1 = Post::create(['title' => '1']);
    $p2 = Post::create(['title' => '2']);
    $p1->tags()->attach([$t1->id, $t2->id]);
    $p2->tags()->attach([$t2->id]);

    $tags = Post::with('tags')->get()->pluckRelation('tags');

    expect($tags)->toBeInstanceOf(TagCollection::class)
        ->and($tags)->toHaveCount(2);
});
