<?php

namespace App\Http\Controllers;

use App\Models\Comment;
use App\Models\JobListing;
use Illuminate\Http\Request;

class CommentController extends Controller
{
    private const SPAM_WORDS = [
        'viagra', 'casino', 'lottery', 'click here', 'buy now',
        'free money', 'earn money', 'make money online', 'crypto',
        'bitcoin', 'investment opportunity', 'work from home earn',
    ];

    public function store(Request $request, $jobSlug)
    {
        $request->validate([
            'body' => 'required|string|min:3|max:1000',
            'hp_field' => 'size:0',
        ]);

        $job = JobListing::where('slug', $jobSlug)->firstOrFail();

        $body = strip_tags($request->body);

        $lower = strtolower($body);
        foreach (self::SPAM_WORDS as $word) {
            if (str_contains($lower, $word)) {
                return back()->with('comment_error', 'Your comment could not be posted.');
            }
        }

        if (preg_match_all('/https?:\/\//', $body) > 2) {
            return back()->with('comment_error', 'Too many links in your comment.');
        }

        Comment::create([
            'user_id' => $request->user()->id,
            'job_listing_id' => $job->id,
            'body' => $body,
            'is_approved' => false,
        ]);

        return back()->with('comment_success', 'Your comment has been submitted and is pending approval.');
    }
}
