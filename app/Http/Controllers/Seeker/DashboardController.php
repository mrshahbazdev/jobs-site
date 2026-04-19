<?php

namespace App\Http\Controllers\Seeker;

use App\Http\Controllers\Controller;
use App\Models\Bookmark;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function __invoke(Request $request): View
    {
        $user = $request->user();

        $bookmarkCount = Bookmark::where('user_id', $user->id)->count();

        $user->profile_completion_percent = $user->recomputeProfileCompletion();
        $user->save();

        return view('seeker.dashboard', [
            'user' => $user,
            'bookmarkCount' => $bookmarkCount,
        ]);
    }
}
