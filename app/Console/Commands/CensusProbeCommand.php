<?php

namespace App\Console\Commands;

use App\Services\Census\CanadaCensusService;
use Illuminate\Console\Command;

class CensusProbeCommand extends Command
{
    protected $signature = 'census:probe
        {--address= : Canadian address to geocode + resolve DA}
        {--lat= : Latitude (skips geocode when set with --lng)}
        {--lng= : Longitude}
        {--dguid= : Fetch Census Profile metrics for a DGUID}
        {--property= : Property ID to run full pipeline}';

    protected $description = 'Probe Canada 2021 Census demographics pipeline (Geoapify → DA → StatsCan profile)';

    public function handle(CanadaCensusService $census): int
    {
        if ($propertyId = $this->option('property')) {
            $property = \Botble\RealEstate\Models\Property::query()->find((int) $propertyId);
            if (! $property) {
                $this->error('Property not found.');

                return self::FAILURE;
            }
            $data = $census->getPropertyCensusData($property);
            $this->line(json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));

            return self::SUCCESS;
        }

        $lat = $this->option('lat');
        $lng = $this->option('lng');
        $address = trim((string) $this->option('address'));

        if (($lat === null || $lng === null) && $address !== '') {
            $coords = $census->geocodeAddress($address);
            if (! $coords) {
                $this->error('Geocode failed (check GEOAPIFY_API_KEY).');

                return self::FAILURE;
            }
            $lat = $coords['lat'];
            $lng = $coords['lng'];
            $this->info("Geocoded: {$lat}, {$lng}");
        }

        if ($lat !== null && $lng !== null) {
            $geo = $census->findDisseminationArea((float) $lat, (float) $lng);
            if (! $geo) {
                $this->error('DA lookup failed.');

                return self::FAILURE;
            }
            $this->info('DAUID=' . $geo['dauid'] . ' DGUID=' . $geo['dguid'] . ' PRUID=' . $geo['pruid']);
            $this->option('dguid') || $this->input->setOption('dguid', $geo['dguid']);
        }

        $dguid = (string) ($this->option('dguid') ?: '');
        if ($dguid !== '') {
            $profile = $census->getCensusProfileCached($dguid);
            if (! $profile) {
                $this->error('Census profile fetch failed.');

                return self::FAILURE;
            }
            $metrics = $census->calculateMetrics($profile['values'] ?? []);
            foreach ($metrics as $m) {
                $this->line(str_pad($m['label'], 32) . ' ' . $m['display']);
            }
        }

        return self::SUCCESS;
    }
}
