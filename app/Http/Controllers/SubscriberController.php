<?php

namespace App\Http\Controllers;

use App\Models\Subscriber;
use Illuminate\Http\Request;

class SubscriberController extends Controller
{
    public function subscribe(Request $request)
    {
        $request->validate([
            'email_or_whatsapp' => 'required',
            'category_id' => 'nullable|exists:categories,id',
            'city_id' => 'nullable|exists:cities,id',
        ]);

        Subscriber::updateOrCreate(
            [
                'email_or_whatsapp' => $request->email_or_whatsapp,
                'category_id' => $request->category_id,
                'city_id' => $request->city_id,
            ],
            [
                'is_active' => true,
                'name' => $request->name ?? null,
            ]
        );

        return redirect()->back()->with('success', 'You have successfully subscribed to job alerts!');
    }

    public function store(Request $request)
    {
        $request->validate([
            'email_or_phone' => 'required|string|max:255',
        ]);

        Subscriber::updateOrCreate(
            ['email_or_whatsapp' => $request->email_or_phone],
            ['is_active' => true]
        );

        return back()->with('success', 'Thank you for subscribing! We will keep you updated.');
    }
}
