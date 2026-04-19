<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Minishlink\WebPush\VAPID;

class GenerateVapidKeys extends Command
{
    protected $signature = 'push:generate-vapid-keys {--force : Print keys even if already set in env}';
    protected $description = 'Generate VAPID public/private key pair for Web Push notifications';

    public function handle(): int
    {
        if (!class_exists(VAPID::class)) {
            $this->error('minishlink/web-push is not installed. Run `composer install` first.');
            return self::FAILURE;
        }

        $existing = config('services.webpush.vapid.public_key');
        if ($existing && !$this->option('force')) {
            $this->warn('VAPID keys already set in env. Use --force to regenerate.');
            return self::SUCCESS;
        }

        $keys = VAPID::createVapidKeys();

        $this->line('');
        $this->info('Add these to your .env file:');
        $this->line('');
        $this->line('VAPID_SUBJECT="mailto:admin@yourdomain.com"');
        $this->line('VAPID_PUBLIC_KEY=' . $keys['publicKey']);
        $this->line('VAPID_PRIVATE_KEY=' . $keys['privateKey']);
        $this->line('VITE_VAPID_PUBLIC_KEY="${VAPID_PUBLIC_KEY}"');
        $this->line('');
        $this->warn('Back up the private key. Regenerating invalidates every existing browser subscription.');

        return self::SUCCESS;
    }
}
