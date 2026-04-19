<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class CvAiService
{
    private const ENDPOINT = 'https://generativelanguage.googleapis.com/v1beta/models/gemini-1.5-flash:generateContent';

    /**
     * Improve a professional summary. Returns ['summary' => string].
     */
    public static function improveSummary(string $current, ?string $targetRole = null): array
    {
        $prompt = "You are a professional CV/resume writer. Rewrite the following professional summary so it is concise (3-4 sentences), impact-driven, uses strong action verbs, avoids clichés, and is written in the first person implicit (no 'I' pronouns). "
            . ($targetRole ? "Target role: \"{$targetRole}\". " : '')
            . "Return JSON of shape {\"summary\": string}.\n\nCurrent summary:\n" . trim($current);

        $data = self::call($prompt);
        return ['summary' => (string) ($data['summary'] ?? '')];
    }

    /**
     * Generate 3-5 achievement bullet points for an experience entry.
     * Returns ['bullets' => string[]].
     */
    public static function generateBullets(string $role, string $company, ?string $context = null): array
    {
        $prompt = "Generate 3-5 resume-ready achievement bullet points for this role. Each bullet must: start with a strong past-tense action verb, include a quantified result where plausible (percentages, currency, team size, time saved), be concise (one line each), use professional tone, avoid first-person pronouns. "
            . "Return JSON of shape {\"bullets\": string[]}.\n\n"
            . "Role: {$role}\nCompany: {$company}\n"
            . ($context ? "Extra context: {$context}\n" : '');

        $data = self::call($prompt);
        $bullets = $data['bullets'] ?? [];
        if (!is_array($bullets)) {
            return ['bullets' => []];
        }
        return ['bullets' => array_values(array_filter(array_map(fn ($b) => trim((string) $b), $bullets)))];
    }

    /**
     * Suggest skills for a target role.
     * Returns ['skills' => [{name, category}, ...]].
     */
    public static function suggestSkills(string $targetRole): array
    {
        $prompt = "Suggest 10-15 skills relevant for the role \"{$targetRole}\". Mix technical skills, tools, and 2-3 soft skills. For each skill, assign a category from: Technical, Tools, Soft, Languages, Frameworks, Other. "
            . "Return JSON of shape {\"skills\": [{\"name\": string, \"category\": string}]}.";

        $data = self::call($prompt);
        $skills = $data['skills'] ?? [];
        if (!is_array($skills)) {
            return ['skills' => []];
        }

        $out = [];
        foreach ($skills as $s) {
            if (!is_array($s)) continue;
            $name = trim((string) ($s['name'] ?? ''));
            $cat = trim((string) ($s['category'] ?? 'Technical'));
            if ($name === '') continue;
            $out[] = ['name' => $name, 'category' => $cat];
        }
        return ['skills' => $out];
    }

    /**
     * Score a CV against a job description. Returns
     * ['score' => int 0-100, 'strengths' => string[], 'gaps' => string[], 'suggestions' => string[]].
     */
    public static function atsScore(array $cvData, string $jobDescription): array
    {
        $cvText = self::flattenCv($cvData);

        $prompt = "You are an ATS (applicant tracking system) + recruiter. Score how well the candidate's CV matches the job description on a 0-100 scale. Identify strengths (what matches), gaps (what is missing or weak), and concrete suggestions to improve the CV for this specific role. "
            . "Return JSON of shape {\"score\": number 0-100, \"strengths\": string[], \"gaps\": string[], \"suggestions\": string[]}.\n\n"
            . "JOB DESCRIPTION:\n{$jobDescription}\n\n"
            . "CANDIDATE CV:\n{$cvText}";

        $data = self::call($prompt);

        return [
            'score' => max(0, min(100, (int) ($data['score'] ?? 0))),
            'strengths' => self::stringArray($data['strengths'] ?? []),
            'gaps' => self::stringArray($data['gaps'] ?? []),
            'suggestions' => self::stringArray($data['suggestions'] ?? []),
        ];
    }

    /**
     * @param  mixed  $arr
     */
    private static function stringArray($arr): array
    {
        if (!is_array($arr)) return [];
        return array_values(array_filter(array_map(fn ($v) => trim((string) $v), $arr)));
    }

    private static function flattenCv(array $cv): string
    {
        $out = [];
        $p = $cv['personal'] ?? [];
        $out[] = 'Name: ' . ($p['full_name'] ?? '');
        $out[] = 'Headline: ' . ($p['headline'] ?? '');
        if (!empty($cv['summary'])) {
            $out[] = "Summary: " . $cv['summary'];
        }

        if (!empty($cv['experience'])) {
            $out[] = "\nExperience:";
            foreach ($cv['experience'] as $e) {
                $out[] = '- ' . ($e['role'] ?? '') . ' at ' . ($e['company'] ?? '')
                    . ' (' . ($e['start'] ?? '') . '-' . (($e['current'] ?? false) ? 'Present' : ($e['end'] ?? '')) . ')';
                foreach (($e['bullets'] ?? []) as $b) {
                    $out[] = '  • ' . $b;
                }
            }
        }

        if (!empty($cv['education'])) {
            $out[] = "\nEducation:";
            foreach ($cv['education'] as $e) {
                $out[] = '- ' . ($e['degree'] ?? '') . ' in ' . ($e['field'] ?? '') . ' — ' . ($e['institution'] ?? '');
            }
        }

        if (!empty($cv['skills'])) {
            $skills = array_map(fn ($s) => $s['name'] ?? '', $cv['skills']);
            $out[] = "\nSkills: " . implode(', ', array_filter($skills));
        }

        return implode("\n", $out);
    }

    /**
     * Make a Gemini API call; return decoded JSON or [] on failure.
     */
    private static function call(string $prompt): array
    {
        $apiKey = config('services.gemini.key') ?: env('GEMINI_API_KEY');

        if (!$apiKey) {
            Log::warning('CvAiService: GEMINI_API_KEY not configured');
            return [];
        }

        try {
            $response = Http::timeout(30)
                ->post(self::ENDPOINT . '?key=' . $apiKey, [
                    'contents' => [
                        ['parts' => [['text' => $prompt]]],
                    ],
                    'generationConfig' => [
                        'responseMimeType' => 'application/json',
                        'temperature' => 0.6,
                    ],
                ]);

            if ($response->successful()) {
                $text = $response->json('candidates.0.content.parts.0.text') ?? '{}';
                $decoded = json_decode($text, true);
                return is_array($decoded) ? $decoded : [];
            }

            Log::error('CvAiService Gemini error: ' . $response->body());
        } catch (\Throwable $e) {
            Log::error('CvAiService exception: ' . $e->getMessage());
        }

        return [];
    }
}
