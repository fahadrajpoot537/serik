<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

class RequestProfilerMiddleware
{
    public function handle(Request $request, Closure $next): Response
    {
        if (! (bool) config('serik.profile_requests', false)) {
            return $next($request);
        }

        DB::flushQueryLog();
        DB::enableQueryLog();

        $started = microtime(true);
        $memoryStart = memory_get_usage(true);

        $response = $next($request);

        $queries = DB::getQueryLog();
        $queryTimes = array_column($queries, 'time');
        $elapsedMs = round((microtime(true) - $started) * 1000, 1);

        Log::channel('perf')->info('request_profile', [
            'method' => $request->method(),
            'path' => $request->path(),
            'status' => $response->getStatusCode(),
            'controller_ms' => $elapsedMs,
            'queries' => count($queries),
            'query_ms' => round(array_sum($queryTimes), 1),
            'slowest_query_ms' => $queryTimes !== [] ? max($queryTimes) : 0,
            'memory_mb' => round((memory_get_peak_usage(true) - $memoryStart) / 1048576, 1),
        ]);

        $response->headers->set('X-Request-Profile-Ms', (string) $elapsedMs);
        $response->headers->set('X-Request-Query-Count', (string) count($queries));

        return $response;
    }
}
