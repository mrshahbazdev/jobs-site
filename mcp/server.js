#!/usr/bin/env node
import crypto from 'node:crypto';
import path from 'node:path';
import { pathToFileURL } from 'node:url';
import { McpServer } from '@modelcontextprotocol/sdk/server/mcp.js';
import { StdioServerTransport } from '@modelcontextprotocol/sdk/server/stdio.js';
import { StreamableHTTPServerTransport } from '@modelcontextprotocol/sdk/server/streamableHttp.js';
import { z } from 'zod';

import { api, ApiError, config } from './client.js';

const SERVER_NAME = 'jobs-site-mcp';
const SERVER_VERSION = '1.1.0';

const READ = { readOnlyHint: true, openWorldHint: false };
const WRITE = { readOnlyHint: false, destructiveHint: false, idempotentHint: false, openWorldHint: false };
const IDEMPOTENT = { readOnlyHint: false, destructiveHint: false, idempotentHint: true, openWorldHint: false };
const DESTRUCTIVE = { readOnlyHint: false, destructiveHint: true, openWorldHint: false };

function jsonResult(data) {
  return {
    content: [{ type: 'text', text: JSON.stringify(data, null, 2) }],
    structuredContent: typeof data === 'object' && data !== null && !Array.isArray(data) ? data : { result: data },
  };
}

function errorResult(err) {
  const payload = {
    success: false,
    error: err instanceof Error ? err.message : String(err),
  };
  if (err instanceof ApiError) {
    payload.status = err.status;
    if (err.body && typeof err.body === 'object') {
      if (err.body.errors) payload.errors = err.body.errors;
      if (err.body.available) payload.available = err.body.available;
    }
  }
  return { ...jsonResult(payload), isError: true };
}

function tool(server, name, meta, handler) {
  server.registerTool(name, meta, async (args = {}) => {
    try {
      return jsonResult(await handler(args));
    } catch (err) {
      return errorResult(err);
    }
  });
}

const id = z.coerce.number().int().positive();
const ids = z.array(z.coerce.number().int().positive()).min(1);
const page = z.number().int().min(1).optional().describe('Page number (1-based).');
const perPage = z.number().int().min(1).max(100).optional().describe('Results per page (max 100).');
const bool = z.boolean().optional();
const str = z.string().optional();

const JOB_FIELDS = {
  title: str.describe('Job title (max 500).'),
  description: str.describe('Job description HTML/text (stored as description_html).'),
  category_id: z.number().int().optional(),
  city_id: z.number().int().optional(),
  slug: str,
  schema_json: str.describe('Raw JSON-LD JobPosting string; auto-generated when omitted.'),
  is_active: bool,
  is_featured: bool,
  is_premium: bool,
  deadline: str.describe('Free-form or Y-m-d deadline.'),
  department: str,
  company_name: str,
  whatsapp_number: str,
  salary_min: z.number().optional(),
  salary_max: z.number().optional(),
  salary_range: str,
  experience: str,
  job_type: str,
  contract_type: str,
  job_role: str,
  skills: str,
  education: str,
  qualification_degree: str,
  newspaper: str,
  province: str,
  gender: str,
  bps_scale: str,
  testing_service: str,
  sector: str,
  sub_sector: str,
  registration_council: str,
  country: str,
  is_overseas: bool,
  is_remote: bool,
  has_walkin_interview: bool,
  is_whatsapp_apply: bool,
  is_retired_army: bool,
  is_student_friendly: bool,
  has_accommodation: bool,
  has_transport: bool,
  has_medical_insurance: bool,
  is_special_quota: bool,
  is_minority_quota: bool,
  meta_description: str.describe('SEO meta description (max 160 chars).'),
  meta_keywords: str,
  job_source_image_id: z.number().int().optional(),
};

const JOB_FILTERS = {
  q: str.describe('Full-text search across title, description, company, department.'),
  category_id: z.number().int().optional(),
  city_id: z.number().int().optional(),
  province: str,
  job_type: str,
  contract_type: str,
  experience: str,
  education: str,
  sector: str,
  sub_sector: str,
  department: str,
  company_name: str,
  newspaper: str,
  gender: str,
  bps_scale: str,
  testing_service: str,
  country: str,
  salary_min: z.number().optional(),
  salary_max: z.number().optional(),
  is_active: bool,
  is_featured: bool,
  is_premium: bool,
  is_overseas: bool,
  is_remote: bool,
  has_walkin_interview: bool,
  is_whatsapp_apply: bool,
  is_retired_army: bool,
  is_student_friendly: bool,
  has_accommodation: bool,
  has_transport: bool,
  has_medical_insurance: bool,
  is_special_quota: bool,
  is_minority_quota: bool,
  date_from: str.describe('Created on/after (Y-m-d).'),
  date_to: str.describe('Created on/before (Y-m-d).'),
  deadline_from: str,
  deadline_to: str,
  expired: bool.describe('true = only expired deadlines, false = only non-expired.'),
  sort_by: str.describe('created_at | deadline | title | salary_min | salary_max | is_featured ...'),
  sort_order: z.enum(['asc', 'desc']).optional(),
  page,
  per_page: perPage,
};

