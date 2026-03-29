<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\City;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class CityApiController extends Controller
{
    public function index()
    {
        return response()->json(City::all());
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
        ]);

        $name = trim($request->name);
        $slug = Str::slug($name);

        $city = City::firstOrCreate(
            ['slug' => $slug],
            ['name' => $name]
        );

        return response()->json($city, 201);
    }
}
