<?php

namespace App\Http\Controllers;

use App\Models\PushSubscription;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PushController extends Controller
{
    public function publicKey(): JsonResponse
    {
        $key = config('services.webpush.vapid.public_key');

        if (empty($key)) {
            return response()->json([
                'error' => 'VAPID public key is not configured.',
            ], 503);
        }

        return response()->json(['publicKey' => $key]);
    }

    public function subscribe(Request $request): JsonResponse
    {
        $data = $request->validate([
            'endpoint' => ['required', 'url', 'max:2000'],
            'keys.p256dh' => ['required', 'string', 'max:255'],
            'keys.auth' => ['required', 'string', 'max:255'],
            'contentEncoding' => ['nullable', 'string', 'max:32'],
            'category_id' => ['nullable', 'integer', 'exists:categories,id'],
            'city_id' => ['nullable', 'integer', 'exists:cities,id'],
        ]);

        $sub = PushSubscription::updateOrCreate(
            ['endpoint_hash' => PushSubscription::hashEndpoint($data['endpoint'])],
            [
                'endpoint' => $data['endpoint'],
                'p256dh' => $data['keys']['p256dh'],
                'auth' => $data['keys']['auth'],
                'content_encoding' => $data['contentEncoding'] ?? null,
                'user_agent' => substr((string) $request->userAgent(), 0, 500),
                'user_id' => $request->user()?->id,
                'category_id' => $data['category_id'] ?? null,
                'city_id' => $data['city_id'] ?? null,
                'failure_count' => 0,
            ]
        );

        return response()->json(['id' => $sub->id, 'status' => 'subscribed']);
    }

    public function unsubscribe(Request $request): JsonResponse
    {
        $data = $request->validate([
            'endpoint' => ['required', 'url', 'max:2000'],
        ]);

        PushSubscription::where('endpoint_hash', PushSubscription::hashEndpoint($data['endpoint']))->delete();

        return response()->json(['status' => 'unsubscribed']);
    }
}
