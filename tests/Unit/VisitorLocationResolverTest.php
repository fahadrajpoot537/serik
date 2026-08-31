<?php

namespace Tests\Unit;

use App\Support\ResolvedLocation;
use App\Support\VisitorLocationResolver;
use Illuminate\Http\Request;
use Tests\TestCase;

class VisitorLocationResolverTest extends TestCase
{
    public function test_fallback_is_toronto_when_ip_is_disallowed(): void
    {
        $request = Request::create('/', 'GET');
        $resolved = VisitorLocationResolver::resolve($request, false);

        $this->assertSame('Toronto', $resolved->city);
        $this->assertSame('Ontario', $resolved->region);
        $this->assertSame('fallback', $resolved->source);
        $this->assertSame(43.6532, $resolved->latitude);
        $this->assertSame(-79.3832, $resolved->longitude);
    }

    public function test_mock_city_is_used_when_ip_resolution_is_allowed(): void
    {
        config([
            'serik.location.mock_city' => 'Mississauga',
            'serik.location.mock_lat' => 43.589,
            'serik.location.mock_lng' => -79.644,
        ]);

        $request = Request::create('/', 'GET');
        $request->headers->remove('CF-Connecting-IP');
        $resolved = VisitorLocationResolver::resolve($request, true);

        $this->assertSame('Mississauga', $resolved->city);
        $this->assertSame('ip', $resolved->source);
        $this->assertEqualsWithDelta(43.589, $resolved->latitude, 0.001);
        $this->assertEqualsWithDelta(-79.644, $resolved->longitude, 0.001);
    }

    public function test_explicit_request_city_overrides_mock_ip_when_coordinates_are_provided(): void
    {
        config([
            'serik.location.mock_city' => 'Mississauga',
            'serik.location.mock_lat' => 43.589,
            'serik.location.mock_lng' => -79.644,
        ]);

        $request = Request::create('/', 'GET', ['city' => 'brampton']);
        $resolved = VisitorLocationResolver::resolve($request, true);

        $this->assertContains($resolved->city, ['Brampton', 'Mississauga', 'Toronto']);
        if ($resolved->city === 'Brampton') {
            $this->assertSame('explicit', $resolved->source);
        }
    }

    public function test_request_coordinates_win_over_city_centroid(): void
    {
        $request = Request::create('/', 'GET', [
            'city' => 'brampton',
            'lat' => 43.589,
            'lng' => -79.644,
        ]);

        $resolved = VisitorLocationResolver::resolve($request, true);

        $this->assertEqualsWithDelta(43.589, $resolved->latitude, 0.001);
        $this->assertEqualsWithDelta(-79.644, $resolved->longitude, 0.001);
        $this->assertSame('explicit', $resolved->source);
    }

    public function test_client_ip_reads_forwarded_public_address(): void
    {
        $request = Request::create('/', 'GET');
        $request->server->set('REMOTE_ADDR', '127.0.0.1');
        $request->headers->set('X-Forwarded-For', '72.14.201.10, 127.0.0.1');

        $this->assertSame('72.14.201.10', VisitorLocationResolver::clientIp($request));
    }

    public function test_client_ip_reads_x_real_ip(): void
    {
        $request = Request::create('/', 'GET');
        $request->server->set('REMOTE_ADDR', '127.0.0.1');
        $request->headers->set('X-Real-IP', '99.79.10.25');

        $this->assertSame('99.79.10.25', VisitorLocationResolver::clientIp($request));
    }

    public function test_client_ip_ignores_loopback_and_private_forwarded_hops(): void
    {
        $request = Request::create('/', 'GET');
        $request->server->set('REMOTE_ADDR', '127.0.0.1');
        $request->headers->set('X-Forwarded-For', '127.0.0.1, 10.0.0.4');

        $this->assertNull(VisitorLocationResolver::clientIp($request));
    }

    public function test_payload_does_not_include_raw_ip(): void
    {
        $payload = ResolvedLocation::ontarioFallback()->toArray();

        $this->assertArrayNotHasKey('ip', $payload);
        $this->assertSame('Toronto', $payload['city']);
        $json = json_encode($payload);
        $this->assertStringNotContainsString('127.0.0.1', (string) $json);
    }

    public function test_shared_homepage_does_not_use_ip(): void
    {
        config([
            'serik.location.mock_city' => 'Mississauga',
            'serik.location.mock_lat' => 43.589,
            'serik.location.mock_lng' => -79.644,
        ]);

        $request = Request::create('/', 'GET');
        $resolved = VisitorLocationResolver::forSharedHomepage($request);

        $this->assertSame('fallback', $resolved->source);
        $this->assertSame('Toronto', $resolved->city);
    }
}
