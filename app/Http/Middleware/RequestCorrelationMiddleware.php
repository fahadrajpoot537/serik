<?php

namespace App\Http\Middleware;

use App\Support\SerikSafeLog;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\Response;

/**
 * Correlate IIS/FastCGI timestamps with Laravel logs via X-Request-ID.
 */
class RequestCorrelationMiddleware
{
    public function handle(Request $request, Closure $next): Response
    {
        $id = $this->resolveRequestId($request);
        $request->headers->set('X-Request-ID', $id);
        $request->attributes->set('request_id', $id);
        Log::withContext(['request_id' => $id]);

        $started = microtime(true);
        $response = $next($request);
        $durationMs = (int) round((microtime(true) - $started) * 1000);

        $response->headers->set('X-Request-ID', $id);

        $this->maybeLog($request, $response, $durationMs);

        return $response;
    }

    private function resolveRequestId(Request $request): string
    {
        $incoming = trim((string) $request->headers->get('X-Request-ID', ''));
        if ($incoming !== '' && preg_match('/^[A-Za-z0-9._-]{8,64}$/', $incoming)) {
            return $incoming;
        }

        return (string) Str::uuid();
    }

    private function maybeLog(Request $request, Response $response, int $durationMs): void
    {
        if ($this->isStaticPath($request) || $this->isHealthPath($request)) {
            return;
        }

        $status = $response->getStatusCode();
        $slowMs = max(250, (int) config('serik.request_id.slow_ms', 2000));
        $isError = $status >= 500;
        $isSlow = $durationMs >= $slowMs;

        if (! $isError && ! $isSlow && ! (bool) config('serik.request_id.log_success', false)) {
            return;
        }

        SerikSafeLog::write($isError ? 'error' : 'info', 'http_request', [
            'request_id' => $request->attributes->get('request_id'),
            'method' => $request->method(),
            'route' => optional($request->route())->getName(),
            'path' => $request->path(),
            'status' => $status,
            'duration_ms' => $durationMs,
        ]);
    }

    private function isStaticPath(Request $request): bool
    {
        $path = strtolower($request->path());

        return (bool) preg_match('/\\.(?:css|js|mjs|map|png|jpe?g|gif|webp|svg|ico|woff2?|ttf|eot|mp4|webm|pdf)$/', $path);
    }

    private function isHealthPath(Request $request): bool
    {
        $path = ltrim($request->path(), '/');

        return in_array($path, ['up', 'health/live', 'health/ready'], true);
    }
}