// ---------------------------------------------------------------------------
// System / diagnostics
// ---------------------------------------------------------------------------
function registerSystemTools(server) {
  tool(server, 'site_overview', {
    title: 'Site overview & health',
    description: 'Full health snapshot: app/Laravel/PHP versions, DB connectivity, cache/queue drivers, integration flags (Gemini, VAPID, IndexNow, cron), record counts, queue depth and scraper states.',
    inputSchema: {},
    annotations: READ,
  }, () => api.get('/api/mcp/health'));

  tool(server, 'db_schema', {
    title: 'Database schema',
    description: 'List tables and columns (type, nullable, default). Optionally restrict to one table and include row counts.',
    inputSchema: {
      table: str.describe('Only this table.'),
      with_counts: bool.describe('Include row counts per table.'),
    },
    annotations: READ,
  }, (a) => api.get('/api/mcp/schema', a));

  tool(server, 'db_query', {
    title: 'Read-only SQL query',
    description: 'Run a single read-only SQL statement (SELECT / WITH / EXPLAIN / PRAGMA). Mutating statements are rejected server-side; rows are capped.',
    inputSchema: { query: z.string().min(1).max(5000) },
    annotations: READ,
  }, (a) => api.post('/api/mcp/sql', a));

  tool(server, 'routes_list', {
    title: 'Application routes',
    description: 'List registered Laravel routes (methods, URI, name, action, middleware). Optional substring filter.',
    inputSchema: { filter: str.describe('Substring matched against URI, name or action.') },
    annotations: READ,
  }, (a) => api.get('/api/mcp/routes', a));

  tool(server, 'logs_tail', {
    title: 'Tail application logs',
    description: 'Return the last N lines from storage/logs/<file>, optionally filtered by level (ERROR, WARNING, INFO...).',
    inputSchema: {
      lines: z.number().int().min(1).max(1000).optional(),
      level: str.describe('Log level filter, e.g. ERROR.'),
      file: str.describe('Log file name (default laravel.log).'),
    },
    annotations: READ,
  }, (a) => api.get('/api/mcp/logs', a));

  tool(server, 'artisan_commands', {
    title: 'Allowed Artisan commands',
    description: 'List Artisan commands (and their permitted options) that artisan_run may execute.',
    inputSchema: {},
    annotations: READ,
  }, () => api.get('/api/mcp/artisan'));

  tool(server, 'artisan_run', {
    title: 'Run Artisan command',
    description: 'Execute an allow-listed Artisan command (scrapers, push, IndexNow, queue, migrate, cache/config/route/view clear+cache, about). Returns output and exit code.',
    inputSchema: {
      command: z.string().describe('e.g. scrape:jobsalert, push:send-new-jobs, optimize:clear'),
      arguments: z.record(z.string(), z.union([z.string(), z.number(), z.boolean()])).optional()
        .describe('Option map, e.g. {"--limit": 5, "--dry-run": true}'),
    },
    annotations: WRITE,
  }, (a) => api.post('/api/mcp/artisan', a));

  tool(server, 'queue_status', {
    title: 'Queue & failed jobs',
    description: 'Pending and failed database queue jobs plus per-source scraper progress.',
    inputSchema: { limit: z.number().int().min(1).max(100).optional() },
    annotations: READ,
  }, (a) => api.get('/api/mcp/queue', a));

  tool(server, 'cache_manage', {
    title: 'Cache: clear / forget / get',
    description: 'Flush the whole application cache, forget one key, or read one key.',
    inputSchema: {
      action: z.enum(['clear', 'forget', 'get']),
      key: str.describe('Cache key (required for forget/get).'),
    },
    annotations: WRITE,
  }, (a) => api.post('/api/mcp/cache', a));
}

// ---------------------------------------------------------------------------
// Jobs
// ---------------------------------------------------------------------------
function registerJobTools(server) {
  tool(server, 'jobs_search', {
    title: 'Search job listings',
    description: 'Advanced filtered/paginated search over job listings (text, taxonomy, salary, flags, dates, deadlines, sorting).',
    inputSchema: JOB_FILTERS,
    annotations: READ,
  }, (a) => api.get('/api/v2/jobs', a));

  tool(server, 'jobs_get', {
    title: 'Get job',
    description: 'Fetch one job listing by numeric ID or slug, including category, city and schema.',
    inputSchema: { id_or_slug: z.string() },
    annotations: READ,
  }, ({ id_or_slug }) => api.get(`/api/v2/jobs/${encodeURIComponent(id_or_slug)}`));

  tool(server, 'jobs_stats', {
    title: 'Job stats',
    description: 'Aggregate job statistics (totals, active, featured, per category/city, recent).',
    inputSchema: {},
    annotations: READ,
  }, () => api.get('/api/v2/jobs/stats'));

  tool(server, 'jobs_analytics', {
    title: 'Job analytics',
    description: 'Time-windowed analytics: jobs created per day, expiring soon, top sectors/provinces/education/newspapers/job types/testing services/departments/companies, flag counts, subscribers, push, comments.',
    inputSchema: { days: z.number().int().min(1).max(365).optional() },
    annotations: READ,
  }, (a) => api.get('/api/mcp/analytics', a));

  tool(server, 'jobs_create', {
    title: 'Create job',
    description: 'Create a job listing. Slug, thumbnail and JobPosting schema are generated automatically when omitted.',
    inputSchema: {
      ...JOB_FIELDS,
      title: z.string().min(3).max(500),
      description: z.string().min(10),
      category_id: z.number().int(),
      city_id: z.number().int(),
    },
    annotations: WRITE,
  }, (a) => api.post('/api/v2/jobs', a));

  tool(server, 'jobs_update', {
    title: 'Update job',
    description: 'Partially update a job listing by ID.',
    inputSchema: { id, ...JOB_FIELDS },
    annotations: IDEMPOTENT,
  }, ({ id: jobId, ...rest }) => api.put(`/api/v2/jobs/${jobId}`, rest));

  tool(server, 'jobs_delete', {
    title: 'Delete job',
    description: 'Permanently delete a job listing.',
    inputSchema: { id },
    annotations: DESTRUCTIVE,
  }, ({ id: jobId }) => api.del(`/api/v2/jobs/${jobId}`));

  tool(server, 'jobs_toggle', {
    title: 'Toggle job flag',
    description: 'Toggle is_active / is_featured / is_premium on a job.',
    inputSchema: { id, field: z.enum(['is_active', 'is_featured', 'is_premium']) },
    annotations: IDEMPOTENT,
  }, ({ id: jobId, ...rest }) => api.post(`/api/v2/jobs/${jobId}/toggle`, rest));

  tool(server, 'jobs_duplicate', {
    title: 'Duplicate job',
    description: 'Clone a job listing (new slug, inactive copy).',
    inputSchema: { id },
    annotations: WRITE,
  }, ({ id: jobId }) => api.post(`/api/v2/jobs/${jobId}/duplicate`));

  tool(server, 'jobs_bulk_create', {
    title: 'Bulk create jobs',
    description: 'Create many job listings in one call.',
    inputSchema: {
      jobs: z.array(z.object({
        ...JOB_FIELDS,
        title: z.string(),
        description: z.string(),
        category_id: z.number().int(),
        city_id: z.number().int(),
      })).min(1).max(100),
    },
    annotations: WRITE,
  }, (a) => api.post('/api/v2/jobs/bulk', a));

  tool(server, 'jobs_bulk_status', {
    title: 'Bulk set job flag',
    description: 'Set is_active / is_featured / is_premium for many jobs at once.',
    inputSchema: { ids, field: z.enum(['is_active', 'is_featured', 'is_premium']), value: z.boolean() },
    annotations: IDEMPOTENT,
  }, (a) => api.post('/api/v2/jobs/bulk-status', a));

  tool(server, 'jobs_bulk_delete', {
    title: 'Bulk delete jobs',
    description: 'Permanently delete many jobs.',
    inputSchema: { ids },
    annotations: DESTRUCTIVE,
  }, (a) => api.del('/api/v2/jobs/bulk', a));

  tool(server, 'jobs_deactivate_expired', {
    title: 'Deactivate expired jobs',
    description: 'Mark jobs whose deadline passed (minus grace days) as inactive. Use dry_run to preview.',
    inputSchema: { grace_days: z.number().int().min(0).max(365).optional(), dry_run: bool },
    annotations: IDEMPOTENT,
  }, (a) => api.post('/api/mcp/jobs/deactivate-expired', a));

  tool(server, 'jobs_regenerate_schema', {
    title: 'Regenerate JobPosting schema',
    description: 'Rebuild schema_json (JSON-LD JobPosting) for given IDs, or for jobs missing it. force=true overwrites existing.',
    inputSchema: {
      ids: z.array(z.number().int()).optional(),
      only_missing: bool,
      force: bool,
      limit: z.number().int().min(1).max(1000).optional(),
    },
    annotations: IDEMPOTENT,
  }, (a) => api.post('/api/mcp/jobs/regenerate-schema', a));
}

