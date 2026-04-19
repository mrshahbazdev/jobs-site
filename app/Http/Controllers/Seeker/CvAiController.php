<?php

namespace App\Http\Controllers\Seeker;

use App\Http\Controllers\Controller;
use App\Models\Cv;
use App\Services\CvAiService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CvAiController extends Controller
{
    public function improveSummary(Request $request, Cv $cv): JsonResponse
    {
        $this->authorizeOwner($request, $cv);

        $validated = $request->validate([
            'current' => 'nullable|string|max:4000',
            'target_role' => 'nullable|string|max:120',
        ]);

        $result = CvAiService::improveSummary($validated['current'] ?? '', $validated['target_role'] ?? null);

        if ($result['summary'] === '') {
            return response()->json([
                'ok' => false,
                'error' => 'AI is not available right now. Check GEMINI_API_KEY or try again.',
            ], 503);
        }

        return response()->json(['ok' => true, 'summary' => $result['summary']]);
    }

    public function generateBullets(Request $request, Cv $cv): JsonResponse
    {
        $this->authorizeOwner($request, $cv);

        $validated = $request->validate([
            'role' => 'required|string|max:120',
            'company' => 'required|string|max:120',
            'context' => 'nullable|string|max:2000',
        ]);

        $result = CvAiService::generateBullets(
            $validated['role'],
            $validated['company'],
            $validated['context'] ?? null,
        );

        if (count($result['bullets']) === 0) {
            return response()->json([
                'ok' => false,
                'error' => 'AI returned no suggestions. Try again or refine the role title.',
            ], 503);
        }

        return response()->json(['ok' => true, 'bullets' => $result['bullets']]);
    }

    public function suggestSkills(Request $request, Cv $cv): JsonResponse
    {
        $this->authorizeOwner($request, $cv);

        $validated = $request->validate([
            'target_role' => 'required|string|max:120',
        ]);

        $result = CvAiService::suggestSkills($validated['target_role']);

        if (count($result['skills']) === 0) {
            return response()->json([
                'ok' => false,
                'error' => 'AI returned no skills. Try a more specific role title.',
            ], 503);
        }

        return response()->json(['ok' => true, 'skills' => $result['skills']]);
    }

    public function atsScore(Request $request, Cv $cv): JsonResponse
    {
        $this->authorizeOwner($request, $cv);

        $validated = $request->validate([
            'job_description' => 'required|string|min:40|max:10000',
        ]);

        $result = CvAiService::atsScore($cv->toRenderable(), $validated['job_description']);

        if ($result['score'] === 0 && count($result['strengths']) === 0 && count($result['gaps']) === 0) {
            return response()->json([
                'ok' => false,
                'error' => 'ATS scoring failed. Ensure your CV has content and try again.',
            ], 503);
        }

        return response()->json([
            'ok' => true,
            'score' => $result['score'],
            'strengths' => $result['strengths'],
            'gaps' => $result['gaps'],
            'suggestions' => $result['suggestions'],
        ]);
    }

    private function authorizeOwner(Request $request, Cv $cv): void
    {
        $user = $request->user();
        if (!$user || $cv->user_id !== $user->id) {
            abort(403);
        }
    }
}
