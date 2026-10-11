<?php

declare(strict_types=1);

use GavG\FluenterEloquentCollections\FluenterCollection;
use GavG\FluenterEloquentCollections\Tests\Fixtures\Author;
use GavG\FluenterEloquentCollections\Tests\Fixtures\Post;

it('saves dirty models in the collection', function () {
    $a = Author::create(['name' => 'A']);
    Post::create(['title' => 'Post 1']);
    Post::create(['title' => 'Post 2']);

    $posts = Post::all();

    $posts->each(function ($post) use ($a) {
        $post->author_id = $a->id;
    });

    $saved = $posts->save();

    expect($saved)->toBeInstanceOf(FluenterCollection::class)
        ->and($saved->every(
            fn ($post) => $post->isClean() && $post->wasChanged('author_id'),
        ))->toBeTrue();

    $posts = Post::all();

    expect($posts->every(fn ($post) => $post->author_id === $a->id))->toBeTrue();
});