// ---------------------------------------------------------------------------
// Taxonomy: categories & cities
// ---------------------------------------------------------------------------
function registerTaxonomyTools(server) {
  tool(server, 'categories_list', {
    title: 'List categories', description: 'All job categories (id, name, slug, icon).', inputSchema: {}, annotations: READ,
  }, () => api.get('/api/categories'));

  tool(server, 'categories_create', {
    title: 'Create category',
    description: 'Create (or fetch existing) category by name; optional landing group attachment.',
    inputSchema: { name: z.string(), landing_group_id: z.number().int().optional() },
    annotations: WRITE,
  }, (a) => api.post('/api/categories', a));

  tool(server, 'categories_resolve', {
    title: 'Resolve category from title',
    description: 'Heuristically pick the best category for a job title.',
    inputSchema: { title: z.string() },
    annotations: READ,
  }, (a) => api.post('/api/categories/resolve', a));

  tool(server, 'categories_update', {
    title: 'Update category',
    inputSchema: { id, name: str, slug: str, icon_name: str },
    annotations: IDEMPOTENT,
  }, ({ id: catId, ...rest }) => api.put(`/api/mcp/categories/${catId}`, rest));

  tool(server, 'categories_delete', {
    title: 'Delete category',
    description: 'Delete a category. If it has jobs you must pass reassign_to (another category id).',
    inputSchema: { id, reassign_to: z.number().int().optional() },
    annotations: DESTRUCTIVE,
  }, ({ id: catId, ...rest }) => api.del(`/api/mcp/categories/${catId}`, rest));

  tool(server, 'categories_merge', {
    title: 'Merge categories',
    description: 'Move all jobs & subscribers from source categories into target and delete the sources.',
    inputSchema: { source_ids: ids, target_id: id },
    annotations: DESTRUCTIVE,
  }, (a) => api.post('/api/mcp/categories/merge', a));

  tool(server, 'cities_list', {
    title: 'List cities', description: 'All cities (id, name, slug).', inputSchema: {}, annotations: READ,
  }, () => api.get('/api/cities'));

  tool(server, 'cities_create', {
    title: 'Create city', inputSchema: { name: z.string() }, annotations: WRITE,
  }, (a) => api.post('/api/cities', a));

  tool(server, 'cities_update', {
    title: 'Update city', inputSchema: { id, name: str, slug: str }, annotations: IDEMPOTENT,
  }, ({ id: cityId, ...rest }) => api.put(`/api/mcp/cities/${cityId}`, rest));

  tool(server, 'cities_delete', {
    title: 'Delete city',
    description: 'Delete a city. If it has jobs you must pass reassign_to (another city id).',
    inputSchema: { id, reassign_to: z.number().int().optional() },
    annotations: DESTRUCTIVE,
  }, ({ id: cityId, ...rest }) => api.del(`/api/mcp/cities/${cityId}`, rest));
}

// ---------------------------------------------------------------------------
// Scrapers & scraper queue
// ---------------------------------------------------------------------------
const SOURCES = z.enum(['pakistan-jobs', 'jobsalert', 'jobz-pk']);

function registerScraperTools(server) {
  tool(server, 'scraper_status', {
    title: 'Scraper status',
    description: 'Progress of one scraper source, or all sources when omitted.',
    inputSchema: { source: SOURCES.optional() },
    annotations: READ,
  }, ({ source }) => (source ? api.get('/api/scraper-status', { source }) : api.get('/api/scraper-status-all')));

  tool(server, 'scraper_trigger', {
    title: 'Trigger scraper',
    description: 'Queue a scrape run for a source. mode=links collects new job links only; mode=full also processes images/AI.',
    inputSchema: { source: SOURCES.optional(), mode: z.enum(['links', 'full']).optional() },
    annotations: WRITE,
  }, (a) => api.post('/api/trigger-scrape', a));

  tool(server, 'scraper_process_image', {
    title: 'Process one queued image',
    description: 'Run the scraper pipeline for a single job_source_images row (download + extract).',
    inputSchema: { id, source: SOURCES.optional() },
    annotations: WRITE,
  }, (a) => api.post('/api/scrape-image', a));

  tool(server, 'scraper_queue_stats', {
    title: 'Scraper queue stats', inputSchema: {}, annotations: READ,
  }, () => api.get('/api/v2/scraper-queue/stats'));

  tool(server, 'scraper_queue_search', {
    title: 'Search scraper queue',
    description: 'Search job_source_images by status, text and source domain.',
    inputSchema: {
      status: z.enum(['pending', 'published', 'skipped', 'failed']).optional(),
      q: str,
      source: str.describe('Substring of source_page_url, e.g. jobsalert.pk'),
      page,
      per_page: perPage,
    },
    annotations: READ,
  }, (a) => api.get('/api/mcp/scraper-queue', a));

  tool(server, 'scraper_queue_next', {
    title: 'Next pending scraped item',
    description: 'Fetch the next pending scraped item to publish.',
    inputSchema: {},
    annotations: READ,
  }, () => api.get('/api/v2/scraper-queue/next'));

  tool(server, 'scraper_queue_set_status', {
    title: 'Set scraped item status',
    inputSchema: {
      id,
      status: z.enum(['pending', 'published', 'skipped', 'failed']),
      published_job_id: z.number().int().optional(),
    },
    annotations: IDEMPOTENT,
  }, ({ id: itemId, ...rest }) => api.put(`/api/v2/scraper-queue/${itemId}/status`, rest));

  tool(server, 'scraper_queue_bulk_status', {
    title: 'Bulk set scraped item status',
    inputSchema: { ids, status: z.enum(['pending', 'published', 'skipped', 'failed']) },
    annotations: IDEMPOTENT,
  }, (a) => api.post('/api/mcp/scraper-queue/bulk-status', a));

  tool(server, 'scraper_queue_purge', {
    title: 'Purge scraper queue',
    description: 'Delete skipped/failed/published scraped items older than N days. dry_run previews the count.',
    inputSchema: {
      status: z.enum(['skipped', 'failed', 'published']),
      older_than_days: z.number().int().min(0).optional(),
      dry_run: bool,
    },
    annotations: DESTRUCTIVE,
  }, (a) => api.post('/api/mcp/scraper-queue/purge', a));
}

