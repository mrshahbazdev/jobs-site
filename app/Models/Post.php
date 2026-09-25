<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Post extends Model
{
    protected $fillable = ['title', 'slug', 'image', 'meta_description', 'poster_path', 'content', 'is_published'];
}
