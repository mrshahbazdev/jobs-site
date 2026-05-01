<?php

namespace App\Livewire;

use Livewire\Component;
use Illuminate\Support\Facades\Cache;
use App\Jobs\ScrapePakistanJobs;
use App\Jobs\ScrapeJobsAlertJobs;
use App\Jobs\ScrapeJobzPkJobs;

class ScraperProgress extends Component
{
    public $progress = null;
    public $isScraping = false;
    public $mode = 'all'; // 'all' or 'links'
    public $source = 'pakistan-jobs'; // 'pakistan-jobs', 'jobsalert', 'jobz-pk'

    private const SOURCE_CONFIG = [
        'pakistan-jobs' => [
            'label' => 'PakistanJobsBank.com',
            'cache_key' => 'scraper_progress',
            'job_class' => ScrapePakistanJobs::class,
        ],
        'jobsalert' => [
            'label' => 'JobsAlert.pk',
            'cache_key' => 'scraper_progress_jobsalert',
            'job_class' => ScrapeJobsAlertJobs::class,
        ],
        'jobz-pk' => [
            'label' => 'Jobz.pk',
            'cache_key' => 'scraper_progress_jobz',
            'job_class' => ScrapeJobzPkJobs::class,
        ],
    ];

    public function mount($mode = 'all', $source = 'pakistan-jobs')
    {
        $this->mode = $mode;
        $this->source = $source;
        $cacheKey = self::SOURCE_CONFIG[$this->source]['cache_key'] ?? 'scraper_progress';
        $this->progress = Cache::get($cacheKey);
        if ($this->progress && $this->progress['status'] === 'running') {
            $this->isScraping = true;
        }
    }

    public function startScraping()
    {
        $this->isScraping = true;
        $config = self::SOURCE_CONFIG[$this->source] ?? self::SOURCE_CONFIG['pakistan-jobs'];
        Cache::put($config['cache_key'], ['current' => 0, 'total' => 0, 'status' => 'starting'], 600);

        $onlyLinks = ($this->mode === 'links');
        $jobClass = $config['job_class'];
        $jobClass::dispatch($onlyLinks);
    }

    public function pollProgress()
    {
        $cacheKey = self::SOURCE_CONFIG[$this->source]['cache_key'] ?? 'scraper_progress';
        $this->progress = Cache::get($cacheKey);

        if ($this->progress && $this->progress['status'] === 'completed') {
            $this->isScraping = false;
        }
    }

    public function getSourceLabel(): string
    {
        return self::SOURCE_CONFIG[$this->source]['label'] ?? 'Unknown';
    }

    public function getSources(): array
    {
        return array_map(fn($cfg) => $cfg['label'], self::SOURCE_CONFIG);
    }

    public function render()
    {
        return view('livewire.scraper-progress');
    }
}
