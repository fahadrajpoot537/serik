<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class HealthEndpointTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Config::set('serik.health.token', 'test-health-token');
        Config::set('cache.default', 'array');
        Config::set('session.driver', 'array');
        Config::set('queue.default', 'sync');
        Config::set('scout.driver', 'null');
    }

    public function test_liveness_is_ok_without_dependencies(): void
    {
        $response = $this->getJson('/health/live');

        $response->assertOk()
            ->assertJson(['status' => 'ok'])
            ->assertHeader('X-Request-ID');
        $this->assertArrayNotHasKey('checks', $response->json());
    }

    public function test_readiness_hides_details_without_token(): void
    {
        $response = $this->getJson('/health/ready');

        $response->assertOk()
            ->assertJsonStructure(['status'])
            ->assertJsonMissingPath('checks.database');
    }

    public function test_readiness_details_with_token(): void
    {
        $response = $this->getJson('/health/ready', [
            'X-Serik-Health-Token' => 'test-health-token',
        ]);

        $response->assertOk()
            ->assertJsonPath('checks.database.status', 'ok')
            ->assertJsonPath('checks.redis.status', 'unused')
            ->assertJsonPath('checks.meilisearch.status', 'unused');
        $this->assertArrayNotHasKey('exception', $response->json());
        $this->assertArrayNotHasKey('sql', $response->json());
    }

    public function test_readiness_reports_meilisearch_down_without_throwing(): void
    {
        Config::set('scout.driver', 'meilisearch');
        Config::set('scout.meilisearch.host', 'http://127.0.0.1:9');
        Http::fake(function () {
            throw new \Illuminate\Http\Client\ConnectionException('meilisearch unavailable');
        });

        $response = $this->getJson('/health/ready', [
            'X-Serik-Health-Token' => 'test-health-token',
        ]);

        $response->assertOk()
            ->assertJsonPath('status', 'degraded')
            ->assertJsonPath('checks.meilisearch.status', 'down')
            ->assertJsonPath('checks.database.status', 'ok');
    }
}
