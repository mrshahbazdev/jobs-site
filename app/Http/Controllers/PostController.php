<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

use App\Models\Post;

class PostController extends Controller
{
    public function index()
    {
        $posts = Post::where('is_published', true)->latest()->paginate(12);
        return view('blog.index', compact('posts'));
    }

    public function show($slug)
    {
        $post = Post::where('slug', $slug)->where('is_published', true)->firstOrFail();
        return view('blog.show', compact('post'));
    }
}
