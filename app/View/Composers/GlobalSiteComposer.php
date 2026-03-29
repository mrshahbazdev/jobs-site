<?php

namespace App\View\Composers;

use Illuminate\View\View;
use App\Models\HomeBlock;
use Illuminate\Support\Facades\Cache;

class GlobalSiteComposer
{
    /**
     * Bind data to the view.
     */
    public function compose(View $view): void
    {
        // Cache global blocks for 60 minutes to improve performance
        $globalBlocks = Cache::remember('global_site_blocks', 3600, function () {
            return HomeBlock::active()
                ->whereIn('page_slug', ['header', 'footer'])
                ->ordered()
                ->get()
                ->groupBy('page_slug');
        });

        $view->with([
            'headerBlocks' => $globalBlocks->get('header', collect()),
            'footerBlocks' => $globalBlocks->get('footer', collect()),
        ]);
    }
}
