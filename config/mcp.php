<?php

return [

    /*
    |--------------------------------------------------------------------------
    | MCP API Token
    |--------------------------------------------------------------------------
    |
    | Shared secret required (as `Authorization: Bearer <token>` or
    | `X-MCP-Token`) on every /api/mcp/* request. When left empty the whole
    | MCP control surface is disabled and answers 404.
    |
    */

    'token' => env('MCP_API_TOKEN'),

    /*
    |--------------------------------------------------------------------------
    | Artisan allow-list
    |--------------------------------------------------------------------------
    |
    | Only these commands may be invoked remotely through /api/mcp/artisan.
    | Each entry maps a command to the options that callers may pass.
    |
    */

    'artisan' => [
        'scrape:pakistan-jobs' => ['--only-links', '--image-id', '--limit'],
        'scrape:jobsalert' => ['--only-links', '--image-id', '--limit'],
        'scrape:jobz-pk' => ['--only-links', '--image-id', '--limit'],
        'push:send-new-jobs' => ['--limit', '--dry-run'],
        'jobs:send-alerts' => [],
        'indexnow:submit-all' => ['--limit'],
        'schedule:run' => [],
        'queue:work' => ['--stop-when-empty', '--tries', '--max-jobs', '--max-time'],
        'queue:retry' => ['id'],
        'queue:flush' => [],
        'queue:clear' => ['--force'],
        'queue:failed' => [],
        'migrate' => ['--force'],
        'migrate:status' => [],
        'storage:link' => [],
        'cache:clear' => [],
        'config:clear' => [],
        'config:cache' => [],
        'route:clear' => [],
        'route:cache' => [],
        'view:clear' => [],
        'optimize:clear' => [],
        'optimize' => [],
        'about' => ['--json'],
    ],

    /*
    |--------------------------------------------------------------------------
    | Read-only SQL
    |--------------------------------------------------------------------------
    */

    'sql' => [
        'enabled' => env('MCP_SQL_ENABLED', true),
        'max_rows' => 500,
    ],

    'log_max_lines' => 1000,

];
