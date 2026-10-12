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

    $saved = $posts->bulkSaveDirty();

    expect($saved)->toBeInstanceOf(FluenterCollection::class)
        ->and($saved->every(
            fn ($post) => $post->isClean() && $post->wasChanged('author_id'),
        ))->toBeTrue();

    $posts = Post::all();

    expect($posts->every(fn ($post) => $post->author_id === $a->id))->toBeTrue();
});

it('updates rows with different dirty fields without overwriting other values', function () {
    Post::create(['title' => 'First', 'status' => 'draft']);
    Post::create(['title' => 'Second', 'status' => 'draft']);

    $posts = Post::all();
    $posts[0]->title = 'Updated first';
    $posts[1]->status = 'published';

    $posts->bulkSaveDirty();

    $stored = Post::orderBy('id')->get();

    expect($stored[0]->title)->toBe('Updated first')
        ->and($stored[0]->status)->toBe('draft')
        ->and($stored[1]->title)->toBe('Second')
        ->and($stored[1]->status)->toBe('published')
        ->and($posts[0]->wasChanged('title'))->toBeTrue()
        ->and($posts[1]->wasChanged('status'))->toBeTrue();
});

    it('does not insert new models', function () {
    $posts = new FluenterCollection([
        new Post(['title' => 'First new post']),
        new Post(['title' => 'Second new post', 'status' => 'published']),
    ]);

    $posts->bulkSaveDirty();

    expect($posts->every(fn ($post) => ! $post->exists && $post->isDirty()))->toBeTrue()
        ->and(Post::query()->count())->toBe(0);
});