// ---------------------------------------------------------------------------
// SEO / AI / IndexNow / Push
// ---------------------------------------------------------------------------
function registerGrowthTools(server) {
  tool(server, 'seo_audit', {
    title: 'SEO audit',
    description: 'Audit active jobs for missing meta descriptions, keywords, schema, short titles/descriptions, missing deadlines and duplicate slugs.',
    inputSchema: { limit: z.number().int().min(1).max(200).optional() },
    annotations: READ,
  }, (a) => api.get('/api/mcp/seo/audit', a));

  tool(server, 'indexnow_submit', {
    title: 'Submit URLs to IndexNow',
    description: 'Ping IndexNow (Bing/Yandex/etc.) with explicit URLs and/or job IDs (resolved to job URLs).',
    inputSchema: {
      urls: z.array(z.string().url()).optional(),
      job_ids: z.array(z.number().int()).optional(),
    },
    annotations: WRITE,
  }, (a) => api.post('/api/mcp/indexnow', a));

  tool(server, 'ai_extract_job', {
    title: 'AI extract job metadata (Gemini)',
    description: 'Use Gemini to extract structured metadata from raw HTML or from an existing job. apply=true writes the extracted fields back to the job.',
    inputSchema: {
      html: str.describe('Raw job advert HTML/text.'),
      job_id: z.number().int().optional().describe('Existing job to analyse (uses its description).'),
      apply: bool,
    },
    annotations: WRITE,
  }, (a) => api.post('/api/mcp/ai/extract', a));

  tool(server, 'push_stats', {
    title: 'Web push stats',
    description: 'VAPID configuration, subscription totals, failing endpoints, breakdown by category/city, jobs awaiting push.',
    inputSchema: {},
    annotations: READ,
  }, () => api.get('/api/mcp/push/stats'));

  tool(server, 'push_broadcast', {
    title: 'Broadcast web push',
    description: 'Send a custom push notification to all (or category/city-filtered) subscribers. dry_run returns recipient count only.',
    inputSchema: {
      title: z.string().max(120),
      body: z.string().max(300),
      url: z.string().url().optional(),
      category_id: z.number().int().optional(),
      city_id: z.number().int().optional(),
      limit: z.number().int().min(1).max(5000).optional(),
      dry_run: bool,
    },
    annotations: WRITE,
  }, (a) => api.post('/api/mcp/push/broadcast', a));
}

// ---------------------------------------------------------------------------
// Content: posts, settings, comments, subscribers, landing, home blocks, users
// ---------------------------------------------------------------------------
function registerContentTools(server) {
  tool(server, 'posts_list', {
    title: 'List blog posts',
    inputSchema: { q: str, published: bool, with_content: bool, page, per_page: perPage },
    annotations: READ,
  }, (a) => api.get('/api/mcp/posts', a));

  tool(server, 'posts_get', {
    title: 'Get blog post', inputSchema: { id_or_slug: z.string() }, annotations: READ,
  }, ({ id_or_slug }) => api.get(`/api/mcp/posts/${encodeURIComponent(id_or_slug)}`));

  tool(server, 'posts_create', {
    title: 'Create blog post',
    inputSchema: { title: z.string().max(255), content: z.string(), slug: str, image: str, is_published: bool },
    annotations: WRITE,
  }, (a) => api.post('/api/mcp/posts', a));

  tool(server, 'posts_update', {
    title: 'Update blog post',
    inputSchema: { id, title: str, content: str, slug: str, image: str, is_published: bool },
    annotations: IDEMPOTENT,
  }, ({ id: postId, ...rest }) => api.put(`/api/mcp/posts/${postId}`, rest));

  tool(server, 'posts_delete', {
    title: 'Delete blog post', inputSchema: { id }, annotations: DESTRUCTIVE,
  }, ({ id: postId }) => api.del(`/api/mcp/posts/${postId}`));

  tool(server, 'settings_get', {
    title: 'Get site settings',
    description: 'All key/value site settings (ad slots, header tags, etc.).',
    inputSchema: {},
    annotations: READ,
  }, () => api.get('/api/mcp/settings'));

  tool(server, 'settings_set', {
    title: 'Upsert site settings',
    inputSchema: { settings: z.array(z.object({ key: z.string().max(191), value: z.string().nullable() })).min(1) },
    annotations: IDEMPOTENT,
  }, (a) => api.put('/api/mcp/settings', a));

  tool(server, 'settings_delete', {
    title: 'Delete setting', inputSchema: { key: z.string() }, annotations: DESTRUCTIVE,
  }, ({ key }) => api.del(`/api/mcp/settings/${encodeURIComponent(key)}`));

  tool(server, 'comments_list', {
    title: 'List comments',
    description: 'Comments by moderation status (pending default, approved, all).',
    inputSchema: { status: z.enum(['pending', 'approved', 'all']).optional(), page, per_page: perPage },
    annotations: READ,
  }, (a) => api.get('/api/mcp/comments', a));

  tool(server, 'comments_moderate', {
    title: 'Moderate comments',
    inputSchema: { ids, action: z.enum(['approve', 'reject', 'delete']) },
    annotations: DESTRUCTIVE,
  }, (a) => api.post('/api/mcp/comments/moderate', a));

  tool(server, 'subscribers_list', {
    title: 'List subscribers',
    description: 'Email/WhatsApp job-alert subscribers with summary counts.',
    inputSchema: { q: str, active: bool, page, per_page: perPage },
    annotations: READ,
  }, (a) => api.get('/api/mcp/subscribers', a));

  tool(server, 'subscribers_update', {
    title: 'Update subscriber',
    inputSchema: {
      id,
      is_active: bool,
      name: str,
      category_id: z.number().int().nullable().optional(),
      city_id: z.number().int().nullable().optional(),
    },
    annotations: IDEMPOTENT,
  }, ({ id: subId, ...rest }) => api.put(`/api/mcp/subscribers/${subId}`, rest));

  tool(server, 'subscribers_delete', {
    title: 'Delete subscriber', inputSchema: { id }, annotations: DESTRUCTIVE,
  }, ({ id: subId }) => api.del(`/api/mcp/subscribers/${subId}`));

  tool(server, 'landing_get', {
    title: 'Landing groups & links',
    description: 'All landing-page groups with their links and attached categories.',
    inputSchema: {},
    annotations: READ,
  }, () => api.get('/api/mcp/landing'));

  tool(server, 'landing_group_create', {
    title: 'Create landing group', inputSchema: { name: z.string(), icon: str }, annotations: WRITE,
  }, (a) => api.post('/api/landing-groups', a));

  tool(server, 'landing_group_update', {
    title: 'Update landing group',
    inputSchema: {
      id, name: str, sub_label: str, icon: str,
      sort_order: z.number().int().optional(),
      is_active: bool,
      section_type: z.enum(['grid', 'strip', 'industry']).optional(),
    },
    annotations: IDEMPOTENT,
  }, ({ id: gId, ...rest }) => api.put(`/api/mcp/landing-groups/${gId}`, rest));

  tool(server, 'landing_group_delete', {
    title: 'Delete landing group', inputSchema: { id }, annotations: DESTRUCTIVE,
  }, ({ id: gId }) => api.del(`/api/mcp/landing-groups/${gId}`));

  const LINK_FIELDS = {
    title: str, url: str, route_name: str, route_param: str, icon: str,
    sort_order: z.number().int().optional(), is_active: bool,
  };

  tool(server, 'landing_link_create', {
    title: 'Create landing link',
    inputSchema: { ...LINK_FIELDS, landing_group_id: z.number().int(), title: z.string() },
    annotations: WRITE,
  }, (a) => api.post('/api/mcp/landing-links', a));

  tool(server, 'landing_link_update', {
    title: 'Update landing link',
    inputSchema: { id, landing_group_id: z.number().int().optional(), ...LINK_FIELDS },
    annotations: IDEMPOTENT,
  }, ({ id: lId, ...rest }) => api.put(`/api/mcp/landing-links/${lId}`, rest));

  tool(server, 'landing_link_delete', {
    title: 'Delete landing link', inputSchema: { id }, annotations: DESTRUCTIVE,
  }, ({ id: lId }) => api.del(`/api/mcp/landing-links/${lId}`));

  const BLOCK_FIELDS = {
    page_slug: str, type: str.describe('Block type key used by the home page renderer.'), title: str, url: str, list_source: str, display_type: str,
    heading_text: str, sub_text: str,
    job_count: z.number().int().min(1).max(100).optional(),
    show_sidebar: bool, variant: str, icon: str,
    cards: z.array(z.record(z.string(), z.unknown())).optional(),
    settings: z.record(z.string(), z.unknown()).optional(),
    sort_order: z.number().int().optional(),
    is_active: bool,
  };

  tool(server, 'home_blocks_list', {
    title: 'List home/page blocks', inputSchema: { page_slug: str }, annotations: READ,
  }, (a) => api.get('/api/mcp/home-blocks', a));

  tool(server, 'home_blocks_create', {
    title: 'Create home block',
    inputSchema: { ...BLOCK_FIELDS, type: z.string() },
    annotations: WRITE,
  }, (a) => api.post('/api/mcp/home-blocks', a));

  tool(server, 'home_blocks_update', {
    title: 'Update home block', inputSchema: { id, ...BLOCK_FIELDS }, annotations: IDEMPOTENT,
  }, ({ id: bId, ...rest }) => api.put(`/api/mcp/home-blocks/${bId}`, rest));

  tool(server, 'home_blocks_delete', {
    title: 'Delete home block', inputSchema: { id }, annotations: DESTRUCTIVE,
  }, ({ id: bId }) => api.del(`/api/mcp/home-blocks/${bId}`));

  tool(server, 'home_blocks_reorder', {
    title: 'Reorder home blocks',
    description: 'Set sort_order according to the given ID order.',
    inputSchema: { ids },
    annotations: IDEMPOTENT,
  }, (a) => api.post('/api/mcp/home-blocks/reorder', a));

  tool(server, 'users_list', {
    title: 'List users',
    description: 'Registered users with CV/bookmark/comment counts and a summary (roles, new signups, CVs, push subs).',
    inputSchema: { q: str, role: str, page, per_page: perPage },
    annotations: READ,
  }, (a) => api.get('/api/mcp/users', a));
}

