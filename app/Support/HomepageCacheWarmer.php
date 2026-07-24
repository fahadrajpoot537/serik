<?php

namespace App\Support;

use App\Actions\HomepageFeaturedPropertiesAction;
use Botble\Base\Facades\BaseHelper;
use Botble\Page\Models\Page;
use Illuminate\Contracts\Http\Kernel as HttpKernel;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

/**
 * Pre-warms homepage-related caches after deploy or cache flush.
 */
final class HomepageCacheWarmer
{
    /** @var list<string> */
    public const PROPERTY_SUB_TYPES = [
        'Detached',
        'Semi-Detached',
        'Att/Row/Townhouse',
        'Condo Townhouse',
        'Condo Apartment',
        'Duplex',
    ];

    /**
     * @return list<array{step: string, ms: float, detail: string}>
     */
    public static function warm(?string $locale = null): array
    {
        $timings = [];
        $homepage = self::homepagePage();

        if ($locale !== null && $locale !== '') {
            app()->setLocale($locale);
        }

        $propertiesLimit = self::propertiesLimit($homepage);
        $agentIds = self::agentAccountIds($homepage);

        $timings[] = self::runStep('real_estate_count_cache.category', static function (): string {
            $count = RealEstateCountCache::categoryPropertyCounts()->count();

            return "{$count} categories";
        });

        $timings[] = self::runStep('real_estate_count_cache.property_sub_types', static function (): string {
            $rows = RealEstateCountCache::propertySubTypeCounts(self::PROPERTY_SUB_TYPES);

            return count($rows) . ' subtypes';
        });

        $timings[] = self::runStep('real_estate_count_cache.agent_counts', static function () use ($agentIds): string {
            if ($agentIds === []) {
                return 'skipped (no agent IDs on homepage)';
            }

            $counts = RealEstateCountCache::agentPropertyCountsFor($agentIds);

            return count($agentIds) . ' agents, ' . $counts->count() . ' with listings';
        });

        $timings[] = self::runStep('homepage_featured_properties', static function () use ($propertiesLimit): string {
            $result = app(HomepageFeaturedPropertiesAction::class)->handle($propertiesLimit);
            $sale = ($result['propertiesForSale'] ?? collect())->count();
            $sold = ($result['propertiesSold'] ?? collect())->count();

            return "limit={$propertiesLimit}, sale={$sale}, sold={$sold}";
        });

        $timings[] = self::runStep('homepage_response_html', static function (): string {
            $kernel = app(HttpKernel::class);
            $request = self::homepageRequest();
            $response = $kernel->handle($request);
            $kernel->terminate($request, $response);

            $cacheStatus = $response->headers->get('X-Serik-Homepage-Cache', 'unknown');
            $bytes = strlen((string) $response->getContent());

            return "status={$response->getStatusCode()}, cache={$cacheStatus}, bytes={$bytes}";
        });

        return $timings;
    }

    /**
     * @return list<array{step: string, ms: float, detail: string}>
     */
    private static function runStep(string $step, callable $callback): array
    {
        $t0 = microtime(true);

        try {
            $detail = (string) $callback();
        } catch (\Throwable $e) {
            $detail = 'error: ' . $e->getMessage();
        }

        return [
            'step' => $step,
            'ms' => round((microtime(true) - $t0) * 1000, 2),
            'detail' => $detail,
        ];
    }

    private static function homepagePage(): ?Page
    {
        try {
            $homepageId = (int) BaseHelper::getHomepageId();

            if ($homepageId <= 0) {
                return null;
            }

            return Page::query()->find($homepageId);
        } catch (\Throwable) {
            return null;
        }
    }

    private static function homepageContent(?Page $page): string
    {
        return (string) ($page?->content ?? '');
    }

    /**
     * @return array<string, string>
     */
    private static function parseShortcodeAttributes(string $content, string $name): array
    {
        if (! preg_match('/\[' . preg_quote($name, '/') . '([^\]]*)\]/', $content, $match)) {
            return [];
        }

        $attrs = [];

        if (preg_match_all('/(\w+)="([^"]*)"/', $match[1], $attrMatches, PREG_SET_ORDER)) {
            foreach ($attrMatches as $attr) {
                $attrs[$attr[1]] = $attr[2];
            }
        }

        return $attrs;
    }

    private static function propertiesLimit(?Page $page): int
    {
        $attrs = self::parseShortcodeAttributes(self::homepageContent($page), 'properties');

        return max(8, min(24, (int) ($attrs['limit'] ?? 8)));
    }

    /**
     * @return list<int>
     */
    private static function agentAccountIds(?Page $page): array
    {
        $attrs = self::parseShortcodeAttributes(self::homepageContent($page), 'agents');
        $raw = (string) ($attrs['account_ids'] ?? '');

        if ($raw === '') {
            return [];
        }

        return array_values(array_unique(array_filter(array_map('intval', explode(',', $raw)))));
    }

    private static function homepageRequest(): Request
    {
        $appUrl = (string) config('app.url', 'http://localhost');
        $host = parse_url($appUrl, PHP_URL_HOST) ?: 'localhost';
        $scheme = parse_url($appUrl, PHP_URL_SCHEME) ?: 'http';
        $port = parse_url($appUrl, PHP_URL_PORT);

        $server = [
            'HTTP_HOST' => $host . ($port ? ':' . $port : ''),
            'SERVER_NAME' => $host,
            'REQUEST_URI' => '/',
            'HTTPS' => $scheme === 'https' ? 'on' : 'off',
            'HTTP_ACCEPT' => 'text/html',
        ];

        return Request::create('/', 'GET', [], [], [], $server);
    }

    /**
     * Clear homepage warm targets (for benchmarking only).
     */
    public static function forgetWarmTargets(): void
    {
        $version = RealEstateCountCache::version();
        Cache::forget('re_category_property_counts_v1:' . $version);

        $typesKey = md5(implode('|', self::PROPERTY_SUB_TYPES));
        Cache::forget('re_property_subtype_counts_v1:' . $version . ':' . $typesKey);

        $homepage = self::homepagePage();
        $agentIds = self::agentAccountIds($homepage);

        if ($agentIds !== []) {
            sort($agentIds);
            $idsKey = md5(implode(',', $agentIds));
            Cache::forget('re_agent_property_counts_v1:scoped:' . $version . ':' . $idsKey);
        }

        $propertiesLimit = self::propertiesLimit($homepage);
        $featuredVersion = HomepageFeaturedCache::version();
        Cache::forget("homepage_featured_props_v4:{$featuredVersion}:ontario:{$propertiesLimit}");

        HomepageResponseCache::bump();
    }
}
