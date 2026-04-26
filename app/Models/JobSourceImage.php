<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class JobSourceImage extends Model
{
    protected $fillable = [
        'title', 'source_page_url', 'source_image_url',
        'local_image_path', 'is_processed', 'article_text',
        'publish_status', 'published_job_id', 'published_at',
    ];

    protected function casts(): array
    {
        return [
            'is_processed' => 'boolean',
            'published_at' => 'datetime',
        ];
    }

    public function jobListing()
    {
        return $this->hasOne(JobListing::class, 'job_source_image_id');
    }

    public function scopePending($query)
    {
        return $query->where('publish_status', 'pending');
    }

    public function scopePublished($query)
    {
        return $query->where('publish_status', 'published');
    }

    public function scopeWithImage($query)
    {
        return $query->whereNotNull('local_image_path')->where('local_image_path', '!=', '');
    }
}
