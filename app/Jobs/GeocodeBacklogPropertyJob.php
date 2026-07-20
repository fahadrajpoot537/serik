<?php

namespace App\Jobs;

use App\Support\GeocodeState;
use App\Support\SerikQueue;
use Botble\RealEstate\Http\Controllers\API\PropertyController;
use Botble\RealEstate\Models\Property;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUniqueUntilProcessing;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Throwable;

/**
 * LOW lane: backlog geocode for one property missing coordinates.
 * Never shares a worker with the HIGH lane.
 */
class GeocodeBacklogPropertyJob implements ShouldQueue, ShouldBeUniqueUntilProcessing
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public int $tries = 4;

    /** @var list<int> */
    public array $backoff = [60, 180, 600, 1800];

    public int $timeout = 90;

    public int $uniqueFor = 600;

    public function __construct(public int $propertyId)
    {
        $this->onQueue(SerikQueue::low());
    }

    public function uniqueId(): string
    {
        return 'geocode-property-' . $this->propertyId;
    }

    public function handle(PropertyController $controller): void
    {
        @set_time_limit(0);

        $this->adaptiveThrottle();

        $lock = Cache::lock(GeocodeState::propertyLockKey($this->propertyId), 180);
        if (! $lock->get()) {
            $this->release(45);

            return;
        }

        try {
            $property = Property::query()->find($this->propertyId);
            if (! $property) {
                return;
            }

            $lat = (float) ($property->latitude ?? 0);
            if ($lat !== 0.0) {
                GeocodeState::markDone($this->propertyId);

                return;
            }

            if ($this->isPermanentlyFailed()) {
                GeocodeState::markFailed($this->propertyId, true);

                return;
            }

            GeocodeState::markProcessing($this->propertyId);

            $result = $controller->geocode([$this->propertyId], 1);
            $body = json_decode($result->getContent(), true) ?: [];

            if (! empty($body['locked']) || ! empty($body['skipped'])) {
                GeocodeState::markQueued($this->propertyId);
                $this->release(60);

                return;
            }

            $property->refresh();
            $lat = (float) ($property->latitude ?? 0);
            if ($lat === 0.0) {
                if ($this->isPermanentlyFailed()) {
                    GeocodeState::markFailed($this->propertyId, true);

                    return;
                }

                throw new \RuntimeException(
                    'Backlog geocode missed coords for property ' . $this->propertyId
                );
            }

            GeocodeState::markDone($this->propertyId);
        } finally {
            optional($lock)->release();
        }
    }

    public function failed(?Throwable $e): void
    {
        GeocodeState::markFailed($this->propertyId);
        Log::error('[GeocodeBacklogPropertyJob] failed permanently', [
            'property_id' => $this->propertyId,
            'error' => $e?->getMessage(),
        ]);
    }

    private function adaptiveThrottle(): void
    {
        $high = SerikQueue::high();
        $highDepth = (int) DB::table('jobs')->where('queue', $high)->count();
        $pauseAt = (int) config('serik.backlog.high_depth_pause', 5);
        $throttleMs = (int) config('serik.backlog.throttle_ms_when_busy', 500);

        if ($highDepth >= $pauseAt) {
            usleep(max(200, $throttleMs) * 1000 * min(5, $highDepth));
        } elseif ($highDepth > 0 && $throttleMs > 0) {
            usleep($throttleMs * 1000);
        }
    }

    private function isPermanentlyFailed(): bool
    {
        if (! Schema::hasTable('re_geocode_queue')) {
            return false;
        }

        return DB::table('re_geocode_queue')
            ->where('property_id', $this->propertyId)
            ->where('permanent_fail', 1)
            ->exists();
    }
}
