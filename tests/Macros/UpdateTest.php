<?php

declare(strict_types=1);

use GavG\FluenterEloquentCollections\Tests\Fixtures\Post;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;

beforeEach(function () {
    Post::create(['title' => 'a', 'status' => 'draft']);
    Post::create(['title' => 'b', 'status' => 'draft']);
    Post::create(['title' => 'c', 'status' => 'draft']);
});

describe('update', function () {
    it('updates all models in the collection and returns affected rows', function () {
        $posts = Post::whereIn('title', ['a', 'b'])->get();

        expect($posts->update(['status' => 'published']))->toBe(2);

        expect(Post::where('status', 'published')->pluck('title')->all())->toEqualCanonicalizing(['a', 'b'])
            ->and(Post::where('title', 'c')->first()->status)->toBe('draft');
    });

    it('returns 0 for an empty collection', function () {
        expect((new Collection())->update(['status' => 'x']))->toBe(0);
    });

    it('does not fire model events', function () {
        $fired = 0;
        Post::updating(function () use (&$fired) { $fired++; });
        Post::updated(function () use (&$fired) { $fired++; });
        Post::saving(function () use (&$fired) { $fired++; });
        Post::saved(function () use (&$fired) { $fired++; });

        Post::all()->update(['status' => 'archived']);

        expect($fired)->toBe(0);
    });

    it('syncs in-memory models', function () {
        $posts = Post::all();
        $posts->update(['status' => 'archived']);

        expect($posts->every(fn ($p) => $p->status === 'archived' && ! $p->isDirty()))->toBeTrue();
    });

    it('issues a single update query', function () {
        $posts = Post::all();
        $queries = 0;
        DB::listen(function ($q) use (&$queries) {
            if (str_starts_with(strtolower($q->sql), 'update')) {
                $queries++;
            }
        });

        $posts->update(['status' => 'published']);

        expect($queries)->toBe(1);
    });
});
