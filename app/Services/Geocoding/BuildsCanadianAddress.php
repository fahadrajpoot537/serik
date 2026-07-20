<?php

namespace App\Services\Geocoding;

use Botble\RealEstate\Models\Property;

trait BuildsCanadianAddress
{
    /**
     * @return array<string, string>
     */
    public function partsFromProperty(Property $property): array
    {
        $raw = trim((string) ($property->getRawOriginal('location') ?: $property->getRawOriginal('name') ?: ''));
        $zip = trim((string) ($property->zip_code ?? ''));

        $unit = '';
        $street = $raw;
        $city = '';
        $postal = $zip;

        if (preg_match('/([A-Za-z]\d[A-Za-z])\s?(\d[A-Za-z]\d)/', $raw, $pm)) {
            $postal = strtoupper($pm[1] . ' ' . $pm[2]);
        }

        $segments = array_values(array_filter(array_map('trim', explode(',', $raw))));
        if ($segments !== []) {
            $street = $segments[0];
            if (preg_match('/(?:#|unit|apt|suite)\s*([A-Za-z0-9\-]+)/i', $street, $um)) {
                $unit = strtoupper($um[1]);
            }
            if ($unit === '' && preg_match('/^(\d+[A-Za-z]?)\s*[-–]\s*(\d+)/', $street, $um2)) {
                $unit = strtoupper($um2[1]);
            }
            if (isset($segments[1]) && ! preg_match('/^[A-Za-z]\d[A-Za-z]/', $segments[1])) {
                $city = $segments[1];
            }
        }

        return [
            'unit' => $unit,
            'street' => $street,
            'city' => $city,
            'province' => 'Ontario',
            'postal_code' => $postal,
            'country' => 'Canada',
        ];
    }

    public function buildAddress(Property $property): string
    {
        $parts = $this->partsFromProperty($property);

        $unit = $parts['unit'];
        $street = $parts['street'];
        $line = $street;
        if ($unit !== '' && $street !== '' && stripos($street, $unit) === false) {
            $line = 'Unit ' . $unit . ', ' . $street;
        } elseif ($unit !== '' && $street === '') {
            $line = 'Unit ' . $unit;
        }

        return implode(', ', array_values(array_filter([
            $line,
            $parts['city'],
            $parts['province'],
            $parts['postal_code'],
            $parts['country'],
        ], static fn ($p) => $p !== '')));
    }
}
