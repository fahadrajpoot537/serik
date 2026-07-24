<?php

namespace App\Support;

use Botble\Shortcode\Facades\Shortcode;
use Illuminate\Contracts\Http\Kernel as HttpKernel;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\View;

/**
 * Blackfire-style homepage timing collector (CLI / diagnostic only).
 */
final class HomepageTimingProfiler
{
    private float $startedAt;

    /** @var array<string, float> */
    private array $checkpoints = [];

    /** @var list<array<string, mixed>> */
    private array $queries = [];

    /** @var list<array<string, mixed>> */
    private array $cacheOps = [];

    /** @var list<array<string, mixed>> */
    private array $httpCalls = [];

    /** @var list<array<string, mixed>> */
    private array $views = [];

    /** @var list<array<string, mixed>> */
    private array $events = [];

    /** @var list<array<string, mixed>> */
    private array $shortcodes = [];

    /** @var list<array<string, mixed>> */
    private array $middleware = [];

    private ?float $routeMatchedAt = null;

    private ?string $matchedRoute = null;

    private bool $instrumented = false;

    public function __construct()
    {
        $this->startedAt = microtime(true);
    }

    public function checkpoint(string $label): void
    {
        $this->checkpoints[$label] = $this->elapsedMs();
    }

    public function instrument(): void
    {
        if ($this->instrumented) {
            return;
        }

        $this->instrumented = true;

        DB::listen(function ($query): void {
            $sql = $query->sql;
            $bindings = $query->bindings ?? [];
            foreach ($bindings as $binding) {
                $replacement = is_numeric($binding) ? (string) $binding : "'" . addslashes((string) $binding) . "'";
                $sql = preg_replace('/\?/', $replacement, (string) $sql, 1) ?? $sql;
            }

            $this->queries[] = [
                'ms' => round((float) $query->time, 2),
                'sql' => mb_substr(trim(preg_replace('/\s+/', ' ', $sql) ?? $sql), 0, 500),
                'connection' => $query->connectionName ?? 'default',
            ];
        });

        Event::listen('composing:*', function (string $eventName, array $data): void {
            $view = $data[0] ?? null;
            if (! is_object($view) || ! method_exists($view, 'getName')) {
                return;
            }

            $name = (string) $view->getName();
            $key = 'view:' . $name;
            if (! isset($this->views[$key])) {
                $this->views[$key] = [
                    'name' => $name,
                    'started_at' => microtime(true),
                    'ms' => 0.0,
                    'count' => 0,
                ];
            }

            $this->views[$key]['count']++;
        });

        Event::listen('Illuminate\Routing\Events\RouteMatched', function ($event): void {
            $this->routeMatchedAt = microtime(true);
            $route = $event->route;
            $this->matchedRoute = $route?->getName() ?? $route?->uri();
        });

        Event::listen('Illuminate\Cache\Events\CacheHit', function ($event): void {
            $this->cacheOps[] = ['op' => 'hit', 'key' => (string) $event->key, 'ms' => 0.0];
        });

        Event::listen('Illuminate\Cache\Events\CacheMissed', function ($event): void {
            $this->cacheOps[] = ['op' => 'miss', 'key' => (string) $event->key, 'ms' => 0.0];
        });

        Event::listen('Illuminate\Cache\Events\CacheWritten', function ($event): void {
            $this->cacheOps[] = ['op' => 'write', 'key' => (string) $event->key, 'ms' => 0.0];
        });

        Http::globalRequestMiddleware(function (callable $handler): callable {
            return function ($request, $options) use ($handler) {
                $started = microtime(true);
                $uri = (string) $request->getUri();

                return $handler($request, $options)->then(function ($response) use ($started, $uri) {
                    $this->httpCalls[] = [
                        'url' => mb_substr($uri, 0, 300),
                        'ms' => round((microtime(true) - $started) * 1000, 2),
                        'status' => method_exists($response, 'getStatusCode') ? $response->getStatusCode() : null,
                    ];

                    return $response;
                });
            };
        });
    }

    /**
     * @return array<string, mixed>
     */
    public function profileRequest(HttpKernel $kernel, Request $request): array
    {
        $requestStarted = microtime(true);
        DB::flushQueryLog();
        DB::enableQueryLog();

        $response = $kernel->handle($request);
        $requestMs = round((microtime(true) - $requestStarted) * 1000, 2);

        $kernel->terminate($request, $response);

        $routeResolutionMs = $this->routeMatchedAt !== null
            ? round(($this->routeMatchedAt - $requestStarted) * 1000, 2)
            : null;

        return [
            'status' => $response->getStatusCode(),
            'bytes' => strlen((string) $response->getContent()),
            'request_ms' => $requestMs,
            'route_resolution_ms' => $routeResolutionMs,
            'matched_route' => $this->matchedRoute,
        ];
    }

