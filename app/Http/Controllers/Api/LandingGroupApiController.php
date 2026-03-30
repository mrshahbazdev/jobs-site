<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\LandingGroup;
use Illuminate\Http\Request;

class LandingGroupApiController extends Controller
{
    /**
     * Display a listing of active groups and their links.
     */
    public function index()
    {
        try {
            $groups = LandingGroup::active()
                ->ordered()
                ->with(['links' => function ($q) {
                    $q->active()->ordered()->select('id', 'landing_group_id', 'title', 'route_param', 'route_name', 'sort_order');
                }])
                ->select('id', 'name', 'icon', 'sort_order')
                ->get();
        } catch (\Exception $e) {
            // Fallback: return groups without links if schema mismatch
            $groups = LandingGroup::active()->ordered()->select('id', 'name', 'icon', 'sort_order')->get();
        }

        return response()->json($groups);
    }

    /**
     * Store a new landing group (auto-create if not exists).
     */
    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
        ]);

        $group = LandingGroup::firstOrCreate(
            ['name' => trim($request->name)],
            [
                'is_active'    => true,
                'sort_order'   => 0,
                'section_type' => 'grid',
            ]
        );

        return response()->json($group, $group->wasRecentlyCreated ? 201 : 200);
    }
}
