<?php

namespace App\Observers;

use App\Models\Post;
use App\Services\JobPosterService;

class PostObserver
{
    public function saved(Post $post): void
    {
        $watch = ['title', 'slug'];
        if (! $post->poster_path || $post->wasChanged($watch) || $post->wasRecentlyCreated) {
            $path = app(JobPosterService::class)->generateForPost($post);
            $post->updateQuietly(['poster_path' => $path]);
        }
    }
}