    /**
     * Benchmark each shortcode found on homepage content individually.
     *
     * @return list<array<string, mixed>>
     */
    public function benchmarkShortcodesFromPage(): array
    {
        $results = [];

        try {
            $homepageId = (int) \Botble\Base\Facades\BaseHelper::getHomepageId();
            if ($homepageId <= 0) {
                return $results;
            }

            $page = \Botble\Page\Models\Page::query()->find($homepageId);
            if (! $page) {
                return $results;
            }

            $content = (string) $page->content;
            if (! preg_match_all('/\[(\w+)([^\]]*)\]/', $content, $matches, PREG_SET_ORDER)) {
                return $results;
            }

            foreach ($matches as $match) {
                $name = $match[1];
                $attrString = $match[2] ?? '';
                $attrs = [];
                if (preg_match_all('/(\w+)="([^"]*)"/', $attrString, $attrMatches, PREG_SET_ORDER)) {
                    foreach ($attrMatches as $attrMatch) {
                        $attrs[$attrMatch[1]] = $attrMatch[2];
                    }
                }

                DB::flushQueryLog();
                DB::enableQueryLog();
                $q0 = count(DB::getQueryLog());

                $started = microtime(true);
                $html = '';
                try {
                    $html = Shortcode::compile(Shortcode::generateShortcode($name, $attrs), true)->toHtml();
                } catch (\Throwable $e) {
                    $html = 'ERROR: ' . $e->getMessage();
                }
                $ms = round((microtime(true) - $started) * 1000, 2);
                $queries = array_slice(DB::getQueryLog(), $q0);
                $queryMs = round(array_sum(array_column($queries, 'time')), 2);

                $results[] = [
                    'name' => $name,
                    'attrs' => $attrs,
                    'ms' => $ms,
                    'bytes' => strlen($html),
                    'queries' => count($queries),
                    'query_ms' => $queryMs,
                ];
            }
        } catch (\Throwable $e) {
            $results[] = ['error' => $e->getMessage()];
        }

        usort($results, static fn (array $a, array $b): int => ($b['ms'] ?? 0) <=> ($a['ms'] ?? 0));

        return $results;
    }

    /**
     * @return list<string>
     */
    public function middlewareStack(): array
    {
        try {
            $route = Route::getRoutes()->match(Request::create('/', 'GET'));

            return collect($route->gatherMiddleware())->values()->all();
        } catch (\Throwable) {
            return [];
        }
    }

    /**
     * @return array<string, mixed>
     */
    public function report(array $requestMeta): array
    {
        $queryTotalMs = round(array_sum(array_column($this->queries, 'ms')), 2);
        $slowQueries = $this->queries;
        usort($slowQueries, static fn (array $a, array $b): int => $b['ms'] <=> $a['ms']);

        $viewRows = array_values($this->views);
        usort($viewRows, static fn (array $a, array $b): int => $b['count'] <=> $a['count']);

        $shortcodeBench = $this->benchmarkShortcodesFromPage();

        $components = [];

        foreach ($this->checkpoints as $label => $ms) {
            $components[] = [
                'component' => $label,
                'ms' => round($ms, 2),
                'category' => 'bootstrap',
            ];
        }

        if ($requestMeta['route_resolution_ms'] !== null) {
            $components[] = [
                'component' => 'route_resolution',
                'ms' => $requestMeta['route_resolution_ms'],
                'category' => 'routing',
            ];
        }

        $components[] = [
            'component' => 'http_request_total',
            'ms' => $requestMeta['request_ms'],
            'category' => 'request',
        ];

        $components[] = [
            'component' => 'database_total',
            'ms' => $queryTotalMs,
            'category' => 'database',
            'queries' => count($this->queries),
        ];

        foreach (array_slice($slowQueries, 0, 15) as $i => $q) {
            $components[] = [
                'component' => 'sql_' . ($i + 1),
                'ms' => $q['ms'],
                'category' => 'database',
                'detail' => $q['sql'],
            ];
        }

        foreach ($shortcodeBench as $sc) {
            if (! isset($sc['name'])) {
                continue;
            }
            $components[] = [
                'component' => 'shortcode:' . $sc['name'],
                'ms' => $sc['ms'],
                'category' => 'shortcode',
                'bytes' => $sc['bytes'] ?? 0,
                'queries' => $sc['queries'] ?? 0,
                'query_ms' => $sc['query_ms'] ?? 0,
            ];
        }

        foreach (array_slice($this->httpCalls, 0, 20) as $i => $http) {
            $components[] = [
                'component' => 'http_' . ($i + 1),
                'ms' => $http['ms'],
                'category' => 'http',
                'detail' => $http['url'],
            ];
        }

        usort($components, static fn (array $a, array $b): int => $b['ms'] <=> $a['ms']);

        $cacheHits = count(array_filter($this->cacheOps, static fn (array $op): bool => $op['op'] === 'hit'));
        $cacheMisses = count(array_filter($this->cacheOps, static fn (array $op): bool => $op['op'] === 'miss'));

        return [
            'summary' => [
                'total_ms' => round($this->elapsedMs(), 2),
                'request_ms' => $requestMeta['request_ms'],
                'ttfb_estimate_ms' => $requestMeta['request_ms'],
                'html_kb' => round($requestMeta['bytes'] / 1024),
                'status' => $requestMeta['status'],
                'matched_route' => $requestMeta['matched_route'],
                'query_count' => count($this->queries),
                'query_total_ms' => $queryTotalMs,
                'cache_hits' => $cacheHits,
                'cache_misses' => $cacheMisses,
                'http_calls' => count($this->httpCalls),
                'views_rendered' => count($viewRows),
                'middleware_stack' => $this->middlewareStack(),
            ],
            'checkpoints' => $this->checkpoints,
            'ranked_components' => $components,
            'slowest_queries' => array_slice($slowQueries, 0, 25),
            'shortcode_benchmarks' => $shortcodeBench,
            'cache_operations' => array_slice($this->cacheOps, 0, 50),
            'http_calls' => $this->httpCalls,
            'top_views_by_count' => array_slice($viewRows, 0, 30),
        ];
    }

    private function elapsedMs(): float
    {
        return round((microtime(true) - $this->startedAt) * 1000, 2);
    }
}
