<?php

namespace App\Http\Controllers;

use App\Models\JobListing;
use App\Models\Bookmark;
use Illuminate\Http\Request;

class BookmarkController extends Controller
{
    public function index()
    {
        $bookmarks = auth()->user()->bookmarkedJobs()->latest()->paginate(15);
        $categories = \App\Models\Category::all();
        $cities = \App\Models\City::all();
        
        return view('bookmarks.index', compact('bookmarks', 'categories', 'cities'));
    }

    public function toggle(JobListing $job)
    {
        $user = auth()->user();
        
        $bookmark = Bookmark::where('user_id', $user->id)
            ->where('job_listing_id', $job->id)
            ->first();

        if ($bookmark) {
            $bookmark->delete();
            return response()->json(['status' => 'removed', 'message' => 'Bookmark removed']);
        }

        Bookmark::create([
            'user_id' => $user->id,
            'job_listing_id' => $job->id
        ]);

        return response()->json(['status' => 'added', 'message' => 'Bookmark added']);
    }
}
