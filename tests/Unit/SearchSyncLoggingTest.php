<?php

namespace Tests\Unit;

use App\Jobs\SearchBatchJob;
use App\Support\PropertySearchSync;
use App\Support\SerikAuditLog;
use App\Support\SerikSafeLog;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use InvalidArgumentException;
use RuntimeException;
use Tests\TestCase;
use Throwable;

class SearchSyncLoggingTest extends TestCase
{
    public function test_search_sync_channel_is_defined_and_resolves(): void
    {
        $this->assertArrayHasKey('search_sync', config('logging.channels'));
        $this->assertSame('daily', config('logging.channels.search_sync.driver'));
        $this->assertTrue(SerikAuditLog::channelExists('search_sync'));
        Log::channel('search_sync')->info('search_sync_channel_ok', ['probe' => true]);
    }

    public function test_search_audit_events_can_be_logged(): void
    {
        Log::spy();

        SerikAuditLog::event(SerikAuditLog::DOMAIN_SEARCH, 'batch_indexed', [
            'count' => 2,
            'password' => 'should-not-appear',
        ]);

        Log::shouldHaveReceived('channel')->with('search_sync');
    }

    public function test_missing_search_sync_channel_does_not_throw(): void
    {
        $channels = config('logging.channels');
        unset($channels['search_sync']);
        config(['logging.channels' => $channels]);
        $this->app->forgetInstance('log');
        Log::clearResolvedInstances();

        SerikAuditLog::event(SerikAuditLog::DOMAIN_SEARCH, 'index_failed', ['error' => 'meili down'], 'warning');

        $this->assertTrue(true);
    }

    public function test_logger_failure_does_not_fail_search_batch_job(): void
    {
        Cache::put(PropertySearchSync::PENDING_CACHE_KEY, []);

        Log::shouldReceive('info')->andThrow(new RuntimeException('logger unavailable'));
        Log::shouldReceive('warning')->andThrow(new RuntimeException('logger unavailable'));
        Log::shouldReceive('debug')->andThrow(new RuntimeException('logger unavailable'));
        Log::shouldReceive('error')->andThrow(new RuntimeException('logger unavailable'));
        Log::shouldReceive('log')->andThrow(new RuntimeException('logger unavailable'));
        Log::shouldReceive('channel')->andThrow(new InvalidArgumentException('Log [search_sync] is not defined.'));
        Log::shouldReceive('getFacadeRoot')->andThrow(new RuntimeException('logger unavailable'));

        $job = new SearchBatchJob();
        $job->handle(new PropertySearchSync());

        $this->assertSame(0, (new PropertySearchSync())->pendingCount());
    }

    public function test_indexing_exception_is_not_swallowed(): void
    {
        Cache::put(PropertySearchSync::PENDING_CACHE_KEY, [1 => true]);

        $this->expectException(Throwable::class);

        (new SearchBatchJob())->handle(new PropertySearchSync());
    }

    public function test_safe_log_redacts_secrets(): void
    {
        $redacted = SerikSafeLog::redact([
            'password' => 'secret',
            'api_token' => 'abc',
            'count' => 3,
        ]);

        $this->assertSame('[redacted]', $redacted['password']);
        $this->assertSame('[redacted]', $redacted['api_token']);
        $this->assertSame(3, $redacted['count']);
    }
}
