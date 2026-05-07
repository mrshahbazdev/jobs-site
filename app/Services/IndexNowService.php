<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class IndexNowService
{
    /**
     * Submit a single URL to IndexNow.
     */
    public static function submit(string $url): bool
    {
        if (! config('indexnow.enabled')) {
            return false;
        }

        $key      = config('indexnow.key');
        $endpoint = config('indexnow.endpoint');

        try {
            $response = Http::timeout(10)->get($endpoint, [
                'url' => $url,
                'key' => $key,
            ]);

            if ($response->successful()) {
                Log::info('IndexNow: URL submitted', ['url' => $url]);
                return true;
            }

            Log::warning('IndexNow: submission failed', [
                'url'    => $url,
                'status' => $response->status(),
                'body'   => $response->body(),
            ]);
        } catch (\Exception $e) {
            Log::error('IndexNow: request error', [
                'url'     => $url,
                'message' => $e->getMessage(),
            ]);
        }

        return false;
    }

    /**
     * Submit multiple URLs to IndexNow in a single batch request.
     *
     * @return array{ok: bool, status: int|null, error: string|null}
     */
    public static function submitBatch(array $urls): array
    {
        if (! config('indexnow.enabled') || empty($urls)) {
            return ['ok' => false, 'status' => null, 'error' => 'Disabled or empty URL list'];
        }

        $key      = config('indexnow.key');
        $endpoint = config('indexnow.endpoint');
        $host     = parse_url(config('app.url'), PHP_URL_HOST);

        $payload = [
            'host'        => $host,
            'key'         => $key,
            'keyLocation' => url("/{$key}.txt"),
            'urlList'     => array_values($urls),
        ];

        try {
            $response = Http::timeout(30)
                ->withHeaders(['Content-Type' => 'application/json; charset=utf-8'])
                ->post($endpoint, $payload);

            if ($response->successful()) {
                Log::info('IndexNow: batch submitted', ['count' => count($urls)]);
                return ['ok' => true, 'status' => $response->status(), 'error' => null];
            }

            $error = "HTTP {$response->status()}: {$response->body()}";
            Log::warning('IndexNow: batch submission failed', [
                'status' => $response->status(),
                'body'   => $response->body(),
                'count'  => count($urls),
                'host'   => $host,
            ]);

            return ['ok' => false, 'status' => $response->status(), 'error' => $error];
        } catch (\Exception $e) {
            Log::error('IndexNow: batch request error', [
                'message' => $e->getMessage(),
                'count'   => count($urls),
            ]);

            return ['ok' => false, 'status' => null, 'error' => $e->getMessage()];
        }
    }
}