// ---------------------------------------------------------------------------
// Admin: users, CVs, bookmarks, source images, sitemaps, alerts, storage
// ---------------------------------------------------------------------------
const ROLE = z.enum(['seeker', 'employer', 'admin']);
const CV_ARRAY = z.array(z.unknown()).optional();

function registerAdminTools(server) {
  tool(server, 'users_get', {
    title: 'Get user',
    description: 'User profile with CVs, bookmarked jobs and profile completion.',
    inputSchema: { id },
    annotations: READ,
  }, ({ id: userId }) => api.get(`/api/mcp/users/${userId}`));

  tool(server, 'users_create', {
    title: 'Create user',
    description: 'Create a user (password auto-generated and returned if omitted).',
    inputSchema: { name: z.string().max(255), email: z.string().email(), password: str, role: ROLE.optional(), phone: str, verified: bool },
    annotations: WRITE,
  }, (a) => api.post('/api/mcp/users', a));

  tool(server, 'users_update', {
    title: 'Update user',
    description: 'Change name/email/role/phone/verified state or set a new password.',
    inputSchema: { id, name: str, email: str, password: str, role: ROLE.optional(), phone: z.string().nullable().optional(), verified: bool },
    annotations: IDEMPOTENT,
  }, ({ id: userId, ...rest }) => api.put(`/api/mcp/users/${userId}`, rest));

  tool(server, 'users_reset_password', {
    title: 'Reset user password',
    description: 'Sets a new password (random if omitted) and invalidates remember-me sessions.',
    inputSchema: { id, password: str },
    annotations: WRITE,
  }, ({ id: userId, ...rest }) => api.post(`/api/mcp/users/${userId}/reset-password`, rest));

  tool(server, 'users_delete', {
    title: 'Delete user',
    description: 'Deletes the user with their bookmarks, comments and CVs. Refuses the last admin unless force=true.',
    inputSchema: { id, force: bool },
    annotations: DESTRUCTIVE,
  }, ({ id: userId, force }) => api.del(`/api/mcp/users/${userId}`, force ? { force: true } : undefined));

  tool(server, 'cvs_list', {
    title: 'List CVs',
    description: 'CV builder documents with summary (templates, public count, views).',
    inputSchema: { q: str, user_id: id.optional(), public: bool, template: z.enum(['modern', 'classic', 'minimal']).optional(), page, per_page: perPage },
    annotations: READ,
  }, (a) => api.get('/api/mcp/cvs', a));

  tool(server, 'cvs_get', {
    title: 'Get CV', description: 'Full CV JSON (personal, experience, education, skills...).', inputSchema: { id }, annotations: READ,
  }, ({ id: cvId }) => api.get(`/api/mcp/cvs/${cvId}`));

  tool(server, 'cvs_update', {
    title: 'Update CV',
    description: 'Edit CV sections/template/visibility. Making it public generates a share URL.',
    inputSchema: {
      id, title: str, template: z.enum(['modern', 'classic', 'minimal']).optional(), theme_color: str, font_family: str,
      personal: z.record(z.string(), z.unknown()).optional(), summary: z.string().nullable().optional(),
      experience: CV_ARRAY, education: CV_ARRAY, skills: CV_ARRAY, languages: CV_ARRAY, certifications: CV_ARRAY,
      projects: CV_ARRAY, references_list: CV_ARRAY, section_order: z.array(z.string()).optional(), is_public: bool,
    },
    annotations: IDEMPOTENT,
  }, ({ id: cvId, ...rest }) => api.put(`/api/mcp/cvs/${cvId}`, rest));

  tool(server, 'cvs_delete', {
    title: 'Delete CV', inputSchema: { id }, annotations: DESTRUCTIVE,
  }, ({ id: cvId }) => api.del(`/api/mcp/cvs/${cvId}`));

  tool(server, 'bookmarks_stats', {
    title: 'Bookmark stats',
    description: 'Most-bookmarked jobs in a window plus totals (demand signal).',
    inputSchema: { days: z.number().int().min(0).optional().describe('0 = all time'), limit: z.number().int().min(1).max(100).optional() },
    annotations: READ,
  }, (a) => api.get('/api/mcp/bookmarks', a));

  tool(server, 'bookmarks_toggle', {
    title: 'Toggle bookmark', inputSchema: { user_id: id, job_id: id }, annotations: IDEMPOTENT,
  }, (a) => api.post('/api/mcp/bookmarks/toggle', a));

  tool(server, 'source_image_get', {
    title: 'Get source image',
    description: 'A scraper-queue item (job ad image) with its OCR/article text and linked job.',
    inputSchema: { id },
    annotations: READ,
  }, ({ id: imgId }) => api.get(`/api/mcp/source-images/${imgId}`));

  tool(server, 'source_image_add', {
    title: 'Add job ad image to scraper queue',
    description: 'Manually ingest a job ad image (by URL or base64) into job_source_images so it can be published via the normal pipeline.',
    inputSchema: {
      title: z.string().max(255), image_url: z.string().url().optional(), image_base64: str,
      source_page_url: z.string().url().optional(), article_text: str,
      publish_status: z.enum(['pending', 'published', 'skipped']).optional(),
    },
    annotations: WRITE,
  }, (a) => api.post('/api/mcp/source-images', a));

  tool(server, 'source_image_update', {
    title: 'Update source image',
    inputSchema: {
      id, title: str, article_text: z.string().nullable().optional(), source_page_url: z.string().nullable().optional(),
      publish_status: z.enum(['pending', 'published', 'skipped']).optional(), published_job_id: id.nullable().optional(),
    },
    annotations: IDEMPOTENT,
  }, ({ id: imgId, ...rest }) => api.put(`/api/mcp/source-images/${imgId}`, rest));

  tool(server, 'source_image_publish', {
    title: 'Link source image to job',
    description: 'Attach the ad image to an existing job listing and mark the queue item published.',
    inputSchema: { id, job_id: id },
    annotations: IDEMPOTENT,
  }, ({ id: imgId, job_id }) => api.post(`/api/mcp/source-images/${imgId}/publish`, { job_id }));

  tool(server, 'source_image_delete', {
    title: 'Delete source image',
    inputSchema: { id, delete_file: bool.describe('Also remove the stored image file (default true).') },
    annotations: DESTRUCTIVE,
  }, ({ id: imgId, delete_file }) => api.del(`/api/mcp/source-images/${imgId}`, delete_file === undefined ? undefined : { delete_file }));

  tool(server, 'sitemaps_status', {
    title: 'Sitemap status',
    description: 'All sitemap/feed URLs, job sitemap page count and which are cached.',
    inputSchema: {},
    annotations: READ,
  }, () => api.get('/api/mcp/sitemaps'));

  tool(server, 'sitemaps_flush', {
    title: 'Flush sitemap caches',
    description: 'Forces sitemaps/feeds to regenerate on next crawl (run after bulk job changes).',
    inputSchema: {},
    annotations: IDEMPOTENT,
  }, () => api.post('/api/mcp/sitemaps/flush', {}));

  tool(server, 'alerts_preview', {
    title: 'Preview job alerts',
    description: 'Which subscribers would receive alerts for jobs created in the last N hours.',
    inputSchema: { hours: z.number().int().min(1).max(720).optional() },
    annotations: READ,
  }, (a) => api.get('/api/mcp/alerts/preview', a));

  tool(server, 'alerts_send', {
    title: 'Send job alerts',
    description: 'Email matching jobs (last 24h) to all active subscribers, or only the given subscriber_ids.',
    inputSchema: { subscriber_ids: z.array(id).optional() },
    annotations: WRITE,
  }, (a) => api.post('/api/mcp/alerts/send', a));

  tool(server, 'mail_test', {
    title: 'Send test email',
    inputSchema: { to: z.string().email(), subject: str, body: str },
    annotations: WRITE,
  }, (a) => api.post('/api/mcp/mail/test', a));

  tool(server, 'storage_list', {
    title: 'List public storage',
    description: 'Files/dirs under storage/app/public with sizes, totals and storage:link status.',
    inputSchema: { dir: str, limit: z.number().int().min(1).max(500).optional() },
    annotations: READ,
  }, (a) => api.get('/api/mcp/storage', a));

  tool(server, 'storage_delete', {
    title: 'Delete storage files', inputSchema: { paths: z.array(z.string()).min(1) }, annotations: DESTRUCTIVE,
  }, (a) => api.post('/api/mcp/storage/delete', a));

  tool(server, 'storage_orphans', {
    title: 'Find/clean orphan job images',
    description: 'Image files in job-sources/ not referenced by any queue item, and records whose file is missing. delete_orphans=true removes the files.',
    inputSchema: { delete_orphans: bool },
    annotations: DESTRUCTIVE,
  }, ({ delete_orphans }) => (delete_orphans ? api.post('/api/mcp/storage/orphans', { delete_orphans: true }) : api.get('/api/mcp/storage/orphans')));

  tool(server, 'maintenance_mode', {
    title: 'Maintenance mode',
    description: 'Put the site down (with optional bypass secret) or bring it back up.',
    inputSchema: { action: z.enum(['down', 'up']), secret: str, retry: z.number().int().optional() },
    annotations: IDEMPOTENT,
  }, ({ action, secret, retry }) => {
    const args = {};
    if (action === 'down') {
      if (secret) args['--secret'] = secret;
      if (retry) args['--retry'] = String(retry);
    }
    return api.post('/api/mcp/artisan', { command: action, arguments: args });
  });

  tool(server, 'schedule_list', {
    title: 'List scheduled tasks', inputSchema: {}, annotations: READ,
  }, () => api.post('/api/mcp/artisan', { command: 'schedule:list', arguments: {} }));
}

