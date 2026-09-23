<?php

namespace App\Console\Commands\Concerns;

use Carbon\Carbon;

/**
 * Stale-ad detection for listing scrapers: recycle sites repost old ads with
 * fresh titles, so we check the ad's own dates before queueing/processing.
 */
trait DetectsStaleAds
{
    /** Ads whose posted date is older than this are never queueable. */
    private const STALE_DAYS = 45;

    /** Ad images hosted under an upload month older than this are recycled. */
    private const STALE_IMAGE_DAYS = 75;

    /** Extract the first parseable date from arbitrary text, or null. */
    private function dateFromText(?string $text): ?Carbon
    {
        if (! $text) {
            return null;
        }
        // "20 July 2026", "July 20, 2026", "2026-07-20", "20/07/2026", "2026/07/20"
        if (preg_match('/(\d{1,2}\s+(?:Jan|Feb|Mar|Apr|May|Jun|Jul|Aug|Sep|Oct|Nov|Dec)[a-z]*\.?\s*,?\s*\d{4}|(?:Jan|Feb|Mar|Apr|May|Jun|Jul|Aug|Sep|Oct|Nov|Dec)[a-z]*\.?\s*\d{1,2}\s*,?\s*\d{4}|\d{4}[\/-]\d{1,2}[\/-]\d{1,2}|\d{1,2}[\/-]\d{1,2}[\/-]\d{4})/i', $text, $m)) {
            try {
                return Carbon::parse($m[1]);
            } catch (\Throwable) {
                return null;
            }
        }

        return null;
    }

    /**
     * A listing is stale when its deadline already passed, or its posted date
     * is older than STALE_DAYS (sites relabel old ads with the current year).
     */
    private function listingIsStale(?Carbon $posted, ?Carbon $lastDate = null): bool
    {
        if ($lastDate && $lastDate->isPast()) {
            return true;
        }

        return $posted && $posted->lt(now()->subDays(self::STALE_DAYS));
    }

    /** Detect ad images hosted under an old YYYY/MM or YYYY-MM upload path. */
    private function imageUrlIsOld(string $url): bool
    {
        if (preg_match('#/(20\d{2})[/-](0?[1-9]|1[0-2])[/-]#', $url, $m)) {
            $imageDate = Carbon::create((int) $m[1], (int) $m[2], 1)->startOfMonth();

            return $imageDate->lt(now()->subDays(self::STALE_IMAGE_DAYS));
        }

        return false;
    }
}
