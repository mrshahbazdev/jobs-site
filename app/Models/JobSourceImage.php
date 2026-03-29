<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class JobSourceImage extends Model
{
    protected $fillable = [
        'title', 'source_page_url', 'source_image_url', 
        'local_image_path', 'is_processed', 'article_text'
    ];

    public function jobListing()
    {
        return $this->hasOne(JobListing::class, 'job_source_image_id');
    }
}