// ---------------------------------------------------------------------------
// Resources
// ---------------------------------------------------------------------------
function jsonResource(server, name, uri, meta, loader) {
  server.registerResource(name, uri, { mimeType: 'application/json', ...meta }, async (u) => {
    let data;
    try {
      data = await loader();
    } catch (err) {
      data = { success: false, error: err.message };
    }
    return { contents: [{ uri: u.href, mimeType: 'application/json', text: JSON.stringify(data, null, 2) }] };
  });
}

function registerResources(server) {
  jsonResource(server, 'health', 'jobs-site://health', { title: 'Health', description: 'Live health snapshot.' }, () => api.get('/api/mcp/health'));
  jsonResource(server, 'schema', 'jobs-site://schema', { title: 'DB schema', description: 'Tables and columns.' }, () => api.get('/api/mcp/schema'));
  jsonResource(server, 'categories', 'jobs-site://categories', { title: 'Categories' }, () => api.get('/api/categories'));
  jsonResource(server, 'cities', 'jobs-site://cities', { title: 'Cities' }, () => api.get('/api/cities'));
  jsonResource(server, 'settings', 'jobs-site://settings', { title: 'Site settings' }, () => api.get('/api/mcp/settings'));
  jsonResource(server, 'seo-audit', 'jobs-site://seo/audit', { title: 'SEO audit' }, () => api.get('/api/mcp/seo/audit'));
  jsonResource(server, 'analytics', 'jobs-site://analytics', { title: 'Analytics (30d)' }, () => api.get('/api/mcp/analytics', { days: 30 }));
  jsonResource(server, 'artisan-allowlist', 'jobs-site://artisan', { title: 'Artisan allow-list' }, () => api.get('/api/mcp/artisan'));
  jsonResource(server, 'sitemaps', 'jobs-site://sitemaps', { title: 'Sitemaps' }, () => api.get('/api/mcp/sitemaps'));
  jsonResource(server, 'queue', 'jobs-site://queue', { title: 'Queue status' }, () => api.get('/api/mcp/queue'));
  jsonResource(server, 'users-summary', 'jobs-site://users', { title: 'Users summary' }, () => api.get('/api/mcp/users', { per_page: 1 }));
}

