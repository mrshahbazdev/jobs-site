<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\LandingLink;
use App\Models\LandingGroup;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class CategoryApiController extends Controller
{
    public function index()
    {
        return response()->json(Category::select('id', 'name', 'slug', 'icon_name')->get());
    }

    /**
     * Store category and optionally link to a landing group for homepage visibility.
     */
    public function store(Request $request)
    {
        $request->validate([
            'name'             => 'required|string|max:255',
            'landing_group_id' => 'nullable|exists:landing_groups,id'
        ]);

        $name = trim($request->name);
        $slug = Str::slug($name);

        $category = Category::firstOrCreate(
            ['slug' => $slug],
            [
                'name'      => $name,
                'icon_name' => 'work', // Material Symbol name
            ]
        );

        // Auto-Link to Landing Group if provided
        if ($request->landing_group_id) {
            LandingLink::firstOrCreate(
                [
                    'landing_group_id' => $request->landing_group_id,
                    'route_param'      => $category->slug,
                ],
                [
                    'title'      => $category->name,
                    'route_name' => 'category.show',
                    'is_active'  => true,
                    'sort_order' => 0,
                    'icon'       => 'work' // Ensure link also has the Material icon
                ]
            );
        }

        return response()->json($category, 201);
    }

    /**
     * Resolve category from arbitrary title (AI driven).
     */
    public function resolve(Request $request)
    {
        $request->validate(['title' => 'required|string']);

        $title = strtolower($request->title);
        $categories = Category::all();
        $bestMatch = null;

        foreach ($categories as $category) {
            if (str_contains($title, strtolower($category->name)) || str_contains($title, strtolower(str_replace('-', ' ', $category->slug)))) {
                $bestMatch = $category;
                break;
            }
        }

        if (!$bestMatch) {
            return response()->json(['message' => 'No matching category found.'], 404);
        }

        // Try to find existing link via category slug (route_param)
        $landingLink = LandingLink::where('route_param', $bestMatch->slug)
            ->with('group')
            ->first();

        // If no group is linked, we return the category but suggest creating a group link in test.html
        return response()->json([
            'category' => [
                'id'   => $bestMatch->id,
                'name' => $bestMatch->name,
                'slug' => $bestMatch->slug,
            ],
            'group' => $landingLink && $landingLink->group ? [
                'id'   => $landingLink->group->id,
                'name' => $landingLink->group->name,
            ] : null
        ]);
    }
}
