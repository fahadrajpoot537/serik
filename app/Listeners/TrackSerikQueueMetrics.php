<?php

namespace App\Listeners;

use App\Support\SerikQueueMetrics;
use Illuminate\Queue\Events\JobExceptionOccurred;
use Illuminate\Queue\Events\JobFailed;
use Illuminate\Queue\Events\JobProcessed;
use Illuminate\Queue\Events\JobProcessing;
use Illuminate\Support\Facades\Cache;
use Throwable;

/**
 * Additive metrics for queue orchestration. Does not alter job outcomes.
 */
class TrackSerikQueueMetrics
{
    public function handleProcessing(JobProcessing $event): void
    {
        try {
            $id = $this->jobKey($event->job);
            Cache::put('serik_job_start:' . $id, microtime(true), 1800);
        } catch (Throwable) {
        }
    }

    public function handleProcessed(JobProcessed $event): void
    {
        try {
            $job = $event->job;
            $queue = (string) ($job->getQueue() ?: 'default');
            $id = $this->jobKey($job);
            $start = Cache::pull('serik_job_start:' . $id);
            $durationMs = is_numeric($start) ? max(0, (microtime(true) - (float) $start) * 1000) : 0.0;
            SerikQueueMetrics::recordProcessed($queue, $durationMs);
        } catch (Throwable) {
        }
    }

    public function handleFailed(JobFailed $event): void
    {
        try {
            $queue = (string) ($event->job->getQueue() ?: 'default');
            Cache::forget('serik_job_start:' . $this->jobKey($event->job));
            SerikQueueMetrics::recordFailed($queue);
        } catch (Throwable) {
        }
    }

    public function handleException(JobExceptionOccurred $event): void
    {
        try {
            $max = $event->job->maxTries();
            if ($max === null || $event->job->attempts() < $max) {
                SerikQueueMetrics::recordRetry((string) ($event->job->getQueue() ?: 'default'));
            }
        } catch (Throwable) {
        }
    }

    protected function jobKey(object $job): string
    {
        try {
            if (method_exists($job, 'uuid') && $job->uuid()) {
                return (string) $job->uuid();
            }
            if (method_exists($job, 'getJobId') && $job->getJobId()) {
                return (string) $job->getJobId();
            }
        } catch (Throwable) {
        }

        return spl_object_hash($job);
    }
}