// ---------------------------------------------------------------------------
// Prompts
// ---------------------------------------------------------------------------
function userPrompt(text) {
  return { messages: [{ role: 'user', content: { type: 'text', text } }] };
}

function registerPrompts(server) {
  server.registerPrompt('daily_ops_checklist', {
    title: 'Daily operations checklist',
    description: 'Walk through health, queue, scrapers, expired jobs, SEO gaps and push backlog, then propose actions.',
    argsSchema: {},
  }, async () => {
    let health = null;
    try { health = await api.get('/api/mcp/health'); } catch (err) { health = { error: err.message }; }
    return userPrompt(
      'You are operating the jobs-site platform via MCP. Perform a daily ops review:\n' +
      '1. site_overview + queue_status + logs_tail(level=ERROR) — flag failures.\n' +
      '2. scraper_status + scraper_queue_stats — are scrapers stuck? pending backlog?\n' +
      '3. jobs_deactivate_expired(dry_run=true) — how many to deactivate? Deactivate if reasonable.\n' +
      '4. seo_audit — summarise top issues; fix cheap ones with jobs_update / jobs_regenerate_schema.\n' +
      '5. push_stats — jobs_awaiting_push; run artisan_run push:send-new-jobs --dry-run first.\n' +
      'Finish with a short report and the concrete tool calls you executed.\n\n' +
      `Current health snapshot:\n${JSON.stringify(health, null, 2)}`,
    );
  });

  server.registerPrompt('publish_scraped_job', {
    title: 'Publish a scraped job',
    description: 'Take a scraper-queue item and publish it as a fully enriched, SEO-ready job listing.',
    argsSchema: { item_id: z.string().optional().describe('job_source_images id; omit to take the next pending item.') },
  }, async ({ item_id }) => userPrompt(
    `Publish ${item_id ? `scraper queue item #${item_id}` : 'the next pending scraper queue item (scraper_queue_next)'} as a job listing.\n` +
    'Steps: read the item; use ai_extract_job on its text/HTML if Gemini is configured; resolve category via categories_resolve and city via cities_list ' +
    '(create if missing); build a rich description_html, meta_description (<=160 chars), meta_keywords, deadline, salary, education, experience; ' +
    'call jobs_create; then scraper_queue_set_status(status=published, published_job_id=<new id>) and indexnow_submit(job_ids=[<id>]).',
  ));

  server.registerPrompt('seo_improvement_plan', {
    title: 'SEO improvement plan',
    description: 'Generate a prioritized SEO plan from the live audit and analytics.',
    argsSchema: { focus: z.string().optional().describe('e.g. schema, meta, content, internal links') },
  }, async ({ focus }) => {
    let audit = null;
    try { audit = await api.get('/api/mcp/seo/audit', { limit: 50 }); } catch (err) { audit = { error: err.message }; }
    return userPrompt(
      `Create a prioritized SEO improvement plan for the jobs site${focus ? ` focused on ${focus}` : ''}. ` +
      'Use jobs_update, jobs_regenerate_schema, posts_create (supporting guides), landing_link_create (internal links) and indexnow_submit to apply fixes.\n\n' +
      `Audit:\n${JSON.stringify(audit, null, 2)}`,
    );
  });

  server.registerPrompt('write_job_guide_post', {
    title: 'Write a job guide blog post',
    description: 'Draft and publish a helpful blog post about a job category, exam, or hiring process.',
    argsSchema: {
      topic: z.string().describe('e.g. "How to prepare for PPSC Lecturer test"'),
      keyword: z.string().optional(),
    },
  }, async ({ topic, keyword }) => userPrompt(
    `Write a well-structured HTML blog post for the jobs site about: ${topic}. Primary keyword: ${keyword || topic}. ` +
    'Pull real, current job data with jobs_search to reference live listings (link to their URLs). Include H2 sections, a FAQ, and a call to action to subscribe. ' +
    'Publish it with posts_create(is_published=true) and submit the URL via indexnow_submit.',
  ));

  server.registerPrompt('incident_triage', {
    title: 'Incident triage',
    description: 'Investigate an error or outage using logs, queue, schema and read-only SQL.',
    argsSchema: { symptom: z.string().describe('What is broken?') },
  }, async ({ symptom }) => userPrompt(
    `Triage this incident on the jobs site: "${symptom}".\n` +
    'Use site_overview, logs_tail(level=ERROR, lines=300), queue_status, routes_list, db_schema and db_query (read-only) to find the root cause. ' +
    'Where safe, remediate with artisan_run (optimize:clear, queue:retry, migrate --force) or cache_manage. Report cause, fix, and follow-ups.',
  ));

  server.registerPrompt('job_from_ad_image', {
    title: 'Publish job from an ad image/text (no Gemini needed)',
    description: 'You (the AI client) read a newspaper ad image or its text and publish a complete listing yourself.',
    argsSchema: {
      image_url: z.string().optional().describe('URL of the job ad image'),
      ad_text: z.string().optional().describe('Raw ad text if already transcribed'),
    },
  }, async ({ image_url, ad_text }) => userPrompt(
    'Publish a job listing from this advertisement without relying on ai_extract_job.\n' +
    (image_url ? `Image: ${image_url}\n` : '') + (ad_text ? `Ad text:\n${ad_text}\n` : '') +
    'Steps: 1) Transcribe/extract: title, department/company, city, province, positions, qualification, experience, age, BPS, salary, deadline, how to apply, newspaper. ' +
    '2) categories_resolve + cities_list (cities_create if missing). 3) Write SEO description_html (H2 sections: Overview, Vacancies, Eligibility, How to Apply, Important Dates), ' +
    'meta_description <=160 chars, meta_keywords, set flags (is_special_quota, has_walkin_interview...). 4) jobs_create. ' +
    (image_url ? '5) source_image_add(title, image_url, article_text=<transcription>) then source_image_publish(id, job_id). ' : '') +
    '6) indexnow_submit(job_ids=[id]) and sitemaps_flush. Report the job URL.',
  ));

  server.registerPrompt('weekly_content_plan', {
    title: 'Weekly content & growth plan',
    description: 'Use analytics, bookmarks, subscribers and SEO audit to plan the week: posts, landing pages, alerts, pushes.',
    argsSchema: {},
  }, async () => {
    const [analytics, bookmarks] = await Promise.all([
      api.get('/api/mcp/analytics', { days: 7 }).catch((e) => ({ error: e.message })),
      api.get('/api/mcp/bookmarks', { days: 7, limit: 10 }).catch((e) => ({ error: e.message })),
    ]);
    return userPrompt(
      'Plan this week for the jobs site. Using the data below plus seo_audit and subscribers_list: ' +
      'propose 3 blog posts (posts_create), 2 landing-page link groups (landing_link_create), which jobs to feature (jobs_toggle is_featured), ' +
      'a push broadcast (push_broadcast) and whether to run alerts_send. Execute the low-risk items and list the rest for approval.\n\n' +
      `Analytics (7d):\n${JSON.stringify(analytics, null, 2)}\n\nTop bookmarked (7d):\n${JSON.stringify(bookmarks, null, 2)}`,
    );
  });

  server.registerPrompt('cv_review', {
    title: 'Review a user CV',
    description: 'Critique and improve a CV builder document, optionally against a target job.',
    argsSchema: { cv_id: z.string(), job_id: z.string().optional() },
  }, async ({ cv_id, job_id }) => userPrompt(
    `Review CV #${cv_id} via cvs_get${job_id ? ` against job #${job_id} (jobs_get)` : ''}. ` +
    'Give an ATS-style score, list gaps, rewrite the summary and weak bullet points, suggest skills. ' +
    'Apply improvements with cvs_update only if explicitly asked; otherwise return the proposed JSON changes.',
  ));

  server.registerPrompt('cleanup_and_maintenance', {
    title: 'Storage & data cleanup',
    description: 'Find orphan images, stale scraper queue items, expired jobs and unused taxonomy, then clean safely.',
    argsSchema: {},
  }, async () => userPrompt(
    'Run a maintenance pass: storage_orphans (report first), scraper_queue_search(status=skipped, older than 30 days) -> scraper_queue_purge, ' +
    'jobs_deactivate_expired(dry_run=true), categories_list / cities_list with zero jobs (do NOT delete without confirming), ' +
    'queue_status failed jobs -> artisan_run queue:retry or queue:flush, then sitemaps_flush + cache_manage(clear). Summarise what was cleaned and what needs approval.',
  ));
}

