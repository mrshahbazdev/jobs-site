<?php

/**
 * URL-based cron entry point for shared hosts that can only trigger cron jobs
 * via HTTP requests (curl / wget).
 *
 * Usage:
 *   1. Set CRON_SECRET=<some-long-random-string> in .env (and php artisan config:clear).
 *   2. In your hosting cron panel add e.g.:
 *        curl --silent https://www.jobspic.com/cron.php?token=<your-secret> > /dev/null
 *      every minute.
 *
 * If CRON_SECRET is NOT set in .env this endpoint refuses all requests, so leaving
 * it unset keeps the endpoint dormant.
 */

use Illuminate\Contracts\Console\Kernel;
use Symfony\Component\Console\Output\BufferedOutput;

require __DIR__.'/../vendor/autoload.php';

$app = require_once __DIR__.'/../bootstrap/app.php';

/** @var Kernel $kernel */
$kernel = $app->make(Kernel::class);
$kernel->bootstrap();

$expected = env('CRON_SECRET');
$provided = $_GET['token'] ?? ($_SERVER['HTTP_X_CRON_TOKEN'] ?? null);

if (empty($expected) || ! is_string($provided) || ! hash_equals((string) $expected, $provided)) {
    http_response_code(404);
    echo 'Not Found';
    exit;
}

header('Content-Type: text/plain; charset=utf-8');

$output = new BufferedOutput();
$status = $kernel->call('schedule:run', [], $output);

echo "status={$status}\n";
echo $output->fetch();
