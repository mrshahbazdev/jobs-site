# jobs-site MCP server

A [Model Context Protocol](https://modelcontextprotocol.io) server that gives AI clients
(Claude Desktop, Cursor, Windsurf, custom agents) a full operational control plane over the
jobs-site Laravel platform: jobs, taxonomy, scrapers, SEO, AI extraction, push, content,
subscribers, users, settings, queue, logs, schema, read-only SQL and allow-listed Artisan.

```
AI client  ──stdio / Streamable HTTP──▶  mcp/server.js  ──HTTPS + Bearer token──▶  /api/mcp/* , /api/v2/* (Laravel)
```

## 1. Laravel side

1. Generate a token and add it to the app `.env`:
   ```bash
   openssl rand -hex 32
   ```
   ```dotenv
   MCP_API_TOKEN=<token>
   MCP_SQL_ENABLED=true   # set false to disable db_query
   ```
   While `MCP_API_TOKEN` is empty every `/api/mcp/*` route answers `404`.
2. `php artisan config:clear` (or `optimize:clear`) after changing `.env`.

All `/api/mcp/*` routes go through `McpTokenMiddleware` and accept the token as
`Authorization: Bearer <token>` or `X-MCP-Token: <token>`.
Allow-listed Artisan commands and their options live in `config/mcp.php`.

## 2. Node MCP server

```bash
cd mcp
npm install
```

Environment (read from process env, falling back to the repo root `.env`):

| Variable | Default | Purpose |
| --- | --- | --- |
| `JOBS_SITE_URL` | `APP_URL` or `http://127.0.0.1:8000` | Base URL of the Laravel app |
| `MCP_API_TOKEN` | – | Bearer token for `/api/mcp/*` (required) |
| `JOBS_SITE_MCP_PORT` | `3040` | Port for `--http` mode |
| `JOBS_SITE_MCP_HTTP_TOKEN` | `MCP_API_TOKEN` | Bearer token clients must send to the HTTP transport |
| `JOBS_SITE_MCP_TIMEOUT_MS` | `120000` | Per-request timeout (scrapers can be slow) |

### stdio (Claude Desktop / Cursor)

```json
{
  "mcpServers": {
    "jobs-site": {
      "command": "node",
      "args": ["/absolute/path/jobs-site/mcp/server.js"],
      "env": {
        "JOBS_SITE_URL": "https://your-domain.com",
        "MCP_API_TOKEN": "<token>"
      }
    }
  }
}
```

### Streamable HTTP (remote agents)

```bash
JOBS_SITE_URL=https://your-domain.com MCP_API_TOKEN=<token> npm run start:http
# POST http://localhost:3040/mcp  with  Authorization: Bearer <token>
# GET  http://localhost:3040/healthz
```

### Smoke test

```bash
JOBS_SITE_URL=http://127.0.0.1:8000 MCP_API_TOKEN=<token> npm run smoke -- --strict
```

## 3. Surface

### Tools (73)

| Area | Tools |
| --- | --- |
| System | `site_overview`, `db_schema`, `db_query` (read-only), `routes_list`, `logs_tail`, `artisan_commands`, `artisan_run`, `queue_status`, `cache_manage` |
| Jobs | `jobs_search`, `jobs_get`, `jobs_stats`, `jobs_analytics`, `jobs_create`, `jobs_update`, `jobs_delete`, `jobs_toggle`, `jobs_duplicate`, `jobs_bulk_create`, `jobs_bulk_status`, `jobs_bulk_delete`, `jobs_deactivate_expired`, `jobs_regenerate_schema` |
| Taxonomy | `categories_list/create/resolve/update/delete/merge`, `cities_list/create/update/delete` |
| Scrapers | `scraper_status`, `scraper_trigger`, `scraper_process_image`, `scraper_queue_stats/search/next/set_status/bulk_status/purge` |
| Growth | `seo_audit`, `indexnow_submit`, `ai_extract_job` (Gemini), `push_stats`, `push_broadcast` |
| Content | `posts_*`, `settings_*`, `comments_list/moderate`, `subscribers_*`, `landing_get`, `landing_group_*`, `landing_link_*`, `home_blocks_*`, `users_list` |

Every tool returns JSON as text **and** `structuredContent`; API errors come back as
`{ success: false, error, status, errors? }` with `isError: true`. Destructive tools are
annotated with `destructiveHint` so clients can ask for confirmation.

### Resources

`jobs-site://health`, `jobs-site://schema`, `jobs-site://categories`, `jobs-site://cities`,
`jobs-site://settings`, `jobs-site://seo/audit`, `jobs-site://analytics`, `jobs-site://artisan`.

### Prompts

`daily_ops_checklist`, `publish_scraped_job`, `seo_improvement_plan`, `write_job_guide_post`,
`incident_triage`.

## 4. Security notes

* Keep `MCP_API_TOKEN` secret; rotate by changing `.env` and clearing config cache.
* `db_query` accepts a single `SELECT/WITH/EXPLAIN/PRAGMA` statement, rejects mutating
  keywords and caps rows at `config('mcp.sql.max_rows')`. Disable with `MCP_SQL_ENABLED=false`.
* `artisan_run` only executes commands/options present in `config('mcp.artisan')`.
* `logs_tail` is restricted to files inside `storage/logs`.
* Run the HTTP transport behind TLS (reverse proxy) when exposing it beyond localhost.
