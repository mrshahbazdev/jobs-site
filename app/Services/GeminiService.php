<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class GeminiService
{
    /**
     * Extract SEO metadata and job details from HTML description.
     */
    public static function extractMetadata(string $htmlContent): array
    {
        $apiKey = env('GEMINI_API_KEY');

        if (!$apiKey) {
            Log::warning('GEMINI_API_KEY is not set in .env');
            return [
                'meta_description' => '',
                'meta_keywords' => '',
                'experience' => '',
                'job_type' => '',
            ];
        }

        $prompt = "Analyze the following job description HTML and extract the following details in JSON format:
        1. meta_description: A concise summary (max 160 chars) for search engines.
        2. meta_keywords: 5-8 relevant comma-separated keywords.
        3. experience: Required experience level (e.g., 'Fresh', '1-2 Years', '5+ Years').
        4. job_type: The type of job (e.g., 'Full-time', 'Contract', 'Part-time').

        Job Description:
        " . strip_tags($htmlContent);

        try {
            $response = Http::post("https://generativelanguage.googleapis.com/v1beta/models/gemini-1.5-flash:generateContent?key=" . $apiKey, [
                'contents' => [
                    [
                        'parts' => [
                            ['text' => $prompt]
                        ]
                    ]
                ],
                'generationConfig' => [
                    'responseMimeType' => 'application/json',
                ]
            ]);

            if ($response->successful()) {
                $data = $response->json();
                $text = $data['candidates'][0]['content']['parts'][0]['text'] ?? '{}';
                return json_decode($text, true) ?? [];
            }

            Log::error('Gemini API Error: ' . $response->body());
        } catch (\Exception $e) {
            Log::error('Gemini Service Exception: ' . $e->getMessage());
        }

        return [];
    }

    /**
     * Parse natural language search query into structured filters.
     */
    public static function parseSearchQuery(string $query): array
    {
        $apiKey = env('GEMINI_API_KEY');

        if (!$apiKey) {
            return [];
        }

        $prompt = "Parse the following job search query into a structured JSON object. 
        Extract:
        1. keywords: Focus on job title or skills (e.g., 'PHP Developer', 'Sales').
        2. city: The city name if mentioned (e.g., 'Lahore', 'Karachi').
        3. experience: Experience level if mentioned (e.g., 'Fresh', '5 years').
        4. job_type: (e.g., 'Full-time', 'Remote').

        If a field is not mentioned, return null for it.

        Search Query: \"" . $query . "\"";

        try {
            $response = Http::post("https://generativelanguage.googleapis.com/v1beta/models/gemini-1.5-flash:generateContent?key=" . $apiKey, [
                'contents' => [
                    [
                        'parts' => [
                            ['text' => $prompt]
                        ]
                    ]
                ],
                'generationConfig' => [
                    'responseMimeType' => 'application/json',
                ]
            ]);

            if ($response->successful()) {
                $data = $response->json();
                $text = $data['candidates'][0]['content']['parts'][0]['text'] ?? '{}';
                return json_decode($text, true) ?? [];
            }
        } catch (\Exception $e) {
            Log::error('Gemini Search Query Exception: ' . $e->getMessage());
        }

        return [];
    }
}
