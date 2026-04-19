<?php

namespace App\Services;

use App\Models\PushSubscription;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;
use Minishlink\WebPush\Subscription;
use Minishlink\WebPush\WebPush;

class WebPushService
{
    private ?WebPush $client = null;

    private function client(): WebPush
    {
        if ($this->client !== null) {
            return $this->client;
        }

        $auth = [
            'VAPID' => [
                'subject' => config('services.webpush.vapid.subject'),
                'publicKey' => config('services.webpush.vapid.public_key'),
                'privateKey' => config('services.webpush.vapid.private_key'),
            ],
        ];

        if (empty($auth['VAPID']['publicKey']) || empty($auth['VAPID']['privateKey'])) {
            throw new \RuntimeException('VAPID keys are not configured. Run `php artisan push:generate-vapid-keys` and set VAPID_PUBLIC_KEY / VAPID_PRIVATE_KEY in .env.');
        }

        return $this->client = new WebPush($auth, [], 30);
    }

    /**
     * Queue a notification payload to a single subscription. The actual HTTP
     * requests are flushed in flush() so callers can batch many subscriptions
     * into one fan-out.
     */
    public function queue(PushSubscription $sub, array $payload): void
    {
        try {
            $subscription = Subscription::create([
                'endpoint' => $sub->endpoint,
                'publicKey' => $sub->p256dh,
                'authToken' => $sub->auth,
                'contentEncoding' => $sub->content_encoding ?? 'aesgcm',
            ]);

            $this->client()->queueNotification($subscription, json_encode($payload));
        } catch (\Throwable $e) {
            Log::warning('[WebPush] queue failed', [
                'subscription_id' => $sub->id,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Send all queued notifications. Deletes subscriptions that report
     * permanent failures (410 Gone / 404 Not Found).
     *
     * @param  Collection<int,PushSubscription>  $subscriptions keyed by endpoint hash
     * @return array{success:int, failed:int, pruned:int}
     */
    public function flush(Collection $subscriptions): array
    {
        $success = 0;
        $failed = 0;
        $pruned = 0;

        foreach ($this->client()->flush() as $report) {
            $endpointHash = PushSubscription::hashEndpoint($report->getRequest()->getUri()->__toString());
            $sub = $subscriptions->get($endpointHash);

            if ($report->isSuccess()) {
                $success++;
                if ($sub) {
                    $sub->forceFill([
                        'last_notified_at' => now(),
                        'failure_count' => 0,
                    ])->save();
                }
                continue;
            }

            $failed++;
            $statusCode = $report->getResponse()?->getStatusCode();

            if (in_array($statusCode, [404, 410], true) && $sub) {
                $sub->delete();
                $pruned++;
                continue;
            }

            if ($sub) {
                $sub->increment('failure_count');
            }

            Log::warning('[WebPush] delivery failed', [
                'status' => $statusCode,
                'reason' => $report->getReason(),
            ]);
        }

        return compact('success', 'failed', 'pruned');
    }

    /**
     * Convenience: fan-out a single payload to many subscriptions in one flush.
     */
    public function broadcast(Collection $subscriptions, array $payload): array
    {
        $keyed = $subscriptions->keyBy('endpoint_hash');

        foreach ($keyed as $sub) {
            $this->queue($sub, $payload);
        }

        return $this->flush($keyed);
    }
}
