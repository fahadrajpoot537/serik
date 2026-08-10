<?php

namespace App\Support;

use App\Models\GhlMlsSyncTask;
use App\Services\Treb\TrebArchiveImportService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Throwable;

/**
 * Detect corrupted / orphaned / incomplete state and apply safe recoveries.
 * Never deletes production property data; only resets sync checkpoints / stuck flags.
 */
final class SerikReliabilityService
{
    /**
     * @return array{ok: bool, issues: list<array<string, mixed>>, recovered: array<string, mixed>}
     */
    public function validate(bool $recover = false): array
    {
        $issues = [];
        $recovered = [];

        $issues = array_merge($issues, $this->checkArchiveProgress());
        $issues = array_merge($issues, $this->checkGhlStuck());
        $issues = array_merge($issues, $this->checkSearchCheckpoints());
        $issues = array_merge($issues, $this->checkDuplicateExternalIds());
        $issues = array_merge($issues, $this->checkOrphanQueueJobs());

        if ($recover) {
            $recovered = $this->recoverSafe();
        }

        $ok = $issues === [] || collect($issues)->every(fn ($i) => ($i['severity'] ?? 'info') === 'info');

        SerikAuditLog::event(SerikAuditLog::DOMAIN_RELIABILITY, $recover ? 'validate_recover' : 'validate', [
            'issue_count' => count($issues),
            'ok' => $ok,
            'recovered' => $recovered,
        ], $ok ? 'info' : 'warning');

        return [
            'ok' => $ok,
            'issues' => $issues,
            'recovered' => $recovered,
        ];
    }

    /**
     * Safe automatic recovery used by queue heal + reliability command.
     *
     * @return array<string, mixed>
     */
    public function recoverSafe(): array
    {
        $out = [
            'ghl_stuck_reset' => 0,
            'search_checkpoint' => ['restored' => 0, 'requeued_inflight' => 0],
            'archive_cache_resync' => false,
        ];

        try {
            $out['ghl_stuck_reset'] = $this->resetStuckGhlTasks();
        } catch (Throwable $e) {
            $out['ghl_error'] = $e->getMessage();
        }

        try {
            $out['search_checkpoint'] = app(PropertySearchSync::class)->recoverFromCheckpoint();
        } catch (Throwable $e) {
            $out['search_error'] = $e->getMessage();
        }

        try {
            $out['archive_cache_resync'] = $this->resyncArchiveProgressCache();
        } catch (Throwable $e) {
            $out['archive_error'] = $e->getMessage();
        }

        SerikAuditLog::event(SerikAuditLog::DOMAIN_RELIABILITY, 'recover', $out);

        return $out;
    }

    public function resetStuckGhlTasks(): int
    {
        $minutes = max(10, (int) config('serik.reliability.ghl_stuck_minutes', 45));

        return (int) GhlMlsSyncTask::query()
            ->where('status', GhlMlsSyncTask::STATUS_PROCESSING)
            ->where(function ($q) use ($minutes): void {
                $q->whereNull('started_at')
                    ->orWhere('started_at', '<', now()->subMinutes($minutes));
            })
            ->update([
                'status' => GhlMlsSyncTask::STATUS_PENDING,
                'last_error' => 'Auto-reset stuck processing (worker crash recovery)',
                'queued_at' => now(),
                'started_at' => null,
            ]);
    }

    protected function resyncArchiveProgressCache(): bool
    {
        /** @var TrebArchiveImportService $service */
        $service = app(TrebArchiveImportService::class);
        $progress = $service->readProgress();
        $service->writeProgress($progress);

        return true;
    }

    /**
     * @return list<array<string, mixed>>
     */
    protected function checkArchiveProgress(): array
    {
        $issues = [];
        $path = storage_path('app/' . TrebArchiveImportService::PROGRESS_PATH);
        $decoded = SerikAtomicFile::readJson($path);

        if ($decoded === null && is_file($path)) {
            $issues[] = [
                'code' => 'archive_progress_corrupt',
                'severity' => 'warning',
                'message' => 'TREB archive progress.json unreadable (bak also failed)',
            ];
        }

        if (is_array($decoded) && empty($decoded['year'])) {
            $issues[] = [
                'code' => 'archive_progress_incomplete',
                'severity' => 'warning',
                'message' => 'Archive progress missing year cursor',
            ];
        }

        return $issues;
    }

