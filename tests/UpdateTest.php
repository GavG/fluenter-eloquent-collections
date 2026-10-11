<?php

declare(strict_types=1);

use GavG\FluenterEloquentCollections\FluenterCollection;
use GavG\FluenterEloquentCollections\Tests\Fixtures\Author;
use GavG\FluenterEloquentCollections\Tests\Fixtures\Post;

it('updates models in the collection', function () {
    $a = Author::create(['name' => 'A']);
    Post::create(['title' => 'Post 1']);
    Post::create(['title' => 'Post 2']);

    $posts = Post::all();

    $saved = $posts->update(['author_id' => $a->id]);

    expect($saved)->toBeInstanceOf(FluenterCollection::class)
        ->and($saved->every(fn ($post) => $post->isClean() && $post->wasChanged('author_id')))->toBeTrue();

    $posts = Post::all();

    expect($posts->every(fn ($post) => $post->author_id === $a->id))->toBeTrue();
});
