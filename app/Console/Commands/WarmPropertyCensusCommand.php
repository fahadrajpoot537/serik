<?php

namespace App\Console\Commands;

use App\Services\Census\CanadaCensusService;
use Botble\RealEstate\Models\Property;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;

class WarmPropertyCensusCommand extends Command
{
    protected $signature = 'census:warm {propertyId : Property id to cache census for}';

    protected $description = 'Build one property census profile outside the web request';

    public function handle(CanadaCensusService $census): int
    {
        @set_time_limit(120);
        @ini_set('max_execution_time', '120');

        $propertyId = (int) $this->argument('propertyId');
        if ($propertyId < 1) {
            return self::FAILURE;
        }

        try {
            $property = Property::query()->find($propertyId);
            if ($property) {
                $census->getPropertyCensusData($property);
            }
        } finally {
            Cache::forget('census:computing:' . $propertyId);
        }

        return self::SUCCESS;
    }
}
