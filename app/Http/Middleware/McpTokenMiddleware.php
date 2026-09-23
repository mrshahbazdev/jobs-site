<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class McpTokenMiddleware
{
    public function handle(Request $request, Closure $next): Response
    {
        $expected = (string) config('mcp.token');

        if ($expected === '') {
            abort(404);
        }

        $provided = $request->bearerToken() ?? $request->header('X-MCP-Token');

        if (! is_string($provided) || ! hash_equals($expected, $provided)) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid or missing MCP token.',
            ], 401);
        }

        $request->headers->set('Accept', 'application/json');

        return $next($request);
    }
}
