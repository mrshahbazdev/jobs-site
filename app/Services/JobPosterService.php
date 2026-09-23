<?php

namespace App\Services;

use App\Models\JobListing;
use Carbon\Carbon;
use Illuminate\Support\Facades\Storage;
use Intervention\Image\Drivers\Gd\Driver;
use Intervention\Image\ImageManager;

class JobPosterService
{
    const W = 1200;

    const H = 630;

    private array $colors = [
        'army-defence-jobs' => ['#0f5132', '#1e6b47'],
        'police-jobs' => ['#1b2a4a', '#2a3d66'],
        'banking' => ['#0b3d91', '#1a52b0'],
        'medical' => ['#8b1e3f', '#a8325a'],
        'teaching-jobs' => ['#5b2c83', '#7240a0'],
        'engineering' => ['#7a3e00', '#9a5410'],
        'default' => ['#1773cf', '#2b88e0'],
    ];

    public function generate(JobListing $job): string
    {
        [$bg, $accent] = $this->colors[$job->category?->slug] ?? $this->colors['default'];
        $bold = resource_path('fonts/Poppins-Bold.ttf');
        $reg = resource_path('fonts/Poppins-Regular.ttf');

        $img = (new ImageManager(new Driver))->create(self::W, self::H)->fill($bg);

        // Diagonal accent + top bar
        $img->drawPolygon(function ($p) use ($accent) {
            $p->point(820, 0);
            $p->point(self::W, 0);
            $p->point(self::W, self::H);
            $p->point(1000, self::H);
            $p->background($accent);
        });
        $img->drawRectangle(0, 0, fn ($r) => $r->size(self::W, 8)->background('#ffc107'));

        // Category pill
        $cat = strtoupper($job->category?->name ?? 'Jobs');
        $img->drawRectangle(60, 50, fn ($r) => $r->size(strlen($cat) * 13 + 40, 42)->background('#ffc107'));
        $this->text($img, $cat, 80, 58, $bold, 20, '#141414');

        // Organization + title
        $org = $job->company_name ?: $job->department ?: '';
        $this->text($img, mb_strimwidth($org, 0, 45, '…'), 60, 118, $bold, 30, '#ffffff');

        $title = $this->shortTitle($job->title);
        $size = mb_strlen($title) > 60 ? 50 : 60;
        $y = 170;
        foreach (array_slice(explode("\n", wordwrap($title, $size === 60 ? 26 : 32, "\n", true)), 0, 3) as $line) {
            $this->text($img, $line, 60, $y, $bold, $size, '#ffffff');
            $y += $size + 16;
        }

        // Facts bar
        $facts = array_filter([
            ['Last Date', $job->deadline ? Carbon::parse($job->deadline)->format('d M Y') : null],
            ['Education', $job->education],
            ['Location',  $job->city?->name],
            [$job->bps_scale ? 'Pay Scale' : 'Job Type', $job->bps_scale ?: str_replace('_', ' ', $job->job_type ?? '')],
        ], fn ($f) => filled($f[1]));
        $facts = array_values($facts);

        $by = self::H - 150;
        $img->drawRectangle(60, $by, fn ($r) => $r->size(self::W - 120, 80)->background('#ffffff'));
        $colW = (self::W - 150) / max(count($facts), 1);
        foreach ($facts as $i => [$label, $val]) {
            $x = 90 + (int) ($i * $colW);
            $this->text($img, $label, $x, $by + 12, $reg, 18, '#6e6e6e');
            $this->text($img, mb_strimwidth($val, 0, 18, '…'), $x, $by + 38, $bold, 24, '#141414');
        }

        // Branding
        $this->text($img, 'JobsPic.com', 60, self::H - 55, $bold, 26, '#ffc107');
        $this->text($img, 'Latest Jobs in Pakistan', self::W - 320, self::H - 50, $reg, 20, '#e6e6e6');

        $path = "job-posters/{$job->slug}.jpg";
        Storage::disk('public')->put($path, (string) $img->toJpeg(85));

        return $path;
    }

    private function text($img, string $t, int $x, int $y, string $font, int $size, string $color): void
    {
        $img->text($t, $x, $y, function ($f) use ($font, $size, $color) {
            $f->filename($font);
            $f->size($size);
            $f->color($color);
            $f->align('left');
            $f->valign('top');
        });
    }

    // "NBP CAD Officer OG-II Jobs 2026 – National Bank of Pakistan" → part before the dash
    private function shortTitle(string $title): string
    {
        return trim(preg_split('/\s[–-]\s/u', $title)[0]);
    }
}
