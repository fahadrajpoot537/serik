<?php

namespace App\Jobs;

use App\Services\Census\CanadaCensusService;
use App\Support\SerikQueue;
use Botble\RealEstate\Models\Property;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUniqueUntilProcessing;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Pre-build census:property:{id}:v12 off the request path so the next page view SSR-paints metrics.
 */
class WarmPropertyCensusJob implements ShouldQueue, ShouldBeUniqueUntilProcessing
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public int $tries = 2;

    /** @var list<int> */
    public array $backoff = [15, 60];

    public int $timeout = 100;

    public int $uniqueFor = 600;

    public function __construct(public int $propertyId)
    {
        $this->onQueue(SerikQueue::cacheRefresh());
    }

    public function uniqueId(): string
    {
        return 'warm-property-census:' . $this->propertyId;
    }

    public function handle(CanadaCensusService $census): void
    {
        $property = Property::query()->find($this->propertyId);
        if (! $property) {
            return;
        }

        $census->getPropertyCensusData($property);
    }

    public function failed(?Throwable $e): void
    {
        Log::warning('census.warm_job_failed', [
            'property_id' => $this->propertyId,
            'message' => $e?->getMessage(),
        ]);
    }
}
