@php
    $template = $cv->template ?: 'modern';
    $color = $cv->theme_color ?: '#004b93';
    $font = $cv->font_family ?: 'Inter';
@endphp

<style>
    * { box-sizing: border-box; }
    body { margin: 0; padding: 0; font-family: "{{ $font }}", -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif; color: #0f172a; background: #f8fafc; line-height: 1.45; }
    .cv-page { max-width: 820px; margin: 0 auto; background: white; padding: 40px 48px; }
    .cv-doc { font-size: 13px; }
    .cv-section { margin-top: 18px; page-break-inside: avoid; }
    .cv-section:first-of-type { margin-top: 14px; }
    .cv-h2 {
        font-size: 14px;
        font-weight: 800;
        letter-spacing: 0.04em;
        text-transform: uppercase;
        color: var(--accent);
        margin: 0 0 8px 0;
        padding-bottom: 4px;
        border-bottom: 2px solid var(--accent);
    }
    .cv-entry { margin-bottom: 10px; page-break-inside: avoid; }
    .cv-entry-head { display: flex; justify-content: space-between; gap: 12px; align-items: flex-start; }
    .cv-role { font-size: 13px; font-weight: 700; color: #0f172a; }
    .cv-company { font-size: 12.5px; color: #475569; margin-top: 1px; }
    .cv-dates { font-size: 11.5px; color: #64748b; white-space: nowrap; text-align: right; }
    .cv-bullets { margin: 6px 0 0 0; padding-left: 18px; }
    .cv-bullets li { margin-bottom: 3px; font-size: 12.5px; }
    .cv-text { font-size: 12.5px; color: #334155; margin: 4px 0 0 0; }
    .cv-summary { font-size: 13px; color: #334155; margin: 0; }
    .cv-skill-row { font-size: 12.5px; color: #334155; margin-bottom: 4px; }
    .cv-skill-cat { font-weight: 700; color: #0f172a; margin-right: 4px; }

    /* Modern template — accent bar */
    .cv-t-modern .cv-header {
        border-left: 5px solid var(--accent);
        padding-left: 18px;
        margin-bottom: 6px;
    }
    .cv-t-modern .cv-name { font-size: 26px; font-weight: 900; margin: 0; letter-spacing: -0.02em; }
    .cv-t-modern .cv-headline { font-size: 14px; color: var(--accent); font-weight: 600; margin: 2px 0 6px 0; }
    .cv-t-modern .cv-contacts { font-size: 11.5px; color: #475569; }
    .cv-t-modern .cv-contacts span { display: inline-block; margin-right: 14px; }

    /* Classic template — centered, serif-ish feel */
    .cv-t-classic .cv-header { text-align: center; padding-bottom: 10px; border-bottom: 1.5px solid #cbd5e1; }
    .cv-t-classic .cv-name { font-size: 28px; font-weight: 800; margin: 0; text-transform: uppercase; letter-spacing: 0.02em; }
    .cv-t-classic .cv-headline { font-size: 13px; color: #475569; font-weight: 500; margin: 4px 0 6px 0; font-style: italic; }
    .cv-t-classic .cv-contacts { font-size: 11.5px; color: #475569; }
    .cv-t-classic .cv-contacts span { display: inline-block; margin: 0 8px; }
    .cv-t-classic .cv-h2 { text-align: left; border-bottom: 1px solid #94a3b8; color: #0f172a; }

    /* Minimal template — ATS-friendly, no color accents */
    .cv-t-minimal .cv-header { padding-bottom: 6px; border-bottom: 1px solid #e2e8f0; }
    .cv-t-minimal .cv-name { font-size: 24px; font-weight: 800; margin: 0; color: #0f172a; }
    .cv-t-minimal .cv-headline { font-size: 13px; color: #475569; margin: 2px 0 4px 0; }
    .cv-t-minimal .cv-contacts { font-size: 11.5px; color: #475569; }
    .cv-t-minimal .cv-contacts span { display: inline-block; margin-right: 12px; }
    .cv-t-minimal .cv-h2 { color: #0f172a; border-bottom: 1px solid #cbd5e1; text-transform: none; font-weight: 700; letter-spacing: 0; }

    @media print {
        body { background: white; }
        .cv-page { padding: 24px 28px; box-shadow: none; }
    }
</style>
