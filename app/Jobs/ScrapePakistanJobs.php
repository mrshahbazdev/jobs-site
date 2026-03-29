<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Artisan;

class ScrapePakistanJobs implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $timeout = 600;
    public $onlyLinks = false;
    public $imageId = null;

    public function __construct($onlyLinks = false, $imageId = null)
    {
        $this->onlyLinks = $onlyLinks;
        $this->imageId = $imageId;
    }

    public function handle(): void
    {
        $params = [];
        if ($this->onlyLinks) $params['--only-links'] = true;
        if ($this->imageId) $params['--image-id'] = $this->imageId;

        Artisan::call('scrape:pakistan-jobs', $params);
    }
}
