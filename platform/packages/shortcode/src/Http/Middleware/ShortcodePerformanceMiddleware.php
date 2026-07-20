<?php

namespace Botble\Shortcode\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

class ShortcodePerformanceMiddleware
{
    public function handle(Request $request, Closure $next): Response
    {
        $startTime = microtime(true);

        $response = $next($request);

        $executionTime = microtime(true) - $startTime;

        $response->headers->set('X-Shortcode-Execution-Time', number_format($executionTime, 4));

        if ($executionTime > 2.0) {
            Log::warning(sprintf(
                'Shortcode render-ui-blocks took %s seconds. Shortcode: %s, Attributes: %s',
                number_format($executionTime, 4),
                $request->input('name'),
                json_encode($request->input('attributes', []))
            ));
        }

        return $response;
    }
}
