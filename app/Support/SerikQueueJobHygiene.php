<?php

namespace App\Support;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Detect and prune duplicate queued jobs (e.g. SyncLiveJob pile-up when workers stop).
 */
final class SerikQueueJobHygiene
{
    /**
     * @return array{matched: int, deleted: int, kept_ids: list<int>}
     */
    public static function pruneDuplicates(
        string $displayName,
        ?string $queue = null,
        int $keepNewest = 1,
        bool $dryRun = false,
    ): array {
        $keepNewest = max(0, $keepNewest);
        // Payloads store displayName as JSON ("App\\Jobs\\SyncLiveJob"). Match
        // on the short class name so backslash escaping never false-negatives.
        $short = class_basename(str_replace('\\\\', '\\', $displayName));
        $needle = '%' . addcslashes($short, '%_') . '%';

        $query = DB::table('jobs')
            ->where('payload', 'like', $needle)
            ->orderByDesc('id');

        if ($queue !== null && $queue !== '') {
            $query->where('queue', $queue);
        }

        $ids = $query->pluck('id')->map(static fn ($id): int => (int) $id)->all();
        $matched = count($ids);

        if ($matched <= $keepNewest) {
            return [
                'matched' => $matched,
                'deleted' => 0,
                'kept_ids' => $ids,
            ];
        }

        $keep = array_slice($ids, 0, $keepNewest);
        $delete = array_slice($ids, $keepNewest);

        if ($delete !== [] && ! $dryRun) {
            foreach (array_chunk($delete, 500) as $chunk) {
                DB::table('jobs')->whereIn('id', $chunk)->delete();
            }
        }

        return [
            'matched' => $matched,
            'deleted' => count($delete),
            'kept_ids' => $keep,
        ];
    }

    public static function countPending(string $displayName, ?string $queue = null): int
    {
        $short = class_basename(str_replace('\\\\', '\\', $displayName));
        $needle = '%' . addcslashes($short, '%_') . '%';
        $query = DB::table('jobs')->where('payload', 'like', $needle);

        if ($queue !== null && $queue !== '') {
            $query->where('queue', $queue);
        }

        return (int) $query->count();
    }

    /**
     * Remove reserved jobs that exceeded Laravel max attempts (stuck poison pills).
     */
    public static function purgeExceededAttempts(int $limit = 50): int
    {
        $limit = max(1, min(500, $limit));
        $removed = 0;

        $rows = DB::table('jobs')
            ->whereNotNull('reserved_at')
            ->orderBy('id')
            ->limit($limit)
            ->get(['id', 'payload', 'attempts']);

        foreach ($rows as $row) {
            $payload = json_decode((string) $row->payload, true);
            if (! is_array($payload)) {
                continue;
            }

            $maxTries = (int) ($payload['maxTries'] ?? $payload['maxExceptions'] ?? 0);
            if ($maxTries <= 0) {
                continue;
            }

            if ((int) $row->attempts >= $maxTries) {
                DB::table('jobs')->where('id', (int) $row->id)->delete();
                $removed++;
                Log::warning('[SerikQueueJobHygiene] purged exceeded-attempts job', [
                    'id' => (int) $row->id,
                    'display' => $payload['displayName'] ?? null,
                    'attempts' => (int) $row->attempts,
                    'maxTries' => $maxTries,
                ]);
            }
        }

        return $removed;
    }

    /**
     * @return array<string, array<string, mixed>>
     */
    public static function healConfiguredDuplicates(bool $dryRun = false): array
    {
        $report = [];
        $rules = (array) config('serik.orchestration.duplicate_job_rules', []);

        foreach ($rules as $rule) {
            if (! is_array($rule)) {
                continue;
            }

            $class = (string) ($rule['class'] ?? '');
            if ($class === '') {
                continue;
            }

            $queue = isset($rule['queue']) ? (string) $rule['queue'] : null;
            $keep = max(0, (int) ($rule['keep'] ?? 1));
            $key = class_basename($class) . ($queue ? '@' . $queue : '');

            try {
                $report[$key] = self::pruneDuplicates($class, $queue, $keep, $dryRun);
            } catch (Throwable $e) {
                $report[$key] = ['error' => $e->getMessage()];
            }
        }

        return $report;
    }
}