    /**
     * @return list<array<string, mixed>>
     */
    protected function checkGhlStuck(): array
    {
        $minutes = max(10, (int) config('serik.reliability.ghl_stuck_minutes', 45));
        $stuck = (int) GhlMlsSyncTask::query()
            ->where('status', GhlMlsSyncTask::STATUS_PROCESSING)
            ->where(function ($q) use ($minutes): void {
                $q->whereNull('started_at')
                    ->orWhere('started_at', '<', now()->subMinutes($minutes));
            })
            ->count();

        if ($stuck === 0) {
            return [];
        }

        return [[
            'code' => 'ghl_stuck_processing',
            'severity' => 'warning',
            'message' => "{$stuck} GHL MLS task(s) stuck in processing > {$minutes}m",
            'count' => $stuck,
        ]];
    }

    /**
     * @return list<array<string, mixed>>
     */
    protected function checkSearchCheckpoints(): array
    {
        $issues = [];
        if (! Schema::hasTable('serik_search_sync_pending')) {
            return [[
                'code' => 'search_checkpoint_missing_table',
                'severity' => 'info',
                'message' => 'Search checkpoint tables not migrated yet',
            ]];
        }

        $pendingDb = (int) DB::table('serik_search_sync_pending')->count();
        $inflight = (int) DB::table('serik_search_sync_inflight')->count();
        $pendingCache = app(PropertySearchSync::class)->pendingCount();

        if ($pendingDb > 0 && $pendingCache === 0) {
            $issues[] = [
                'code' => 'search_cache_empty_db_pending',
                'severity' => 'warning',
                'message' => "Search cache empty but DB pending={$pendingDb} (incomplete sync / cache flush)",
                'pending_db' => $pendingDb,
            ];
        }

        $staleMinutes = max(5, (int) config('serik.search_sync.inflight_stale_minutes', 30));
        $staleInflight = (int) DB::table('serik_search_sync_inflight')
            ->where('claimed_at', '<', now()->subMinutes($staleMinutes))
            ->count();

        if ($staleInflight > 0) {
            $issues[] = [
                'code' => 'search_stale_inflight',
                'severity' => 'warning',
                'message' => "{$staleInflight} search IDs claimed but not completed (crash mid-index)",
                'count' => $staleInflight,
            ];
        }

        if ($inflight > 0) {
            $issues[] = [
                'code' => 'search_inflight',
                'severity' => 'info',
                'message' => "{$inflight} search ID(s) currently in-flight",
                'count' => $inflight,
            ];
        }

        return $issues;
    }

    /**
     * @return list<array<string, mixed>>
     */
    protected function checkDuplicateExternalIds(): array
    {
        try {
            $dupes = (int) DB::selectOne(
                'SELECT COUNT(*) AS c FROM (
                    SELECT external_id FROM re_properties
                    WHERE external_id IS NOT NULL AND external_id != ?
                    GROUP BY external_id HAVING COUNT(*) > 1
                    LIMIT 20
                ) AS d',
                ['']
            )->c;

            if ($dupes > 0) {
                return [[
                    'code' => 'duplicate_external_id',
                    'severity' => 'error',
                    'message' => "Found duplicate re_properties.external_id groups ({$dupes}+) — unique index may be missing",
                    'count' => $dupes,
                ]];
            }
        } catch (Throwable $e) {
            return [[
                'code' => 'duplicate_check_failed',
                'severity' => 'info',
                'message' => $e->getMessage(),
            ]];
        }

        return [];
    }

    /**
     * @return list<array<string, mixed>>
     */
    protected function checkOrphanQueueJobs(): array
    {
        $staleSeconds = max(120, (int) config('serik.orchestration.stale_reserved_seconds', 900));
        $stale = (int) DB::table('jobs')
            ->whereNotNull('reserved_at')
            ->where('reserved_at', '<', time() - $staleSeconds)
            ->count();

        if ($stale === 0) {
            return [];
        }

        return [[
            'code' => 'stale_reserved_jobs',
            'severity' => 'warning',
            'message' => "{$stale} queue job(s) reserved past stale TTL (worker crash)",
            'count' => $stale,
        ]];
    }
}
