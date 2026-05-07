<?php

return [

    /*
    |--------------------------------------------------------------------------
    | IndexNow API Key
    |--------------------------------------------------------------------------
    |
    | The API key used to verify domain ownership with IndexNow-compatible
    | search engines (Bing, Yandex, Seznam, Naver, etc.).
    |
    */

    'key' => env('INDEXNOW_KEY', '595fa1a2b8294f73b47236791dcf3646'),

    /*
    |--------------------------------------------------------------------------
    | IndexNow Enabled
    |--------------------------------------------------------------------------
    |
    | Toggle IndexNow submissions on or off without removing the integration.
    |
    */

    'enabled' => env('INDEXNOW_ENABLED', true),

    /*
    |--------------------------------------------------------------------------
    | IndexNow Endpoint
    |--------------------------------------------------------------------------
    |
    | The search engine endpoint that receives IndexNow submissions.
    | api.indexnow.org fans out to all participating engines.
    |
    */

    'endpoint' => env('INDEXNOW_ENDPOINT', 'https://api.indexnow.org/IndexNow'),

];
