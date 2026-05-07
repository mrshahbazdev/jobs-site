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
        $host     = parse_url(config('app.url'), PHP_URL_HOST);

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
     */
    public static function submitBatch(array $urls): bool
    {
        if (! config('indexnow.enabled') || empty($urls)) {
            return false;
        }

        $key      = config('indexnow.key');
        $endpoint = config('indexnow.endpoint');
        $host     = parse_url(config('app.url'), PHP_URL_HOST);

        try {
            $response = Http::timeout(30)->post($endpoint, [
                'host'        => $host,
                'key'         => $key,
                'keyLocation' => url("/{$key}.txt"),
                'urlList'     => array_values($urls),
            ]);

            if ($response->successful()) {
                Log::info('IndexNow: batch submitted', ['count' => count($urls)]);
                return true;
            }

            Log::warning('IndexNow: batch submission failed', [
                'status' => $response->status(),
                'body'   => $response->body(),
                'count'  => count($urls),
            ]);
        } catch (\Exception $e) {
            Log::error('IndexNow: batch request error', [
                'message' => $e->getMessage(),
                'count'   => count($urls),
            ]);
        }

        return false;
    }
}
