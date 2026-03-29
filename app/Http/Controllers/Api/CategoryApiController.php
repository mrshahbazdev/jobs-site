<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Category;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class CategoryApiController extends Controller
{
    public function index()
    {
        return response()->json(Category::select('id', 'name', 'slug', 'icon_name')->get());
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
        ]);

        $name = trim($request->name);
        $slug = Str::slug($name);

        $category = Category::firstOrCreate(
            ['slug' => $slug],
            [
                'name' => $name,
                'icon_name' => 'heroicon-o-briefcase', // Default icon
            ]
        );

        return response()->json($category, 201);
    }

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

        // Find parent group
        $landingLink = \App\Models\LandingLink::where('url', 'LIKE', '%' . $bestMatch->slug . '%')->with('group')->first();

        return response()->json([
            'category' => [
                'id' => $bestMatch->id,
                'name' => $bestMatch->name,
                'slug' => $bestMatch->slug,
            ],
            'group' => $landingLink ? [
                'id' => $landingLink->group->id,
                'name' => $landingLink->group->name,
            ] : null
        ]);
    }
}