// ---------------------------------------------------------------------------
export function createServer() {
  const server = new McpServer(
    { name: SERVER_NAME, version: SERVER_VERSION, websiteUrl: config.baseUrl },
    { capabilities: { logging: {} } },
  );
  registerSystemTools(server);
  registerJobTools(server);
  registerTaxonomyTools(server);
  registerScraperTools(server);
  registerGrowthTools(server);
  registerContentTools(server);
  registerAdminTools(server);
  registerResources(server);
  registerPrompts(server);
  return server;
}

async function runStdio() {
  const server = createServer();
  await server.connect(new StdioServerTransport());
  console.error(`${SERVER_NAME} running on stdio -> ${config.baseUrl}`);
}

function requireBearer(req, res, next) {
  const header = req.get('authorization') || '';
  const provided = header.startsWith('Bearer ') ? header.slice(7) : '';
  const a = Buffer.from(config.httpToken);
  const b = Buffer.from(provided);
  if (a.length && a.length === b.length && crypto.timingSafeEqual(a, b)) return next();
  return res.status(401).json({ error: 'Unauthorized MCP request.' });
}

async function runHttp() {
  if (!config.httpToken) {
    throw new Error('JOBS_SITE_MCP_HTTP_TOKEN (or MCP_API_TOKEN) is required for --http mode.');
  }
  const { default: express } = await import('express');
  const app = express();
  app.use(express.json({ limit: '4mb' }));
  app.get('/healthz', (_req, res) => res.json({ ok: true, name: SERVER_NAME, version: SERVER_VERSION }));
  app.use('/mcp', requireBearer);

  app.post('/mcp', async (req, res) => {
    const server = createServer();
    const transport = new StreamableHTTPServerTransport({ sessionIdGenerator: undefined });
    res.on('close', () => { void Promise.allSettled([transport.close(), server.close()]); });
    try {
      await server.connect(transport);
      await transport.handleRequest(req, res, req.body);
    } catch (err) {
      console.error('MCP HTTP error:', err);
      if (!res.headersSent) {
        res.status(500).json({ jsonrpc: '2.0', error: { code: -32603, message: 'Internal server error' }, id: null });
      }
    }
  });
  app.get('/mcp', (_req, res) => res.status(405).json({ error: 'Use POST /mcp (stateless Streamable HTTP).' }));
  app.delete('/mcp', (_req, res) => res.status(405).json({ error: 'Stateless sessions do not require DELETE.' }));

  app.listen(config.httpPort, () => console.error(`${SERVER_NAME} HTTP at http://localhost:${config.httpPort}/mcp -> ${config.baseUrl}`));
}

const isMain = Boolean(process.argv[1]) && import.meta.url === pathToFileURL(path.resolve(process.argv[1])).href;
if (isMain) {
  const runner = process.argv.includes('--http') ? runHttp : runStdio;
  runner().catch((err) => {
    console.error(`${SERVER_NAME} failed:`, err);
    process.exit(1);
  });
}
