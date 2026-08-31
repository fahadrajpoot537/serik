<?php

namespace Tests\Unit;

use App\Support\HomepageResponseCache;
use App\Support\SerikCache;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use RuntimeException;
use Tests\TestCase;

class CacheResilienceTest extends TestCase
{
    public function test_remember_falls_back_to_source_when_cache_get_throws(): void
    {
        Cache::shouldReceive('get')->andThrow(new RuntimeException('Redis connection refused'));
        Cache::shouldReceive('lock')->andThrow(new RuntimeException('Redis connection refused'));
        Cache::shouldReceive('remember')->andThrow(new RuntimeException('Redis connection refused'));

        $value = SerikCache::remember('featured:test', 60, static fn (): string => 'from-source');

        $this->assertSame('from-source', $value);
    }

    public function test_optional_get_returns_default_when_store_unavailable(): void
    {
        Cache::shouldReceive('get')->andThrow(new RuntimeException('Memurai stopped'));

        $this->assertNull(SerikCache::get('homepage_html_v5:missing'));
        $this->assertSame(1, SerikCache::get('homepage_response_cache_version_v4', 1));
    }

    public function test_homepage_shared_html_cache_miss_when_redis_down(): void
    {
        Cache::shouldReceive('get')->andThrow(new RuntimeException('Memurai stopped'));

        $html = HomepageResponseCache::getSharedHtml(Request::create('/', 'GET'));

        $this->assertNull($html);
    }

    public function test_session_driver_is_not_silently_switched_to_null(): void
    {
        $this->assertSame('array', config('session.driver'));
        $this->assertContains(config('session.driver'), ['array', 'file', 'cookie', 'database', 'redis']);
    }
}
