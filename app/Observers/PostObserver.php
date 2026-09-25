<?php

namespace App\Observers;

use App\Models\Post;
use App\Services\JobPosterService;
use Illuminate\Support\Facades\Log;

class PostObserver
{
    public function saved(Post $post): void
    {
        $watch = ['title', 'slug'];
        if (! $post->poster_path || $post->wasChanged($watch) || $post->wasRecentlyCreated) {
            try {
                $path = app(JobPosterService::class)->generateForPost($post);
                $post->updateQuietly(['poster_path' => $path]);
            } catch (\Throwable $e) {
                Log::error('Post poster generation failed', [
                    'post_id' => $post->id,
                    'msg' => $e->getMessage(),
                    'at' => $e->getFile().':'.$e->getLine(),
                ]);
            }
        }
    }
}
