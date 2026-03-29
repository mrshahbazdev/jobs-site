<?php

namespace App\Livewire;

use Livewire\Component;
use Illuminate\Support\Facades\Cache;
use App\Jobs\ScrapePakistanJobs;

class ScraperProgress extends Component
{
    public $progress = null;
    public $isScraping = false;
    public $mode = 'all'; // 'all' or 'links'

    public function mount($mode = 'all')
    {
        $this->mode = $mode;
        $this->progress = Cache::get('scraper_progress');
        if ($this->progress && $this->progress['status'] === 'running') {
            $this->isScraping = true;
        }
    }

    public function startScraping()
    {
        $this->isScraping = true;
        Cache::put('scraper_progress', ['current' => 0, 'total' => 0, 'status' => 'starting'], 600);
        
        $onlyLinks = ($this->mode === 'links');
        ScrapePakistanJobs::dispatch($onlyLinks);
    }

    public function pollProgress()
    {
        $this->progress = Cache::get('scraper_progress');
        
        if ($this->progress && $this->progress['status'] === 'completed') {
            $this->isScraping = false;
        }
    }

    public function render()
    {
        return view('livewire.scraper-progress');
    }
}
