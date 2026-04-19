<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class Cv extends Model
{
    use HasFactory;

    public const TEMPLATES = ['modern', 'classic', 'minimal'];

    public const DEFAULT_SECTION_ORDER = [
        'summary',
        'experience',
        'education',
        'skills',
        'languages',
        'certifications',
        'projects',
        'references',
    ];

    protected $fillable = [
        'user_id',
        'share_uuid',
        'title',
        'template',
        'theme_color',
        'font_family',
        'personal',
        'summary',
        'experience',
        'education',
        'skills',
        'languages',
        'certifications',
        'projects',
        'references_list',
        'section_order',
        'is_public',
        'views_count',
        'last_viewed_at',
    ];

    protected function casts(): array
    {
        return [
            'personal' => 'array',
            'experience' => 'array',
            'education' => 'array',
            'skills' => 'array',
            'languages' => 'array',
            'certifications' => 'array',
            'projects' => 'array',
            'references_list' => 'array',
            'section_order' => 'array',
            'is_public' => 'boolean',
            'views_count' => 'integer',
            'last_viewed_at' => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (Cv $cv) {
            if (empty($cv->share_uuid)) {
                $cv->share_uuid = (string) Str::uuid();
            }
        });
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function getRouteKeyName(): string
    {
        return 'id';
    }

    /**
     * Return a fully hydrated data array with safe defaults for rendering.
     */
    public function toRenderable(): array
    {
        $personal = array_merge([
            'full_name' => '',
            'headline' => '',
            'email' => '',
            'phone' => '',
            'location' => '',
            'website' => '',
            'linkedin' => '',
            'github' => '',
        ], $this->personal ?? []);

        return [
            'id' => $this->id,
            'title' => $this->title,
            'template' => $this->template,
            'theme_color' => $this->theme_color,
            'font_family' => $this->font_family,
            'personal' => $personal,
            'summary' => (string) ($this->summary ?? ''),
            'experience' => $this->normalizeList($this->experience, [
                'company' => '', 'role' => '', 'location' => '',
                'start' => '', 'end' => '', 'current' => false,
                'bullets' => [],
            ]),
            'education' => $this->normalizeList($this->education, [
                'institution' => '', 'degree' => '', 'field' => '',
                'location' => '', 'start' => '', 'end' => '',
                'gpa' => '', 'description' => '',
            ]),
            'skills' => $this->normalizeList($this->skills, [
                'category' => 'Technical', 'name' => '', 'level' => '',
            ]),
            'languages' => $this->normalizeList($this->languages, [
                'name' => '', 'level' => '',
            ]),
            'certifications' => $this->normalizeList($this->certifications, [
                'name' => '', 'issuer' => '', 'date' => '', 'url' => '',
            ]),
            'projects' => $this->normalizeList($this->projects, [
                'name' => '', 'description' => '', 'technologies' => '', 'url' => '',
            ]),
            'references' => $this->normalizeList($this->references_list, [
                'name' => '', 'role' => '', 'company' => '', 'phone' => '', 'email' => '',
            ]),
            'section_order' => $this->section_order ?: self::DEFAULT_SECTION_ORDER,
        ];
    }

    /**
     * @param  array|null  $rows
     * @param  array  $defaults
     */
    private function normalizeList($rows, array $defaults): array
    {
        if (!is_array($rows)) {
            return [];
        }

        $out = [];
        foreach ($rows as $row) {
            if (!is_array($row)) {
                continue;
            }
            $merged = array_merge($defaults, $row);
            if (isset($merged['bullets']) && !is_array($merged['bullets'])) {
                $merged['bullets'] = [];
            }
            $out[] = $merged;
        }

        return $out;
    }

    public function computedCompleteness(): int
    {
        $checks = [
            (bool) ($this->personal['full_name'] ?? null),
            (bool) ($this->personal['email'] ?? null),
            (bool) ($this->personal['phone'] ?? null),
            (bool) $this->summary,
            is_array($this->experience) && count($this->experience) > 0,
            is_array($this->education) && count($this->education) > 0,
            is_array($this->skills) && count($this->skills) > 0,
        ];

        $filled = count(array_filter($checks));
        return (int) round(($filled / count($checks)) * 100);
    }
}
