<?php

namespace Tests\Unit;

use Botble\RealEstate\Services\PropertySearchService;
use Tests\TestCase;

class MeilisearchResilienceTest extends TestCase
{
    public function test_meilisearch_timeouts_are_configured(): void
    {
        $this->assertArrayHasKey('timeout', config('scout.meilisearch'));
        $this->assertArrayHasKey('connect_timeout', config('scout.meilisearch'));
        $this->assertGreaterThan(0, (float) config('scout.meilisearch.timeout'));
        $this->assertGreaterThan(0, (float) config('scout.meilisearch.connect_timeout'));
    }

    public function test_is_available_is_false_when_scout_is_not_meilisearch(): void
    {
        if (! class_exists(PropertySearchService::class)) {
            $this->markTestSkipped('PropertySearchService is not autoloaded in this test process.');
        }

        config(['scout.driver' => 'null']);

        $this->assertFalse((new PropertySearchService())->isAvailable());
    }

    public function test_property_search_health_uses_optional_cache(): void
    {
        $src = (string) file_get_contents(base_path('platform/plugins/real-estate/src/Services/PropertySearchService.php'));

        $this->assertStringContainsString('SerikCache::get', $src);
        $this->assertStringContainsString('config(\'scout.meilisearch.timeout\'', $src);
    }

    public function test_meili_heal_config_defaults_preserve_behavior(): void
    {
        $this->assertTrue((bool) config('serik.health.meili_heal.enabled'));
        $this->assertFalse((bool) config('serik.health.meili_heal.auto_start_process'));
        $this->assertSame('SerikMeilisearch', (string) config('serik.health.meili_heal.service_name'));
    }

    public function test_queue_heal_includes_meilisearch_step(): void
    {
        $src = (string) file_get_contents(base_path('app/Support/SerikQueueSelfHealService.php'));
        $this->assertStringContainsString('SerikMeilisearchHealth', $src);
    }
}
