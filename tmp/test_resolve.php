<?php
require __DIR__.'/../vendor/autoload.php';
$app = require_once __DIR__.'/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);

use Illuminate\Http\Request;
use App\Http\Controllers\Api\CategoryApiController;

$request = Request::create('/api/categories/resolve', 'POST', ['title' => 'PPSC Junior Clerk Jobs']);
$response = (new CategoryApiController)->resolve($request);

print_r($response->getData());
