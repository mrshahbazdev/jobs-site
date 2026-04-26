<?php

namespace App\Http\Controllers\Seeker;

use App\Http\Controllers\Controller;
use App\Models\Cv;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\View\View;

class CvController extends Controller
{
    public function index(Request $request): View
    {
        $cvs = $request->user()->cvs()->get();
        return view('seeker.cv.index', compact('cvs'));
    }

    public function create(Request $request): RedirectResponse
    {
        $user = $request->user();

        $cv = $user->cvs()->create([
            'title' => 'My CV',
            'template' => 'modern',
            'theme_color' => '#004b93',
            'font_family' => 'Inter',
            'personal' => [
                'full_name' => $user->name ?? '',
                'email' => $user->email ?? '',
                'phone' => $user->phone ?? '',
            ],
            'section_order' => Cv::DEFAULT_SECTION_ORDER,
        ]);

        $this->syncUserCvPath($cv);

        return redirect()->route('cv.edit', $cv);
    }

    public function edit(Request $request, Cv $cv): View
    {
        $this->authorizeOwner($request, $cv);

        return view('seeker.cv.edit', [
            'cv' => $cv,
            'data' => $cv->toRenderable(),
        ]);
    }

    public function update(Request $request, Cv $cv): JsonResponse
    {
        $this->authorizeOwner($request, $cv);

        $validated = $request->validate([
            'title' => 'nullable|string|max:120',
            'template' => 'nullable|string|in:modern,classic,minimal',
            'theme_color' => 'nullable|string|max:16',
            'font_family' => 'nullable|string|max:32',
            'summary' => 'nullable|string|max:4000',
            'is_public' => 'nullable|boolean',
            'personal' => 'nullable|array',
            'experience' => 'nullable|array',
            'education' => 'nullable|array',
            'skills' => 'nullable|array',
            'languages' => 'nullable|array',
            'certifications' => 'nullable|array',
            'projects' => 'nullable|array',
            'references_list' => 'nullable|array',
            'section_order' => 'nullable|array',
        ]);

        $cv->fill($validated);

        if ($cv->isDirty('title') && trim((string) $cv->title) === '') {
            $cv->title = 'Untitled CV';
        }

        $cv->save();

        return response()->json([
            'ok' => true,
            'updated_at' => $cv->updated_at?->toIso8601String(),
            'completeness' => $cv->computedCompleteness(),
        ]);
    }

    public function destroy(Request $request, Cv $cv): RedirectResponse
    {
        $this->authorizeOwner($request, $cv);

        $user = $request->user();
        $cv->delete();

        if (!$user->cvs()->exists()) {
            $user->cv_file_path = null;
            $user->save();
        }

        return redirect()->route('cv.index')->with('status', 'cv-deleted');
    }

    public function duplicate(Request $request, Cv $cv): RedirectResponse
    {
        $this->authorizeOwner($request, $cv);

        $copy = $cv->replicate(['share_uuid', 'views_count', 'last_viewed_at']);
        $copy->title = trim($cv->title) . ' (copy)';
        $copy->share_uuid = (string) Str::uuid();
        $copy->views_count = 0;
        $copy->last_viewed_at = null;
        $copy->save();

        return redirect()->route('cv.edit', $copy);
    }

    public function preview(Request $request, Cv $cv): View
    {
        $this->authorizeOwner($request, $cv);

        return view('seeker.cv.preview_frame', [
            'cv' => $cv,
            'data' => $cv->toRenderable(),
        ]);
    }

    public function download(Request $request, Cv $cv): Response
    {
        $this->authorizeOwner($request, $cv);

        $data = $cv->toRenderable();
        $template = in_array($cv->template, Cv::TEMPLATES, true) ? $cv->template : 'modern';

        $pdf = Pdf::loadView("seeker.cv.pdf.{$template}", ['cv' => $cv, 'data' => $data])
            ->setPaper('a4');

        $filename = Str::slug($data['personal']['full_name'] ?: $cv->title) . '-cv.pdf';
        if ($filename === '-cv.pdf') {
            $filename = 'cv-' . $cv->id . '.pdf';
        }

        $binary = $pdf->output();

        $path = 'cvs/' . $cv->user_id . '/' . $cv->id . '.pdf';
        Storage::disk('local')->put($path, $binary);
        $cv->user->update(['cv_file_path' => $path]);

        return response($binary, 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
        ]);
    }

    /**
     * Public share page (by share_uuid).
     */
    public function publicShow(string $uuid): View|Response
    {
        $cv = Cv::where('share_uuid', $uuid)->firstOrFail();

        if (!$cv->is_public) {
            abort(404);
        }

        $cv->increment('views_count');
        $cv->forceFill(['last_viewed_at' => now()])->save();

        return view('seeker.cv.public', [
            'cv' => $cv,
            'data' => $cv->toRenderable(),
        ]);
    }

    private function authorizeOwner(Request $request, Cv $cv): void
    {
        $user = $request->user();
        if (!$user || $cv->user_id !== $user->id) {
            abort(403);
        }
    }

    private function syncUserCvPath(Cv $cv): void
    {
        $user = $cv->user;
        if ($user && empty($user->cv_file_path)) {
            $user->cv_file_path = 'cvs/pending/' . $cv->id;
            $user->save();
        }
    }
}
