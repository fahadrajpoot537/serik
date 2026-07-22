<?php

namespace Botble\RealEstate\Http\Controllers\API;

use Botble\Base\Facades\BaseHelper;
use Botble\Base\Http\Controllers\BaseController;
use Botble\RealEstate\Facades\RealEstateHelper;
use Botble\RealEstate\Http\Resources\ListPropertyResource;
use Botble\RealEstate\Http\Resources\PropertyResource;
use Botble\RealEstate\Models\Property;
use Botble\RealEstate\Models\PropertyVisit;
use Botble\Slug\Facades\SlugHelper;
use Illuminate\Http\Request;
use Botble\Media\Models\MediaFile;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use Botble\Media\Facades\RvMedia;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\DB;
use Symfony\Component\Mime\MimeTypes;
use Illuminate\Support\Arr;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Cache;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;
use Theme\homzen\Supports\TrebPropertyHelper;

use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Schema;
use Botble\RealEstate\Supports\PropertyFulltextSearch;
use App\Support\TrebImageStore;

class PropertyController extends BaseController
{
    /**
     * List properties
     *
     * @group Real Estate
     * @queryParam page                 Current page of the collection. Default: 1
     * @queryParam per_page             Maximum number of items to be returned in result set. Default: 10
     * @queryParam search               Limit results to those matching a string.
     * @queryParam type                 Filter by property type (sale, rent).
     * @queryParam city_id              Filter by city ID.
     * @queryParam state_id             Filter by state ID.
     * @queryParam country_id           Filter by country ID.
     * @queryParam category_id          Filter by category ID.
     * @queryParam project_id           Filter by project ID.
     * @queryParam min_price            Filter by minimum price.
     * @queryParam max_price            Filter by maximum price.
     * @queryParam min_square           Filter by minimum square footage.
     * @queryParam max_square           Filter by maximum square footage.
     * @queryParam number_bedroom       Filter by number of bedrooms.
     * @queryParam number_bathroom      Filter by number of bathrooms.
     * @queryParam number_floor         Filter by number of floors.
     * @queryParam features             Filter by feature IDs (comma-separated).
     * @queryParam facilities           Filter by facility IDs (comma-separated).
     * @queryParam is_featured          Filter by featured properties (1 or 0).
     * @queryParam order                Order sort attribute ascending or descending. Default: desc. One of: asc, desc
     * @queryParam order_by             Sort collection by object attribute. Default: created_at. One of: created_at, updated_at, name, price
     */
    public function index(Request $request)
    {
        $perPage = $request->integer('per_page', 10);
        $perPage = min($perPage, 100); // Limit to 100 items per page

        $properties = RealEstateHelper::getPropertiesFilter($perPage);

        return $this
            ->httpResponse()
            ->setData(ListPropertyResource::collection($properties))
            ->toApiResponse();
    }



    public function bookAppointment(Request $request)
    {

        $validator = Validator::make($request->all(), [

            'name' => 'required|string|max:255',
            'email' => 'required|email',
            'phone' => 'required',
            'date' => 'required',
            'time' => 'required',
            'subject' => 'required'

        ]);

        if ($validator->fails()) {

            return response()->json([
                'status' => false,
                'message' => $validator->errors()
            ], 422);
        }

        $remarks = "Appointment Date: " . $request->date . " Time: " . $request->time;

        DB::table('contacts')->insert([
            'name' => $request->name,
            'email' => $request->email,
            'phone' => $request->phone,
            'address' => $request->address,
            'subject' => $request->subject,
            'content' => $remarks,
            'status' => 0,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return response()->json([
            'status' => true,
            'message' => 'Appointment booked successfully'
        ]);

    }


    /**
     * Search properties
     *
     * @bodyParam q string required The search keyword.
     *
     * @group Real Estate
     */
    public function getSearch(Request $request)
    {
        $query = BaseHelper::stringify($request->input('q'));

        $request->merge(['keyword' => $query]);

        $properties = RealEstateHelper::getPropertiesFilter();

        $data = [
            'items' => ListPropertyResource::collection($properties),
            'query' => $query,
            'count' => $properties->count(),
        ];

        if ($data['count'] > 0) {
            return $this
                ->httpResponse()
                ->setData($data);
        }

        return $this
            ->httpResponse()
            ->setError()
            ->setMessage(trans('core/base::layouts.no_search_result'));
    }


    public function getFilters(Request $request)
    {
        $perPage = $request->integer('per_page', 10);
        $perPage = min($perPage, 100); // Limit to 100 items per page

        $properties = RealEstateHelper::getPropertiesFilter($perPage, []);

        return $this
            ->httpResponse()
            ->setData(ListPropertyResource::collection($properties))
            ->toApiResponse();
    }


    public function addproperties()
    {
        $skip = 0;//cache()->get('amp_skip', 1000); and PropertySubType eq 'Condo Apartment'
        // AMPRE: $expand=Media requires $top <= 100
        $top = 100;

        $filter = rawurlencode("MlsStatus eq 'New' and contains(City,'Ottawa') and ListingContractDate ge 2026-05-15 and  PropertySubType ne 'Industrial' and PropertySubType ne 'Commercial Retail'");

        // 
        $url = "https://query.ampre.ca/odata/Property?"
            . "\$filter={$filter}"
            . "&\$top={$top}"
            . "&\$skip={$skip}"
            . "&\$select=ListingKey,UnparsedAddress,PropertySubType,PublicRemarks,PrivateRemarks,"
            . "BedroomsTotal,BathroomsTotalInteger,KitchensTotal,LivingAreaRange,"
            . "StandardStatus,ExpirationDate,ListPrice,PostalCode,"
            . "OriginalEntryTimestamp,ModificationTimestamp,TransactionType,MlsStatus,ListOfficeName,BedroomsBelowGrade,ListingContractDate,Basement,ParkingSpaces,ClosePrice"
            . "&\$expand=Media";
        //  dd($url) ;



        $response = $this->ampCurl($url);
        //dd($response);


        $payload = $response;




        if (!isset($payload['value'])) {
            return response()->json([
                'error' => 'Invalid AMP response'
            ], 500);
        }

        foreach ($payload['value'] as $item) {

            if (empty($item['ListingKey'])) {
                continue;
            }

            $latitude = 0;
            $longitude = 0;

            $mediaUrl = !empty($item['Media']) ? ($item['Media'][0]['MediaURL'] ?? null) : null;
            $listingkey = $item['ListingKey'];

            // dd($mediaUrl);

            $property = Property::firstOrCreate(
                ['external_id' => $item['ListingKey']],
                [
                    'unique_id' => $this->generateUniqueId(),

                    'author_id' => 1,
                    'author_type' => 'Botble\ACL\Models\User',

                    'name' => $item['UnparsedAddress'] ?? '',
                    'PropertySubType' => $item['PropertySubType'] ?? 'sell',
                    'description' => $item['PublicRemarks'] ?? '',
                    'content' => ($item['PublicRemarks'] ?? '') . '<br>' . ($item['PrivateRemarks'] ?? ''),
                    'location' => $item['UnparsedAddress'] ?? '',

                    'number_bedroom' => $this->extractMainBedrooms($item),
                    'number_bathroom' => (int) ($item['BathroomsTotalInteger'] ?? 0),
                    'number_floor' => $this->extractNumberFloor($item),
                    'BedroomsBelowGrade' => $this->extractBelowGradeBedrooms($item),
                    'broker' => $item['ListOfficeName'] ?? null,

                    'square' => is_array($item['LivingAreaRange'] ?? null)
                        ? null
                        : $this->normalizeSquare($item['LivingAreaRange'] ?? null),
                    'price' => (float) ($item['ListPrice'] ?? 0),
                    'currency_id' => 1,

                    'is_featured' => 0,
                    'featured_priority' => 0,

                    'status' => ($item['StandardStatus'] ?? '') === 'Active' ? 'selling' : 'draft',
                    'moderation_status' => 'approved',

                    'expire_date' => $item['ExpirationDate'] ?? now()->addYear()->toDateTimeString(),
                    'auto_renew' => 1,
                    'never_expired' => 1,

                    'TransactionType' => $item['TransactionType'] ?? null,
                    'MlsStatus' => $item['MlsStatus'] ?? null,
                    'latitude' => $latitude ? (float) $latitude : 0,
                    'longitude' => $longitude ? (float) $longitude : 0,
                    'image_val' => $mediaUrl ?? null,


                    'zip_code' => $item['PostalCode'] ?? null,
                    'views' => 0,
                    'ParkingSpaces' => $item['ParkingSpaces'] ?? '0',
                    'Basement' => isset($item['Basement'])
                        ? (is_array($item['Basement']) ? implode(', ', $item['Basement']) : $item['Basement'])
                        : '0',
                    'ClosePrice' => isset($item['ClosePrice'])
                        ? (is_array($item['ClosePrice']) ? implode(', ', $item['ClosePrice']) : $item['ClosePrice'])
                        : '0',

                    'created_at' => Carbon::parse($item['ListingContractDate'] ?? now()),
                    'updated_at' => Carbon::parse($item['ModificationTimestamp'] ?? now()),

                    'private_notes' => $item['PrivateRemarks'] ?? '',
                ]
            );

            $baseSlug = Str::slug($item['UnparsedAddress'] ?? 'property');
            $slug = $baseSlug . '-' . strtolower($item['ListingKey']);


            if ($property->wasRecentlyCreated) {
                SlugHelper::createSlug($property, $slug);
            }

            if ($mediaUrl && ($property->wasRecentlyCreated || $this->trebImageStore()->isRemoteUrl($property->image_val))) {
                if ($this->assignTrebCoverImage($property, $listingkey, $mediaUrl)) {
                    $property->saveQuietly();
                }
            }

            //$this->importPropertyImages($item['ListingKey']);



        }
        $newSkip = $skip + $top;
        cache()->put('amp_skip', $newSkip);
        return response()->json([
            'status' => 'success',
            'synced' => count($payload['value'])
        ]);

    }














    public function addpropertiesall()
    {
        //  dd('started');
        \Log::info('AMP all cron started', [
            'time' => now()
        ]);

        try {

            $skip = 0;//cache()->get('amp_skip', 1000); and PropertySubType eq 'Condo Apartment'
            // AMPRE: $expand=Media requires $top <= 100
            $top = 100;

            $filter = rawurlencode("PropertySubType ne 'Industrial' and PropertySubType ne 'Commercial Retail'");

            // 
            $url = "https://query.ampre.ca/odata/Property?"
                . "\$filter={$filter}"
                . "&\$top={$top}"
                . "&\$skip={$skip}"
                . "&\$select=ListingKey,UnparsedAddress,PropertySubType,PublicRemarks,PrivateRemarks,"
                . "BedroomsTotal,BathroomsTotalInteger,KitchensTotal,LivingAreaRange,"
                . "StandardStatus,ExpirationDate,ListPrice,PostalCode,"
                . "OriginalEntryTimestamp,ModificationTimestamp,TransactionType,MlsStatus,ListOfficeName,BedroomsBelowGrade,ListingContractDate,Basement,ParkingSpaces,ClosePrice"
                . "&\$expand=Media";
            //  dd($url) ;



            $response = $this->ampCurl($url);
            //dd($response);


            $payload = $response;




            if (!isset($payload['value'])) {
                return response()->json([
                    'error' => 'Invalid AMP response'
                ], 500);
            }

            foreach ($payload['value'] as $item) {

                if (empty($item['ListingKey'])) {
                    continue;
                }

                $latitude = 0;
                $longitude = 0;

                $mediaUrl = !empty($item['Media']) ? ($item['Media'][0]['MediaURL'] ?? null) : null;
                $listingkey = $item['ListingKey'];

                // dd($mediaUrl);

                $property = Property::firstOrCreate(
                    ['external_id' => $item['ListingKey']],
                    [
                        'unique_id' => $this->generateUniqueId(),

                        'author_id' => 1,
                        'author_type' => 'Botble\ACL\Models\User',

                        'name' => $item['UnparsedAddress'] ?? '',
                        'PropertySubType' => $item['PropertySubType'] ?? 'sell',
                        'description' => $item['PublicRemarks'] ?? '',
                        'content' => ($item['PublicRemarks'] ?? '') . '<br>' . ($item['PrivateRemarks'] ?? ''),
                        'location' => $item['UnparsedAddress'] ?? '',

                        'number_bedroom' => $this->extractMainBedrooms($item),
                        'number_bathroom' => (int) ($item['BathroomsTotalInteger'] ?? 0),
                        'number_floor' => $this->extractNumberFloor($item),
                        'BedroomsBelowGrade' => $this->extractBelowGradeBedrooms($item),
                        'broker' => $item['ListOfficeName'] ?? null,

                        'square' => is_array($item['LivingAreaRange'] ?? null)
                            ? null
                            : $this->normalizeSquare($item['LivingAreaRange'] ?? null),
                        'price' => (float) ($item['ListPrice'] ?? 0),
                        'currency_id' => 1,

                        'is_featured' => 0,
                        'featured_priority' => 0,

                        'status' => ($item['StandardStatus'] ?? '') === 'Active' ? 'selling' : 'draft',
                        'moderation_status' => 'approved',

                        'expire_date' => $item['ExpirationDate'] ?? now()->addYear()->toDateTimeString(),
                        'auto_renew' => 1,
                        'never_expired' => 1,

                        'TransactionType' => $item['TransactionType'] ?? null,
                        'MlsStatus' => $item['MlsStatus'] ?? null,
                        'latitude' => $latitude ? (float) $latitude : 0,
                        'longitude' => $longitude ? (float) $longitude : 0,
                        'image_val' => $mediaUrl ?? null,


                        'zip_code' => $item['PostalCode'] ?? null,
                        'views' => 0,
                        'ParkingSpaces' => $item['ParkingSpaces'] ?? '0',
                        'Basement' => isset($item['Basement'])
                            ? (is_array($item['Basement']) ? implode(', ', $item['Basement']) : $item['Basement'])
                            : '0',
                        'ClosePrice' => isset($item['ClosePrice'])
                            ? (is_array($item['ClosePrice']) ? implode(', ', $item['ClosePrice']) : $item['ClosePrice'])
                            : '0',

                        'created_at' => Carbon::parse($item['ListingContractDate'] ?? now()),
                        'updated_at' => Carbon::parse($item['ModificationTimestamp'] ?? now()),

                        'private_notes' => $item['PrivateRemarks'] ?? '',
                    ]
                );

                $baseSlug = Str::slug($item['UnparsedAddress'] ?? 'property');
                $slug = $baseSlug . '-' . strtolower($item['ListingKey']);


                if ($property->wasRecentlyCreated) {
                    SlugHelper::createSlug($property, $slug);
                }

                if ($mediaUrl && ($property->wasRecentlyCreated || $this->trebImageStore()->isRemoteUrl($property->image_val))) {
                    if ($this->assignTrebCoverImage($property, $item['ListingKey'], $mediaUrl)) {
                        $property->saveQuietly();
                    }
                }

                //$this->importPropertyImages($item['ListingKey']);



            }

            return response()->json([
                'status' => 'success',
                'synced' => count($payload['value'])
            ]);


        } catch (\Exception $e) {

            Log::error('AMP cron failed', [
                'message' => $e->getMessage()
            ]);

            return response()->json([
                'error' => $e->getMessage()
            ]);
        }

    }





















    public function addpropertiescron()
    {
        $top = 2000;

        // use cached pagination
        $skip = cache()->get('amp_skip', 0);

        $cities = [
            'Brampton',
            'Mississauga',
            'Vaughan',
            'Milton',
            'Oakville',
            'NiagaraFalls',
            'Toronto',
            'Kitchener',
            'Waterloo',
            'Cambridge',
            'Hamilton',
            'Ottawa',
            'Markham',
            'Windsor',
            'RichmondHill',
            'Oshawa',
            'Barrie',
            'Guelph',
            'Kingston',
            'Brantford',
        ];

        $cityIndex = cache()->get('amp_city_index', 0);
        $city = $cities[$cityIndex] ?? $cities[0];

        // prevent duplicate cron
        if (!cache()->add('amp_lock', true, 600)) {
            return response()->json([
                'status' => 'already running',
                'city' => $city,
            ]);
        }

        try {

            \Log::info("AMP START CITY: {$city}");

            $cutoffDate = now()->subDays(60)->format('Y-m-d');
            $cutoffDateTime = $cutoffDate . 'T00:00:00Z';

            $baseConditions = [
                "PropertySubType ne 'Industrial'",
                "PropertySubType ne 'Commercial Retail'",
            ];

            $cityFilters = [
                implode(' and ', array_merge([
                    "contains(City,'{$city}')",
                    "ModificationTimestamp ge {$cutoffDateTime}",
                ], $baseConditions)),
                implode(' and ', array_merge([
                    "contains(City,'{$city}')",
                    "ListingContractDate ge {$cutoffDate}",
                ], $baseConditions)),
                implode(' and ', array_merge([
                    "StandardStatus eq 'Active'",
                    "ModificationTimestamp ge {$cutoffDateTime}",
                ], $baseConditions)),
                implode(' and ', array_merge([
                    "ModificationTimestamp ge {$cutoffDateTime}",
                ], $baseConditions)),
            ];

            $payload = null;
            $usedFilter = '';

            foreach ($cityFilters as $filterBody) {
                $url =
                    'https://query.ampre.ca/odata/Property?'
                    . '$filter=' . rawurlencode($filterBody)
                    . '&$top=' . $top
                    . '&$skip=' . $skip
                    . '&$select='
                    . 'ListingKey,UnparsedAddress,PropertySubType,PublicRemarks,PrivateRemarks,'
                    . 'BedroomsTotal,BedroomsAboveGrade,BathroomsTotalInteger,KitchensTotal,LivingAreaRange,'
                    . 'StandardStatus,ExpirationDate,ListPrice,PostalCode,'
                    . 'OriginalEntryTimestamp,ModificationTimestamp,PriceChangeTimestamp,'
                    . 'TransactionType,MlsStatus,ListOfficeName,BedroomsBelowGrade,'
                    . 'ListingContractDate,CloseDate,PurchaseContractDate,Basement,ParkingSpaces,CoveredSpaces,ClosePrice,ArchitecturalStyle';

                $response = $this->ampCurl($url, 45);
                $payload = is_array($response) ? $response : null;

                if (is_array($payload) && isset($payload['value'])) {
                    $usedFilter = $filterBody;
                    if ($payload['value'] !== []) {
                        break;
                    }
                }
            }

            if (! is_array($payload) || ! isset($payload['value'])) {
                throw new \Exception('Invalid AMP response — API timeout or token error');
            }

            $created = 0;
            $updated = 0;
            $newPropertyIds = [];

            foreach ($payload['value'] as $item) {
                $result = $this->saveAmpPropertyItem($item, $newPropertyIds);

                if ($result === 'created') {
                    $created++;
                } elseif ($result === 'updated') {
                    $updated++;
                }
            }

            // Geocode freshly imported listings right away so they appear on the
            // map without waiting for the separate geocode cron run. Nominatim
            // is rate-limited (~1 req/s), so cap the inline work and let the
            // scheduled `serik:geocode` clear any remainder.
            if ($newPropertyIds !== []) {
                $inline = array_slice($newPropertyIds, 0, 40);
                try {
                    $this->geocodePropertyBatch(count($inline), $inline);
                } catch (\Throwable $e) {
                    \Log::warning('[geocode] Post-import geocode failed: ' . $e->getMessage());
                }
            }

            if (count($payload['value']) < $top) {
                cache()->put('amp_skip', 0);
            } else {
                cache()->put('amp_skip', $skip + $top);
            }

            $this->syncAmpListingDatesForMapFilter(60, 8);
            $this->syncRecentSoldListingsFromAmp(60, 6);

            return response()->json([
                'status' => 'success',
                'city' => $city,
                'created' => $created,
                'updated' => $updated,
                'total' => count($payload['value']),
                'filter' => $usedFilter,
                'next_city' =>
                    $cities[
                        ($cityIndex + 1)
                        % count($cities)
                    ]
            ]);

        } catch (\Throwable $e) {

            \Log::error(
                'AMP ERROR: ' . $e->getMessage()
            );

            return response()->json([
                'status' => 'failed',
                'city' => $city,
                'error' => $e->getMessage()
            ], 500);

        } finally {

            // ALWAYS ROTATE CITY
            cache()->put(
                'amp_city_index',
                ($cityIndex + 1)
                % count($cities)
            );

            // ALWAYS RELEASE LOCK
            cache()->forget('amp_lock');
        }
    }

    /**
     * Create or update a single Property row from one AMP OData item.
     * Returns 'created' | 'updated' | 'skipped'. New ids are pushed into
     * $newPropertyIds so callers can geocode them.
     *
     * This is the shared upsert used by both the per-city cron
     * (addpropertiescron) and the hourly site-wide recent sweep
     * (importRecentModifiedAmpListings).
     */
    private function saveAmpPropertyItem(array $item, array &$newPropertyIds): string
    {
        if (empty($item['ListingKey'])) {
            return 'skipped';
        }

        $listingkey = $item['ListingKey'];

        $mediaUrl = !empty($item['Media']) ? ($item['Media'][0]['MediaURL'] ?? null) : null;
        $contractDate = $this->parseAmpDateValue($item['ListingContractDate'] ?? $item['OriginalEntryTimestamp'] ?? null);
        $modifiedDate = $this->parseAmpDateValue($item['ModificationTimestamp'] ?? $item['PriceChangeTimestamp'] ?? null);
        $purchaseDate = $this->parseAmpDateValue($item['PurchaseContractDate'] ?? null);
        $closeDate = $this->parseAmpDateValue($item['CloseDate'] ?? null);
        $soldDate = $purchaseDate ?? $closeDate ?? $modifiedDate;

        $property = Property::firstOrNew(['external_id' => $listingkey]);
        $isNew = ! $property->exists;

        if ($isNew) {
            $property->unique_id = $this->generateUniqueId();
            $property->author_id = 1;
            $property->author_type = 'Botble\ACL\Models\User';
        }

        $property->fill([
                'name' => $item['UnparsedAddress'],

                'PropertySubType' =>
                    $item['PropertySubType']
                    ?? 'sell',

                'description' =>
                    $item['PublicRemarks']
                    ?? '',

                'content' =>
                    ($item['PublicRemarks'] ?? '')
                    . '<br>'
                    . ($item['PrivateRemarks'] ?? ''),

                'location' =>
                    $item['UnparsedAddress'],

                'number_bedroom' =>
                    $this->extractMainBedrooms($item),

                'number_bathroom' =>
                    (int) ($item['BathroomsTotalInteger'] ?? 0),

                'number_floor' =>
                    $this->extractNumberFloor($item),

                'BedroomsBelowGrade' =>
                    $this->extractBelowGradeBedrooms($item),

                'broker' =>
                    $item['ListOfficeName'] ?? null,

                'square' =>
                    is_array($item['LivingAreaRange'] ?? null)
                    ? null
                    : $this->normalizeSquare(
                        $item['LivingAreaRange']
                    ),

                'price' =>
                    (float) ($item['ListPrice'] ?? 0),

                'currency_id' => 1,

                'is_featured' => 0,
                'featured_priority' => 0,

                'status' =>
                    ($item['StandardStatus'] ?? '') === 'Active'
                    ? 'selling'
                    : 'draft',

                'moderation_status' => 'approved',

                'expire_date' =>
                    $item['ExpirationDate'] ?? now()->addYear()->toDateTimeString(),

                'auto_renew' => 1,
                'never_expired' => 1,

                'TransactionType' =>
                    trim((string) ($item['TransactionType'] ?? '')) ?: 'For Sale',

                'MlsStatus' =>
                    $item['MlsStatus']
                    ?? 'Active',

                'latitude' => $property->latitude ?: 0,
                'longitude' => $property->longitude ?: 0,

                'image_val' => $mediaUrl ?: $property->image_val,

                'zip_code' =>
                    $item['PostalCode'] ?? null,

                'views' => $property->views ?? 0,

                'ParkingSpaces' =>
                    (int) ($item['ParkingSpaces'] ?? 0),

                'CoveredSpaces' =>
                    (int) ($item['CoveredSpaces'] ?? 0),

                'Basement' =>
                    is_array($item['Basement'] ?? null)
                    ? implode(', ', $item['Basement'])
                    : ($item['Basement'] ?? '0'),

                'ClosePrice' =>
                    $item['ClosePrice'] ?? 0,

                'listing_contract_date' => $contractDate,
                'listing_modified_at' => $modifiedDate,
                'close_date' => $soldDate ?? $closeDate,
                'purchase_contract_date' => $soldDate ?? $purchaseDate,

                'created_at' => $contractDate ?? $property->created_at ?? now(),
                'updated_at' => $modifiedDate ?? now(),

                'private_notes' =>
                    $item['PrivateRemarks']
                    ?? '',
        ]);

        $property->save();

        if ($mediaUrl && ($isNew || $this->trebImageStore()->isRemoteUrl($property->image_val))) {
            if ($this->assignTrebCoverImage($property, $listingkey, $mediaUrl)) {
                $property->saveQuietly();
            }
        }

        if ($isNew) {
            $slug =
                Str::slug(
                    $item['UnparsedAddress']
                )
                . '-'
                . strtolower($listingkey);

            SlugHelper::createSlug(
                $property,
                $slug
            );

            $newPropertyIds[] = $property->id;

            return 'created';
        }

        return 'updated';
    }

    /**
     * Site-wide "freshest first" import of listings modified in the last
     * $days days. The per-city cron (addpropertiescron) only touches one city
     * per run, so a brand-new listing can take days to surface. Running this
     * hourly guarantees anything newly listed / updated in AMP shows up within
     * ~1 hour regardless of city.
     *
     * Reuses the exact same upsert as the per-city cron, so no data drift.
     *
     * IMPORTANT: Do NOT add $expand=Media here. AMPRE rejects $top>100 with
     * Media expand (HTTP 400), which zeroes SyncLiveJob imports. Cover images
     * are filled later via Media endpoint / getMediaUrl when needed.
     * Page size may be >100 because this query has no $expand.
     */
    public function importRecentModifiedAmpListings(
        int $days = 3,
        int $maxPages = 6,
        int $maxSeconds = 150,
        int $maxNew = 0,
        int $pageSize = 2000
    ) {
        $days = max(1, min(30, $days));
        $maxPages = max(1, min(20, $maxPages));
        $maxSeconds = max(20, min(1800, $maxSeconds));
        $maxNew = max(0, min(500, $maxNew));
        $pageSize = max(25, min(2000, $pageSize));

        // CLI/cron runs must not be killed by php.ini max_execution_time; we
        // enforce our own time budget below instead.
        @set_time_limit(0);

        $deadline = microtime(true) + $maxSeconds;

        $cutoffDateTime = now()->subDays($days)->format('Y-m-d\TH:i:s\Z');

        $filterParts = [
            "ModificationTimestamp ge {$cutoffDateTime}",
        ];
        TrebPropertyHelper::appendOntarioResidentialAmpConditions($filterParts);
        $filterBody = implode(' and ', $filterParts);

        $select =
            'ListingKey,UnparsedAddress,PropertySubType,PublicRemarks,PrivateRemarks,'
            . 'BedroomsTotal,BedroomsAboveGrade,BathroomsTotalInteger,KitchensTotal,LivingAreaRange,'
            . 'StandardStatus,ExpirationDate,ListPrice,PostalCode,'
            . 'OriginalEntryTimestamp,ModificationTimestamp,PriceChangeTimestamp,'
            . 'TransactionType,MlsStatus,ListOfficeName,BedroomsBelowGrade,'
            . 'ListingContractDate,CloseDate,PurchaseContractDate,Basement,ParkingSpaces,CoveredSpaces,ClosePrice,ArchitecturalStyle';

        $top = $pageSize;
        $skip = 0;
        $created = 0;
        $updated = 0;
        $unchanged = 0;
        $pages = 0;
        $stoppedEarly = false;
        $newPropertyIds = [];

        if (! cache()->add('amp_recent_lock', true, 200)) {
            return response()->json(['status' => 'already running', 'new_id_list' => []]);
        }

        try {
            for ($page = 0; $page < $maxPages; $page++) {
                if (microtime(true) > $deadline) {
                    $stoppedEarly = true;
                    break;
                }

                if ($maxNew > 0 && count($newPropertyIds) >= $maxNew) {
                    $stoppedEarly = true;
                    break;
                }

                // No $expand=Media — keeps $top valid above 100 and avoids AMP 400.
                $url =
                    'https://query.ampre.ca/odata/Property?'
                    . '$filter=' . rawurlencode($filterBody)
                    . '&$orderby=' . rawurlencode('ModificationTimestamp desc')
                    . '&$top=' . $top
                    . '&$skip=' . $skip
                    . '&$select=' . $select;

                $response = $this->ampCurl($url, 30);

                if (! is_array($response) || ! isset($response['value'])) {
                    break;
                }

                $rows = $response['value'];
                $pages++;

                // Bulk pre-check: skip rows already up to date so repeated hourly
                // runs stay fast and reach deeper into the window each time.
                $existing = $this->fetchExistingModifiedMap($rows);

                foreach ($rows as $item) {
                    if (microtime(true) > $deadline) {
                        $stoppedEarly = true;
                        break 2;
                    }

                    if ($maxNew > 0 && count($newPropertyIds) >= $maxNew) {
                        $stoppedEarly = true;
                        break 2;
                    }

                    if ($this->ampItemUnchanged($item, $existing)) {
                        $unchanged++;
                        continue;
                    }

                    $result = $this->saveAmpPropertyItem($item, $newPropertyIds);

                    if ($result === 'created') {
                        $created++;
                    } elseif ($result === 'updated') {
                        $updated++;
                    }
                }

                if (count($rows) < $top) {
                    break;
                }

                $skip += $top;
            }

            // Light cover-image fill for brand-new rows only (separate Media API —
            // never via Property $expand). Bounded so SyncLiveJob stays fast.
            if ($newPropertyIds !== [] && microtime(true) < $deadline) {
                $hydrateIds = array_slice($newPropertyIds, 0, min(40, count($newPropertyIds)));
                $props = Property::query()
                    ->select(['id', 'external_id', 'image_val'])
                    ->whereIn('id', $hydrateIds)
                    ->where(function ($q) {
                        $q->whereNull('image_val')->orWhere('image_val', '');
                    })
                    ->get();

                foreach ($props as $property) {
                    if (microtime(true) > $deadline) {
                        break;
                    }
                    $key = (string) $property->external_id;
                    if ($key === '') {
                        continue;
                    }
                    try {
                        if ($this->assignTrebCoverImage($property, $key)) {
                            $property->saveQuietly();
                        }
                } catch (\Throwable $e) {
                        // Non-fatal — listing is already imported.
                    }
                }
            }

            // Geocoding + history are owned by serik:sync-live pipeline
            // (GeocodePropertyJob → SyncPropertyHistoryJob). Do not inline here —
            // a long Nominatim walk was killing the 5-min / 300s cron.
            return response()->json([
                'status' => 'success',
                'days' => $days,
                'pages' => $pages,
                'created' => $created,
                'updated' => $updated,
                'unchanged' => $unchanged,
                'new_ids' => count($newPropertyIds),
                'new_id_list' => array_values(array_map('intval', $newPropertyIds)),
                'stopped_early' => $stoppedEarly,
            ]);
        } catch (\Throwable $e) {
            \Log::error('AMP RECENT IMPORT ERROR: ' . $e->getMessage());

            return response()->json([
                'status' => 'failed',
                'error' => $e->getMessage(),
                'created' => $created,
                'updated' => $updated,
                'new_id_list' => array_values(array_map('intval', $newPropertyIds)),
            ], 500);
        } finally {
            cache()->forget('amp_recent_lock');
        }
    }

    /**
     * Map of external_id => stored listing_modified_at (string) for the given
     * AMP rows, fetched in a single query.
     */
    private function fetchExistingModifiedMap(array $rows): array
    {
        $keys = [];
        foreach ($rows as $item) {
            if (! empty($item['ListingKey'])) {
                $keys[] = $item['ListingKey'];
            }
        }

        if ($keys === []) {
            return [];
        }

        return Property::query()
            ->whereIn('external_id', $keys)
            ->pluck('listing_modified_at', 'external_id')
            ->map(fn ($v) => (string) $v)
            ->all();
    }

    /**
     * True when the listing already exists locally and its stored
     * listing_modified_at is at or after the AMP ModificationTimestamp
     * (i.e. nothing new to write). New listings always return false.
     */
    private function ampItemUnchanged(array $item, array $existing): bool
    {
        $key = $item['ListingKey'] ?? null;

        if ($key === null || ! array_key_exists($key, $existing)) {
            return false;
        }

        $stored = trim((string) $existing[$key]);

        if ($stored === '' || str_starts_with($stored, '0000')) {
            return false;
        }

        $ampModified = $this->parseAmpDateValue($item['ModificationTimestamp'] ?? $item['PriceChangeTimestamp'] ?? null);

        if (empty($ampModified)) {
            return false;
        }

        try {
            return Carbon::parse($stored)->greaterThanOrEqualTo(Carbon::parse($ampModified));
        } catch (\Throwable $e) {
            return false;
        }
    }














    public function testapi()
    {

        $params = [
            '$filter' => "ResourceRecordKey eq 'W12824040'"
        ];

        $url = "https://query.ampre.ca/odata/HistoryTransactional?" . http_build_query($params);

        $url = "https://query.ampre.ca/odata/HistoryTransactional?" . http_build_query($params);

        $ch = curl_init($url);

        $token = TrebPropertyHelper::ampTokens()[0] ?? null;

        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER => [
                'Authorization: Bearer ' . $token,
                'Accept: application/json',
                'OData-Version: 4.0',
                'OData-MaxVersion: 4.0',
            ],
        ]);

        $response = curl_exec($ch);

        if ($response === false) {
            return response()->json([
                'error' => curl_error($ch)
            ], 500);
        }

        curl_close($ch);

        $payload = json_decode($response, true);

        if (!isset($payload['value'])) {
            return response()->json([
                'error' => 'Invalid AMP response',
                'raw' => $response
            ], 500);
        }

        return $payload['value'];
    }





    /*

    public function syncMissingDescriptions()
    { 
       // $listingKeys = Property::whereNull('broker')
      $listingKeys = Property::whereNotNull('external_id')
        ->whereNull('Basement')   // correct Laravel syntax for NULL
        ->pluck('external_id');
     Log::info('[syncMissingDescriptions] started');
        foreach ($listingKeys as $listingKey) {
            $this->addPropertyData($listingKey);
            echo $listingKey;
            sleep(1);
        }

        return response()->json([
            'status' => 'completed',
            'total' => $listingKeys->count()
        ]);
    }


    public function addPropertyData($listingKey)
    {
        if (empty($listingKey)) {
            return response()->json([
                'error' => 'ListingKey is required'
            ], 400);
        }


     /*  $url = "https://query.ampre.ca/odata/Property?"
        . "\$filter=ListingKey%20eq%20%27$listingKey%27"
        . "&\$select=PropertySubType,BedroomsTotal,BathroomsTotalInteger,KitchensTotal,BedroomsBelowGrade,LivingAreaRange,ListPrice,TransactionType,MlsStatus,ListOfficeName"
        . "&\$top=1";



         $url = "https://query.ampre.ca/odata/Property?"
        . "\$filter=ListingKey%20eq%20%27$listingKey%27"
        . "&\$select=Basement,ParkingSpaces"
        . "&\$top=1";


        $ch = curl_init($url);

        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER => [
                'Authorization: Bearer eyJhbGciOiJIUzI1NiJ9.eyJzdWIiOiJ2ZW5kb3IvdHJyZWIvMTAzMjQiLCJhdWQiOiJBbXBVc2Vyc1ByZCIsInJvbGVzIjpbIkFtcFZlbmRvciJdLCJpc3MiOiJwcm9kLmFtcHJlLmNhIiwiZXhwIjoyNTM0MDIzMDA3OTksImlhdCI6MTc2ODg2NzI4Miwic3ViamVjdFR5cGUiOiJ2ZW5kb3IiLCJzdWJqZWN0S2V5IjoiMTAzMjQiLCJqdGkiOiJjMDRkMzYwMDhlNzc0Zjc4IiwiY3VzdG9tZXJOYW1lIjoidHJyZWIifQ.IBqRgRDkr9eqSzkOqrYQN1m0V_difH0FqHRPM11vL9Y',
                    'Accept: application/json',
                'OData-Version: 4.0',
                'OData-MaxVersion: 4.0',
            ],
        ]);

        $response = curl_exec($ch);

        if ($response === false) {
            return response()->json([
                'error' => curl_error($ch)
            ], 500);
        }

        curl_close($ch);

        $payload = json_decode($response, true);

        if (!isset($payload['value']) || empty($payload['value'])) {
            return response()->json([
                'error' => 'Property not found'
            ], 404);
        }

        $item = $payload['value'][0];

        if (empty($item['ListingKey'])) {
            return response()->json([
                'error' => 'Invalid ListingKey'
            ], 400);
        }


          /*  
               $mediaUrl = null;

                try {

                    $mediaApi = "https://query.ampre.ca/odata/Media?"
                        . "%24filter=ResourceRecordKey%20eq%20%27{$listingKey}%27"
                        . "&%24top=1"
                        . "&%24select=MediaURL";

                    $mediaResponse = $this->ampCurl($mediaApi);
                    $mediaData = json_decode($mediaResponse, true);

                    $mediaUrl = $mediaData['value'][0]['MediaURL'] ?? null;

                } catch (\Throwable $e) {
                    $mediaUrl = null;
                }
    */

    // Create or Update Property
    /*  $property = Property::updateOrCreate(
         ['external_id' => $item['ListingKey']],
         [


             'PropertySubType' => $item['PropertySubType'] ?? 'sell',


             'number_bedroom' => $this->extractMainBedrooms($item),
             'number_bathroom' => (int) ($item['BathroomsTotalInteger'] ?? 0),
             'number_floor' => $this->extractNumberFloor($item),
             'BedroomsBelowGrade' => $this->extractBelowGradeBedrooms($item),

             'square' => $item['LivingAreaRange'] ?? null,
             'price' => (float) ($item['ListPrice'] ?? 0),


             'broker' => $item['ListOfficeName'] ?? null,
             'TransactionType' => $item['TransactionType'] ?? null,
             'MlsStatus' => $item['MlsStatus'] ?? null,
             'image_val' => $mediaUrl ?? null,
         ]
     );  


  $property = Property::updateOrCreate(
         ['external_id' => $item['ListingKey']],
         [


             'ParkingSpaces' => $item['ParkingSpaces'] ?? '0',
             'Basement' => $item['Basement'] ?? '0',


         ]
     );

     // OPTIONAL IMAGE IMPORT


     return response()->json([
         'status' => 'success',
         'listingKey' => $listingKey,
         'property_id' => $property->id
     ]);
 }


 public function syncMissingDescriptions()
 {
     $listingKeys = Property::whereNotNull('external_id')
         ->whereNull('Basement')
         ->pluck('external_id');

     Log::info('[syncMissingDescriptions] started', ['total' => $listingKeys->count()]);

     foreach ($listingKeys as $listingKey) {
         $this->addPropertyDataFast($listingKey);
     }

     Log::info('[syncMissingDescriptions] finished');
 }

 protected function addPropertyDataFast($listingKey)
 {
     if (empty($listingKey)) return;

     $url = "https://query.ampre.ca/odata/Property?"
          . "\$filter=ListingKey%20eq%20%27$listingKey%27"
          . "&\$select=ListingContractDate,MlsStatus,TransactionType"
          . "&\$top=1";

     $ch = curl_init($url);
     curl_setopt_array($ch, [
         CURLOPT_RETURNTRANSFER => true,
         CURLOPT_HTTPHEADER => [
             'Authorization: Bearer eyJhbGciOiJIUzI1NiJ9.eyJzdWIiOiJ2ZW5kb3IvdHJyZWIvMTAzMjQiLCJhdWQiOiJBbXBVc2Vyc1ByZCIsInJvbGVzIjpbIkFtcFZlbmRvciJdLCJpc3MiOiJwcm9kLmFtcHJlLmNhIiwiZXhwIjoyNTM0MDIzMDA3OTksImlhdCI6MTc2ODg2NzI4Miwic3ViamVjdFR5cGUiOiJ2ZW5kb3IiLCJzdWJqZWN0S2V5IjoiMTAzMjQiLCJqdGkiOiJjMDRkMzYwMDhlNzc0Zjc4IiwiY3VzdG9tZXJOYW1lIjoidHJyZWIifQ.IBqRgRDkr9eqSzkOqrYQN1m0V_difH0FqHRPM11vL9Y',
             'Accept: application/json',
             'OData-Version: 4.0',
             'OData-MaxVersion: 4.0',
         ],
     ]);
     $response = curl_exec($ch);
     curl_close($ch);

     $payload = json_decode($response, true);

     if (!isset($payload['value'][0])) return;

     $item = $payload['value'][0];

     Property::updateOrCreate(
         ['external_id' => $item['ListingKey']], 
         [
             'created_at' => $item['ListingContractDate'] ?? '0',
             'MlsStatus' => $item['MlsStatus'] ?? '0',
             'TransactionType' => $item['TransactionType'] ?? '0',
         ]
     );
 }
 */

    public function syncStatus()
    {
        return response()->json([
            'status' => Cache::get('sync_status', 'idle'),
            'current_processing_id' => Cache::get('current_processing_id'),
            'current_external_id' => Cache::get('current_processing_external'),
            'last_processed_id' => Cache::get('last_processed_id'),
            'last_error' => Cache::get('last_error'),
            'last_activity_time' => Cache::get('last_activity_time'),
        ]);
    }




    public function syncMissingDescriptions()
    {
        // ✅ Detect if already running + not stuck
        $lastActivity = Cache::get('last_activity_time');

        if (
            Cache::get('sync_running') &&
            $lastActivity &&
            now()->diffInMinutes($lastActivity) < 5
        ) {
            return response()->json([
                'status' => 'already running'
            ]);
        }

        // ✅ Start fresh lock
        Cache::put('sync_running', true, 800);
        Cache::put('sync_status', 'running');

        try {

            set_time_limit(0);

            $lastId = Cache::get('last_processed_id');

            $query = Property::whereNotNull('external_id')
                ->where(function ($q) {
                    $q->whereNull('updated_at')
                        ->orWhere('updated_at', '<', now()->subDay());
                });

            if ($lastId) {
                $query->where('id', '>', $lastId);
            }

            $processedAny = false;

            $query->orderBy('id')
                ->chunk(100, function ($properties) use (&$processedAny) {

                    foreach ($properties as $property) {

                        $processedAny = true;

                        // =========================
                        // 🔥 LIVE TRACKING
                        // =========================
                        Cache::put('current_processing_id', $property->id);
                        Cache::put('current_processing_external', $property->external_id);
                        Cache::put('last_activity_time', now());

                        // =========================
                        // 🔥 FAIL SAFE COUNTER
                        // =========================
                        $failKey = 'fail_' . $property->id;
                        $failCount = Cache::get($failKey, 0);

                        try {
                            $this->addPropertyDataFast($property->external_id);

                            // reset fail count on success
                            Cache::forget($failKey);

                        } catch (\Exception $e) {

                            Cache::increment($failKey);

                            Cache::put('last_error', $e->getMessage());

                            Log::error('[syncMissingDescriptions] Sync Failed', [
                                'property_id' => $property->id,
                                'external_id' => $property->external_id,
                                'error' => $e->getMessage(),
                            ]);

                            // 🚨 Skip permanently after 3 failures
                            if ($failCount >= 3) {
                                Cache::put('last_processed_id', $property->id);
                                continue;
                            }
                        }

                        // =========================
                        // 🔥 ALWAYS MOVE FORWARD
                        // =========================
                        Cache::put('last_processed_id', $property->id);

                        // throttle API
                        usleep(200000);
                    }
                });

            // =========================
            // 🔥 AUTO RESTART LOGIC
            // =========================
            if (!$processedAny) {
                Cache::forget('last_processed_id');
                Cache::put('sync_status', 'restarting');
            } else {
                Cache::put('sync_status', 'running');
            }

        } finally {
            Cache::forget('sync_running');
        }

        return response()->json([
            'status' => Cache::get('sync_status')
        ]);
    }

    public function addPropertyDataFast($listingKey)
    {
        if (empty($listingKey)) {
            return;
        }

        $url = "https://query.ampre.ca/odata/Property?"
            . "\$filter=ListingKey%20eq%20%27$listingKey%27"
            . "&\$select=ListingKey,ListingContractDate,MlsStatus,TransactionType,ClosePrice"
            . "&\$top=1";

        $ch = curl_init($url);

        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER => [
                'Authorization: Bearer ' . (TrebPropertyHelper::ampTokens()[0] ?? ''),
                'Accept: application/json',
                'OData-Version: 4.0',
                'OData-MaxVersion: 4.0',
            ],
        ]);

        $response = curl_exec($ch);

        // ❗ Capture HTTP status
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);

        if (curl_errno($ch)) {
            throw new \Exception('Curl Error: ' . curl_error($ch));
        }

        curl_close($ch);

        // ❗ Handle API failure safely
        if ($httpCode !== 200) {
            throw new \Exception("API Error: HTTP $httpCode | Response: $response");
        }

        $payload = json_decode($response, true);

        if (!isset($payload['value'][0])) {
            return;
        }

        $item = $payload['value'][0];

        $closePrice = $item['ClosePrice'] ?? 0;

        if (!is_numeric($closePrice)) {
            $closePrice = 0;
        }

        Property::where('external_id', $item['ListingKey'])
            ->update([
                'created_at' => $item['ListingContractDate'] ?? null,
                'MlsStatus' => $item['MlsStatus'] ?? null,
                'TransactionType' => $item['TransactionType'] ?? null,
                'ClosePrice' => $closePrice,
                'updated_at' => now(),
            ]);
    }



    public function addSingleProperty($listingKey)
    {
        if (empty($listingKey)) {
            return response()->json([
                'error' => 'ListingKey is required',
            ], 400);
        }

        $normalizedKey = strtoupper(trim((string) $listingKey));
        $service = app(\Botble\RealEstate\Services\LiveTrebPropertyFallbackService::class);
        $existing = Property::query()->where('external_id', $normalizedKey)->first();

        if ($existing) {
            $this->ensurePropertySlug((int) $existing->id, (string) $existing->name, $normalizedKey);

            return response()->json([
                'status' => 'exists',
                'skipped' => true,
                'property_id' => $existing->id,
                'listing_key' => $normalizedKey,
            ]);
        }

        $property = $service->ingestByListingKey($normalizedKey, true, false);

        if ($property === null) {
            return response()->json([
                'error' => 'Property not found or API returned empty response',
            ], 404);
        }

            return response()->json([
            'status' => 'imported',
            'property_id' => $property->id,
            'listing_key' => $normalizedKey,
        ]);
    }

    public function syncPropertiesOneByOne()
    {
        $totalUpdated = 0;

        Property::whereNull('broker')
            ->whereNotNull('external_id')
            ->chunk(100, function ($properties) use (&$totalUpdated) {

                foreach ($properties as $property) {

                    //$listingKey = $property->external_id;
    


                    // Update ONLY existing property (no create)
                    $property->update([

                        'unique_id' => $this->generateUniqueId(),


                    ]);

                    $totalUpdated++;


                }
            });

        return response()->json([
            'success' => true,
            'updated' => $totalUpdated
        ]);
    }







    private function normalizeNumber($value)
    {
        if (! $value) {
            return null;
        }

        $clean = preg_replace('/[^0-9.]/', '', (string) $value);

        return is_numeric($clean) ? (float) $clean : null;
    }

    private function normalizeSquare($value)
    {
        if (! $value) {
            return null;
        }

        if (is_array($value)) {
            return null;
        }

        $value = trim((string) $value);

        if (preg_match('/^(\d+)\s*-\s*(\d+)$/', $value)) {
            return $value;
        }

        return TrebPropertyHelper::normalizeSquareStorage($value);
    }

    private function extractBelowGradeBedrooms(array $item): int
    {
        return max(0, (int) ($item['BedroomsBelowGrade'] ?? 0));
    }

    private function extractMainBedrooms(array $item): int
    {
        if (isset($item['BedroomsAboveGrade']) && is_numeric($item['BedroomsAboveGrade'])) {
            return max(0, (int) $item['BedroomsAboveGrade']);
        }

        $total = (int) ($item['BedroomsTotal'] ?? 0);
        $below = $this->extractBelowGradeBedrooms($item);

        if ($total > 0 && $total >= $below) {
            return $total - $below;
        }

        return max(0, $total);
    }

    private function extractNumberFloor(array $item): int
    {
        $candidates = [
            $item['StoriesTotal'] ?? null,
            $item['Levels'] ?? null,
            is_array($item['ArchitecturalStyle'] ?? null)
                ? implode(' ', $item['ArchitecturalStyle'])
                : ($item['ArchitecturalStyle'] ?? null),
        ];

        foreach ($candidates as $candidate) {
            if (is_numeric($candidate)) {
                return max(0, (int) $candidate);
            }

            if (! is_string($candidate) || trim($candidate) === '') {
                continue;
            }

            $value = trim($candidate);

            if (preg_match('/(\d+(?:\.\d+)?)\s*-\s*storey/i', $value, $matches)) {
                return max(0, (int) round((float) $matches[1]));
            }

            if (preg_match('/(\d+(?:\.\d+)?)\s*(?:storey|story|floor)/i', $value, $matches)) {
                return max(0, (int) round((float) $matches[1]));
            }
        }

        return 0;
    }


    private function generateUniqueId(int $length = 10): string
    {
        $chars = 'ABCDEFGHJKLMNPQRSTUVWXYZ';
        return substr(str_shuffle($chars), 0, $length);
    }













    public function importAllPropertyImages()
    {
        // cache()->put('property_image_last_id', 0);
        return Cache::lock('import-property-images-lock', 300)->block(5, function () {

            $lastId = cache()->get('property_image_last_id', 0);

            Property::where(function ($q) {
                $q->whereNull('image_val')
                    ->orWhere('image_val', '')
                    ->orWhere('image_val', 'like', 'http%');
            })
                ->where('id', '>', $lastId)
                ->orderBy('id')
                ->chunkById(20, function ($properties) {

                    foreach ($properties as $property) {

                        $listingKey = $property->external_id;
                        if (empty($listingKey)) {
                            continue;
                        }

                        $url = "https://query.ampre.ca/odata/Media?"
                            . "%24filter=ResourceRecordKey%20eq%20%27{$listingKey}%27"
                            . "&%24top=5"
                            . "&%24select=MediaURL,ImageSizeDescription";

                        $response = $this->ampCurl($url);

                        if (!isset($response['value']) || !is_array($response['value'])) {
                            continue;
                        }

                        $firstImage = null;

                        foreach ($response['value'] as $item) {
                            if (!empty($item['MediaURL'])) {
                                $firstImage = $item['MediaURL'];
                                break;
                            }
                        }

                        if (!$firstImage) {
                            continue;
                        }

                        if ($this->assignTrebCoverImage($property, $listingKey, $firstImage)) {
                            $property->save();
                        }

                        cache()->put('property_image_last_id', $property->id);

                        usleep(300000);
                    }
                });

            return 'Images imported successfully';
        });
    }











    public function getImageURLs(array $images): array
    {
        $images = array_values(array_filter($images));

        $limitDownloadImageFormUrl = 5;
        $i = 0;

        foreach ($images as $key => $image) {
            $images[$key] = str_replace(RvMedia::getUploadURL() . '/', '', trim($image));
            $images[$key] = str_replace('storage/', '', ltrim($images[$key], '/'));

            if (Str::contains($images[$key], ['http://', 'https://']) && $i < $limitDownloadImageFormUrl) {
                $images[$key] = $this->uploadImageFromURL($images[$key]);
                $i++;
            }
        }

        return $images;
    }

    public function uploadImageFromURL(?string $url): ?string
    {
        if (empty($url)) {
            return $url;
        }

        $info = pathinfo($url);

        try {
            $contents = file_get_contents($url);
        } catch (Exception) {
            return $url;
        }

        if (empty($contents)) {
            return $url;
        }

        $path = '/tmp';

        File::ensureDirectoryExists($path);

        $path = $path . '/' . $info['basename'];

        file_put_contents($path, $contents);

        $mimeTypeDetection = (new MimeTypes())->getMimeTypes(File::extension($url));

        $mimeType = Arr::first($mimeTypeDetection);

        $fileUpload = new UploadedFile($path, $info['basename'], $mimeType, null, true);

        $result = RvMedia::handleUpload($fileUpload, 0, 'properties');

        File::delete($path);

        if (!$result['error']) {
            $url = $result['data']->url;
        }

        return $url;
    }


    public function addAllOntarioProperties()
    {
        $top = 500; // number of records per request
        $skip = 0;  // start at 0
        $totalSynced = 0;

        do {
            // Build OData URL with pagination, Ontario filter, and select only needed fields
            $url = "https://query.ampre.ca/odata/Property?" . http_build_query([
                '$top' => $top,
                '$skip' => $skip,
                '$select' => "ListingKey,UnparsedAddress,PostalCode,PropertySubType,StandardStatus"
            ]);

            $ch = curl_init($url);



            $token = TrebPropertyHelper::ampTokens()[0] ?? null;

            curl_setopt_array($ch, [
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_HTTPHEADER => [
                    'Authorization: Bearer ' . $token,
                    'Accept: application/json',
                    'OData-Version: 4.0',
                    'OData-MaxVersion: 4.0',
                ],
            ]);

            $response = curl_exec($ch);

            if ($response === false) {
                curl_close($ch);
                return response()->json([
                    'error' => curl_error($ch)
                ], 500);
            }

            curl_close($ch);

            $payload = json_decode($response, true);

            if (!isset($payload['value']) || empty($payload['value'])) {
                break; // no more records
            }

            foreach ($payload['value'] as $item) {
                if (empty($item['ListingKey']))
                    continue;

                // Only store required fields
                Property::updateOrCreate(
                    ['external_id' => $item['ListingKey']],
                    [
                        'name' => $item['UnparsedAddress'] ?? null,
                        'location' => $item['UnparsedAddress'] ?? null,
                        'zip_code' => $item['PostalCode'] ?? null,
                        'status' => ($item['StandardStatus'] ?? '') === 'Active' ? 'selling' : 'draft',
                        'moderation_status' => 'approved',
                        'type' => $item['PropertySubType'] ?? 'sell',
                        'latitude' => 0,
                        'longitude' => 0,
                    ]
                );
            }

            $totalSynced += count($payload['value']);
            $skip += $top; // next page

        } while (true);

        return response()->json([
            'status' => 'success',
            'total_synced' => $totalSynced
        ]);
    }


    public function create_slug()
    {
        $created = 0;
        $updated = 0;

        Property::with('slugable')
            ->chunk(200, function ($properties) use (&$created, &$updated) {

                foreach ($properties as $property) {

                    // Generate new slug format
                    $baseSlug = Str::slug($property->name ?? 'property');

                    $uniqueKey = $property->external_id
                        ? strtolower($property->external_id)
                        : $property->id;

                    $newSlug = $baseSlug . '-' . $uniqueKey;

                    // Case 1: No slug exists → CREATE
                    if (!$property->slugable) {

                        SlugHelper::createSlug($property, $newSlug);
                        $created++;
                        continue;
                    }

                    // Case 2: Slug exists but different → UPDATE
                    if ($property->slugable->key !== $newSlug) {

                        $property->slugable->update([
                            'key' => $newSlug
                        ]);

                        $updated++;
                    }
                }
            });

        return response()->json([
            'success' => true,
            'created' => $created,
            'updated' => $updated,
            'message' => "Slug sync completed."
        ]);
    }





    public function geocode(?array $propertyIds = null, ?int $limit = null, array $opts = [])
    {
        $idCount = is_array($propertyIds) ? count($propertyIds) : 0;
        $isTargeted = $idCount > 0;
        $batchLimit = $isTargeted
            ? ($limit ?? $idCount)
            : ($limit ?? 150);

        // Nominatim enforces ~1 request/second PER IP. Backlog sweeps (no ID
        // list) take a bulk lock. Targeted batches from sync-live / chain jobs
        // always run so newly imported listings are geocoded 100% in-tick.
        $lock = null;
        if (! $isTargeted) {
            // Lock TTL must cover one batch (~limit seconds of Nominatim) but not
            // strand the queue for an hour if a worker is killed mid-run.
            $lockTtl = max(300, min(900, $batchLimit * 3));
            $lock = Cache::lock('serik:geocode:bulk', $lockTtl);
            if (! $lock->get()) {
                return response()->json([
                    'message' => 'Another bulk geocode is running',
                    'processed' => 0,
                    'geocoded' => 0,
                    'skipped' => true,
                    'locked' => true,
                ]);
            }
        }

        try {
        $result = $this->geocodePropertyBatch(
                $batchLimit,
                $propertyIds,
                $opts
        );
        } finally {
            optional($lock)->release();
        }

        if ($result['processed'] === 0) {
            return response()->json([
                'message' => 'No properties to geocode',
                'processed' => 0,
                'geocoded' => 0,
                'borrowed' => 0,
            ]);
        }

        if (! empty($result['error'])) {
            return response()->json([
                'error' => 'Geocoding failed',
                'details' => $result['error'],
                'processed' => $result['processed'],
                'geocoded' => $result['geocoded'],
                'borrowed' => $result['borrowed'] ?? 0,
            ], 500);
        }

        return response()->json([
            'message' => 'Geocoding completed',
            'processed' => $result['processed'],
            'geocoded' => $result['geocoded'],
            'borrowed' => $result['borrowed'] ?? 0,
            'failed' => $result['failed'] ?? 0,
            'nominatim_calls' => $result['nominatim_calls'] ?? 0,
        ]);
    }

    /**
     * @param  array<int, int>|null  $propertyIds
     * @param  array{active_only?: bool, days?: int}  $opts
     * @return array{processed: int, geocoded: int, error?: string}
     */
    private function geocodePropertyBatch(int $limit = 200, ?array $propertyIds = null, array $opts = []): array
    {
        // Nominatim is rate-limited (~1 req/sec), so a full batch can easily
        // exceed php.ini max_execution_time (300s). On CLI/cron there is no HTTP
        // request to protect, so lift the limit and let the batch finish.
        if (PHP_SAPI === 'cli') {
            @set_time_limit(0);
        }

        $selectCols = ['id', 'external_id', 'name', 'location', 'zip_code', 'latitude', 'longitude', 'MlsStatus', 'TransactionType'];
        $activeStatuses = ['New', 'Price Change', 'Extension', 'Ext', 'Previous Status', 'Active'];
        $activeOnly = ! empty($opts['active_only']);
        $activeDays = max(0, (int) ($opts['days'] ?? 0));

        if ($propertyIds !== null && $propertyIds !== []) {
            $properties = Property::query()
                ->whereIn('id', $propertyIds)
            ->where(function ($q) {
                    $q->whereNull('latitude')->orWhere('latitude', 0)
                        ->orWhereNull('longitude')->orWhere('longitude', 0);
                })
            ->where(function ($q) {
                $q->where(function ($q2) {
                    $q2->whereNotNull('location')->where('location', '!=', '');
                })->orWhere(function ($q2) {
                    $q2->whereNotNull('name')->where('name', '!=', '');
                });
                })
                ->limit($limit)
                ->get($selectCols);
        } else {
            // CRITICAL: select rows that NEED coords (latitude=0), not "newest N
            // actives then filter". The old window left ~30k+ active sales stuck
            // forever once the newest few hundred already had coordinates.
            $hasAddress = function ($q) {
                $q->where(function ($q2) {
                    $q2->whereNotNull('location')->where('location', '!=', '');
                })->orWhere(function ($q2) {
                    $q2->whereNotNull('name')->where('name', '!=', '');
                });
            };

            $applyActiveWindow = function ($q) use ($activeDays) {
                if ($activeDays > 0) {
                    $q->where('listing_contract_date', '>=', now()->subDays($activeDays)->toDateString());
                }
            };

            // 1) Active For Sale first — newest listings first (what "Last 1/3/7 days" needs)
            $properties = Property::query()
                ->where('latitude', 0)
                ->whereIn('MlsStatus', $activeStatuses)
                ->where('TransactionType', 'For Sale')
                ->where($hasAddress)
                ->tap($applyActiveWindow)
                ->tap(fn ($q) => $this->applyGeocodeQueueSkip($q))
                ->orderByDesc('listing_contract_date')
                ->orderByDesc('id')
                ->limit($limit)
                ->get($selectCols);

            // 2) Active For Lease
            if ($properties->count() < $limit) {
                $need = $limit - $properties->count();
                $haveIds = $properties->pluck('id')->all();
                $extra = Property::query()
                    ->where('latitude', 0)
                    ->whereIn('MlsStatus', $activeStatuses)
                    ->where('TransactionType', 'For Lease')
                    ->where($hasAddress)
                    ->tap($applyActiveWindow)
                    ->tap(fn ($q) => $this->applyGeocodeQueueSkip($q))
                    ->when($haveIds !== [], fn ($q) => $q->whereNotIn('id', $haveIds))
                    ->orderByDesc('listing_contract_date')
                    ->orderByDesc('id')
                    ->limit($need)
                    ->get($selectCols);
                $properties = $properties->concat($extra)->values();
            }

            // 3) Any other missing coords (sold / terminated / etc.) — skipped for map-first runs
            if (! $activeOnly && $properties->count() < $limit) {
                $need = $limit - $properties->count();
                $haveIds = $properties->pluck('id')->all();
                $extra = Property::query()
                    ->where('latitude', 0)
                    ->where($hasAddress)
                    ->tap(fn ($q) => $this->applyGeocodeQueueSkip($q))
                    ->when($haveIds !== [], fn ($q) => $q->whereNotIn('id', $haveIds))
                    ->orderByDesc('id')
                    ->limit($need)
                    ->get($selectCols);
                $properties = $properties->concat($extra)->values();
            }
        }

        if ($properties->isEmpty()) {
            \Log::info('[geocode] No properties need coordinates (latitude/longitude missing or 0).');

            return ['processed' => 0, 'geocoded' => 0, 'borrowed' => 0, 'failed' => 0];
        }

        \Log::info('[geocode] Found ' . $properties->count() . ' properties needing coordinates.');

        $geocoded = 0;
        $borrowed = 0;
        $failed = 0;
        $count = $properties->count();
        $rateLimitMs = max(0, (int) config('services.nominatim.rate_limit_ms', 1100));
        $nominatimCalls = 0;

        foreach ($properties->values() as $i => $property) {
            $base = trim((string) ($property->location ?: $property->name ?: ''));

            if ($base === '') {
                continue;
            }

            // Free path: copy coords from another unit/listing on the same street.
            // Condos often share a building — this clears thousands without Nominatim.
            $coords = $this->borrowCoordsFromSibling($property);
            $fromBorrow = $coords !== null;
            $providerMeta = null;

            if ($coords === null && class_exists(\App\Services\Geocoding\GeocodingManager::class)) {
                $geocoder = app(\App\Services\Geocoding\GeocodingManager::class);
                if ($geocoder->isConfigured()) {
                    try {
                        $g = $geocoder->geocode($property);
                        if (is_array($g) && isset($g['lat'], $g['lng'])) {
                            $coords = ['lat' => $g['lat'], 'lng' => $g['lng']];
                            $providerMeta = $g;
                        }
                    } catch (\Throwable $e) {
                        if (str_contains($e->getMessage(), 'OVER_QUERY_LIMIT')) {
                            \Log::channel('geocoding')->warning(
                                strtoupper($geocoder->providerName()) . ' OVER_QUERY_LIMIT in batch — stopping round'
                            );
                            break;
                        }
                        \Log::channel('geocoding')->warning(
                            $geocoder->providerName() . ' batch error: ' . $e->getMessage()
                        );
                    }
                }
            }

            if ($coords === null) {
            $candidates = $this->buildGeocodeCandidates($base, (string) ($property->zip_code ?? ''));
            $usedAddress = '';

            foreach ($candidates as $ci => $address) {
                $usedAddress = $address;
                \Log::info("[geocode] ({$property->external_id}) requesting: {$address}");

                $coords = $this->nominatimGeocode($address);
                    $nominatimCalls++;

                if ($coords !== null) {
                    break;
                }

                if ($ci < count($candidates) - 1 && $rateLimitMs > 0) {
                    usleep($rateLimitMs * 1000);
                }
            }

            if ($coords === null) {
                $failed++;
                    $this->recordGeocodeFailure($property, $usedAddress, 'no match from geocoder');
                \Log::warning("[geocode] No coordinates for {$property->external_id} ({$usedAddress}).");

                    if ($i < $count - 1 && $rateLimitMs > 0) {
                        usleep($rateLimitMs * 1000);
                    }

                    continue;
                }
            }

            $update = [
                    'latitude' => $coords['lat'],
                    'longitude' => $coords['lng'],
            ];
            if (is_array($providerMeta)) {
                $ok = \App\Support\GeocodePersistence::apply($property, array_merge($providerMeta, [
                    'lat' => $coords['lat'],
                    'lng' => $coords['lng'],
                ]), $providerMeta['provider'] ?? null);

                if (! $ok) {
                    $failed++;
                    continue;
                }
            } else {
                // Borrow / Nominatim path — still use audited persistence.
                $ok = \App\Support\GeocodePersistence::apply($property, [
                    'lat' => $coords['lat'],
                    'lng' => $coords['lng'],
                    'provider' => $fromBorrow ? 'borrow' : 'nominatim',
                    'status' => 'OK',
                    'searched_address' => $base,
                ], $fromBorrow ? 'borrow' : 'nominatim');

                if (! $ok) {
                    $failed++;
                    continue;
                }
            }

            $this->clearGeocodeFailure((int) $property->id);
            if (class_exists(\App\Support\GeocodeState::class)) {
                \App\Support\GeocodeState::markDone((int) $property->id);
            }
            // Ensure Meili gets _geo even when scout.queue was true / worker lagged.
            $this->syncPropertyToSearchIndex($property);
                $geocoded++;
            if ($fromBorrow) {
                $borrowed++;
            }
            $via = $fromBorrow
                ? ' (borrowed)'
                : ($providerMeta ? ' (' . ($providerMeta['provider'] ?? 'provider') . ')' : '');
            \Log::info("[geocode] Updated {$property->external_id} -> lat={$coords['lat']}, lng={$coords['lng']}"
                . $via . '.');

            // Only sleep after a real Nominatim call — borrowed/provider coords are free-ish.
            if (! $fromBorrow && $providerMeta === null && $i < $count - 1 && $rateLimitMs > 0) {
                usleep($rateLimitMs * 1000);
            }
        }

        return [
            'processed' => $count,
            'geocoded' => $geocoded,
            'borrowed' => $borrowed,
            'failed' => $failed,
            'nominatim_calls' => $nominatimCalls,
        ];
    }

    /**
     * Push a freshly geocoded row into Meili immediately (sync), so map pins
     * appear without waiting on a database queue worker.
     */
    private function syncPropertyToSearchIndex(Property $property): void
    {
        try {
            $previous = config('scout.queue');
            config(['scout.queue' => false]);
            $property->refresh();
            if ($property->shouldBeSearchable()) {
                $property->searchable();
            }
            config(['scout.queue' => $previous]);
        } catch (\Throwable $e) {
            \Log::warning('[geocode] Meili sync failed for ' . $property->external_id . ': ' . $e->getMessage());
        }
    }

    /**
     * Reuse lat/lng from another listing at the same street number + name.
     * Instant and Nominatim-free — critical for condo buildings.
     *
     * @return array{lat: float, lng: float}|null
     */
    private function borrowCoordsFromSibling(object $property): ?array
    {
        $base = trim((string) ($property->location ?: $property->name ?: ''));
        if ($base === '') {
            return null;
        }

        $parsed = TrebPropertyHelper::enrichRecordAddress(['UnparsedAddress' => $base]);
        $streetNumber = trim((string) ($parsed['StreetNumber'] ?? ''));
        $streetName = trim((string) ($parsed['StreetName'] ?? ''));

        if ($streetNumber === '' || $streetName === '' || mb_strlen($streetName) < 2) {
            return null;
        }

        $patterns = array_values(array_unique([
            $streetNumber . ' ' . $streetName . '%',
            $streetNumber . ' ' . strtoupper($streetName) . '%',
            $streetNumber . ' ' . ucwords(strtolower($streetName)) . '%',
        ]));

        $sibling = DB::table('re_properties')
            ->where('id', '!=', (int) $property->id)
            ->where('latitude', '!=', 0)
            ->where('longitude', '!=', 0)
            ->where(function ($q) use ($patterns) {
                foreach ($patterns as $i => $pattern) {
                    $method = $i === 0 ? 'where' : 'orWhere';
                    $q->{$method}(function ($inner) use ($pattern) {
                        $inner->where('name', 'like', $pattern)
                            ->orWhere('location', 'like', $pattern);
                    });
                }
            })
            ->orderByDesc('updated_at')
            ->limit(1)
            ->first(['latitude', 'longitude']);

        if (! $sibling) {
            return null;
        }

        $lat = (float) $sibling->latitude;
        $lng = (float) $sibling->longitude;

        if (! $this->isWithinOntario($lat, $lng)) {
            return null;
        }

        return ['lat' => $lat, 'lng' => $lng];
    }

    /**
     * Exclude rows that are quarantined (permanent_fail) or still inside their
     * exponential-backoff window from a geocode-selection query. A point lookup
     * on the unique property_id index keeps this cheap even on the big table.
     */
    private function applyGeocodeQueueSkip($query): void
    {
        if (! Schema::hasTable('re_geocode_queue')) {
            return;
        }

        $query->whereNotExists(function ($sub) {
            $sub->select(DB::raw(1))
                ->from('re_geocode_queue')
                ->whereColumn('re_geocode_queue.property_id', 're_properties.id')
                ->where(function ($w) {
                    $w->where('permanent_fail', 1)
                        ->orWhere('next_attempt_at', '>', now());
                });
        });
    }

    /**
     * Record a failed geocode with exponential backoff so the same address is
     * not retried on every round (root cause of the stalled backlog). After
     * ~10 attempts the row is quarantined (permanent_fail) and reported instead
     * of endlessly consuming the ~1 req/sec Nominatim budget.
     */
    private function recordGeocodeFailure(object $property, string $address, string $error): void
    {
        if (! Schema::hasTable('re_geocode_queue')) {
            return;
        }

        try {
            $id = (int) $property->id;
            $existing = DB::table('re_geocode_queue')->where('property_id', $id)->first();
            $attempts = (int) ($existing->attempts ?? 0) + 1;

            // 2h, 4h, 8h, ... capped at 30 days. Quarantine after 10 tries.
            $backoffHours = min(720, 2 ** min($attempts, 10));
            $permanent = $attempts >= 10;

            DB::table('re_geocode_queue')->updateOrInsert(
                ['property_id' => $id],
                [
                    'external_id' => $property->external_id ?? null,
                    'attempts' => $attempts,
                    'last_error' => mb_substr($error, 0, 250),
                    'last_address' => mb_substr($address, 0, 250),
                    'last_attempt_at' => now(),
                    'next_attempt_at' => now()->addHours($backoffHours),
                    'permanent_fail' => $permanent,
                    'updated_at' => now(),
                    'created_at' => $existing->created_at ?? now(),
                ]
            );

            if ($permanent && class_exists(\App\Support\GeocodeState::class)) {
                \App\Support\GeocodeState::markFailed($id, true);
            }
        } catch (\Throwable $e) {
            \Log::warning('[geocode] failed to record failure: ' . $e->getMessage());
        }
    }

    private function clearGeocodeFailure(int $propertyId): void
    {
        if (! Schema::hasTable('re_geocode_queue')) {
            return;
        }

        try {
            DB::table('re_geocode_queue')->where('property_id', $propertyId)->delete();
        } catch (\Throwable $e) {
            // best-effort
        }
    }

    /**
     * Build ordered geocoder query candidates for a listing address:
     *   1) cleaned address + postal code (best precision)
     *   2) cleaned address without postal code (MLS zips are often wrong)
     *
     * @return array<int, string>
     */
    private function buildGeocodeCandidates(string $base, string $zip = ''): array
    {
        $clean = $this->cleanAddressText($base);
        $zip = $this->cleanAddressText($zip);

        $primary = $clean;
        if ($zip !== '' && stripos($primary, $zip) === false) {
            $primary .= ', ' . $zip;
        }

        $candidates = [];
        $candidates[] = $this->appendRegion($primary);

        // Candidate 1b: strip a trailing unit number from the street line.
        // AMP addresses embed the unit inline ("1440 Clarriage Court 410"),
        // which Nominatim usually cannot resolve; the building address can.
        $noUnit = $this->stripUnitFromStreetLine($clean);
        if ($noUnit !== $clean && $noUnit !== '') {
            $noUnitPrimary = $noUnit;
            if ($zip !== '' && stripos($noUnitPrimary, $zip) === false) {
                $noUnitPrimary .= ', ' . $zip;
            }
            $candidates[] = $this->appendRegion($noUnitPrimary);
            $candidates[] = $this->appendRegion($this->stripPostalCode($noUnit));
        }

        // Candidate 2: full address with the MLS community descriptor removed.
        // MLS stores things like "Orleans - Cumberland and Area" in the city slot
        // which OSM/Nominatim cannot resolve; reduce it to the real place name.
        $decommunitied = $this->stripCommunityDescriptor($clean);
        $candidates[] = $this->appendRegion($decommunitied);

        // Candidate 3: street + place only (no postal code), de-communitied.
        $candidates[] = $this->appendRegion($this->stripPostalCode($decommunitied));

        // Candidate 4: city/town centroid — last resort so rural listings that
        // OSM has no street match for still land on the correct municipality.
        // (Canadian postal codes are NOT in OSM, so we use the place name.)
        $city = $this->extractCityFromAddress($decommunitied);
        if ($city !== '') {
            $candidates[] = $city . ', Ontario, Canada';
        }

        // De-duplicate while preserving order.
        $seen = [];
        $out = [];
        foreach ($candidates as $c) {
            $c = trim((string) $c, ' ,');
            if ($c !== '' && ! isset($seen[$c])) {
                $seen[$c] = true;
                $out[] = $c;
            }
        }

        return $out;
    }

    /**
     * Reduce MLS "community" descriptors to their real place name so a geocoder
     * can resolve them, e.g.:
     *   "667 Everlasting Crescent, Orleans - Cumberland and Area, ON K4A 0K3"
     *   -> "667 Everlasting Crescent, Orleans, ON K4A 0K3"
     */
    private function stripCommunityDescriptor(string $address): string
    {
        $parts = array_map('trim', explode(',', $address));

        $parts = array_map(function (string $segment, int $index): string {
            // "Orleans - Cumberland and Area" -> "Orleans"
            if (str_contains($segment, ' - ')) {
                $segment = trim(explode(' - ', $segment, 2)[0]);
            }
            // Drop leading MLS zone codes like "1014 - QE Queen Elizabeth" / "7711 - ..."
            // NEVER on index 0 (the street line) — that would strip the street
            // number ("333 Glenbrae Avenue" -> "Glenbrae Avenue") and wreck
            // geocode precision. Zone codes only appear in the city/community slot.
            if ($index > 0) {
                $segment = preg_replace('/^\d{3,5}\s*/', '', $segment);
            }
            // Remove trailing "and Area".
            $segment = preg_replace('/\s+and Area$/i', '', (string) $segment);

            return trim((string) $segment);
        }, $parts, array_keys($parts));

        return trim(implode(', ', array_filter($parts)), ' ,');
    }

    /**
     * Pull the municipality out of a cleaned address. The city is the segment
     * after the street line and before the province/postal code.
     */
    private function extractCityFromAddress(string $address): string
    {
        $parts = array_values(array_filter(array_map('trim', explode(',', $address))));

        foreach ($parts as $i => $segment) {
            if ($i === 0) {
                continue; // street line
            }
            // Skip province + postal segments.
            if (preg_match('/^(ON|Ontario)\b/i', $segment)) {
                continue;
            }
            $segment = trim(preg_replace('/\b[A-Za-z]\d[A-Za-z]\s?\d[A-Za-z]\d\b/', '', $segment) ?? '', ' ,');
            if ($segment !== '' && ! is_numeric($segment)) {
                return $segment;
            }
        }

        return '';
    }

    /**
     * Remove a trailing unit/suite token from the first (street) segment of an
     * address so a condo/apartment resolves to its building. Examples:
     *   "1440 Clarriage Court 410, Milton, ON" -> "1440 Clarriage Court, Milton, ON"
     *   "88 Queen Street E 4707, Toronto, ON"  -> "88 Queen Street E, Toronto, ON"
     * Preserves directional suffixes (E/W/N/S) and never strips the street number.
     */
    private function stripUnitFromStreetLine(string $address): string
    {
        $parts = array_map('trim', explode(',', $address));

        if ($parts === [] || $parts[0] === '') {
            return $address;
        }

        // Trailing token that looks like a unit: 410, 4707, PH1, TH12, A4, 1201B.
        // Keep single directional letters (E/W/N/S) which are street suffixes.
        $parts[0] = preg_replace(
            '/\s+(?!(?:[NSEW])$)(?:PH\d+|TH\d+|[A-Za-z]?\d{1,5}[A-Za-z]?)$/i',
            '',
            $parts[0]
        ) ?? $parts[0];

        return trim(implode(', ', $parts), ' ,');
    }

    private function cleanAddressText(string $s): string
    {
        // Drop apostrophes (VENT'S -> VENTS) and collapse whitespace.
        $s = str_replace(["\u{2019}", "'", '`'], '', $s);
        $s = preg_replace('/\s+/', ' ', $s);

        return trim((string) $s, " ,");
    }

    private function stripPostalCode(string $s): string
    {
        // Remove Canadian postal codes (A1A 1A1 / A1A1A1).
        $s = preg_replace('/\b[A-Za-z]\d[A-Za-z]\s?\d[A-Za-z]\d\b/', '', $s);
        $s = preg_replace('/\s+/', ' ', (string) $s);

        return trim((string) $s, " ,");
    }

    private function appendRegion(string $address): string
    {
        $address = trim($address, " ,");

        if ($address === '') {
            return '';
        }

        if (stripos($address, 'ontario') === false && ! preg_match('/,\s*ON\b/i', $address)) {
            $address .= ', Ontario';
        }

        if (stripos($address, 'canada') === false) {
            $address .= ', Canada';
        }

        return $address;
    }

    /**
     * Geocode a single free-form address with OpenStreetMap Nominatim.
     * Returns ['lat' => float, 'lng' => float] or null when not found.
     */
    private function nominatimGeocode(string $address): ?array
    {
        $endpoint = rtrim((string) config('services.nominatim.url', 'https://nominatim.openstreetmap.org/search'), '/');
        $userAgent = (string) config('services.nominatim.user_agent', 'SerikRealEstate/1.0');
        $email = (string) config('services.nominatim.email', '');

        $params = [
            'q' => $address,
            'format' => 'jsonv2',
            'limit' => 1,
            'countrycodes' => 'ca',
            'addressdetails' => 0,
        ];

        if ($email !== '') {
            $params['email'] = $email;
        }

        try {
            $response = Http::timeout(30)
                ->retry(2, 2000, throw: false)
                ->withHeaders([
                    // Nominatim requires an identifying User-Agent.
                    'User-Agent' => $userAgent,
                    'Accept' => 'application/json',
                    'Referer' => (string) config('app.url', ''),
                ])
                ->get($endpoint, $params);
        } catch (\Throwable $e) {
            \Log::warning('[geocode] Nominatim request threw: ' . $e->getMessage());

            return null;
        }

        if (! $response->successful()) {
            \Log::warning('[geocode] Nominatim HTTP ' . $response->status() . ' for: ' . $address);

            return null;
        }

        $data = $response->json();

        if (! is_array($data) || empty($data[0]['lat']) || empty($data[0]['lon'])) {
            return null;
        }

        $lat = (float) $data[0]['lat'];
        $lng = (float) $data[0]['lon'];

        // Accuracy guard: reject anything outside Ontario's bounding box so a
        // wrong-province match never gets written. All TRREB listings are in ON.
        if (! $this->isWithinOntario($lat, $lng)) {
            \Log::warning("[geocode] Rejected out-of-Ontario result ({$lat},{$lng}) for: {$address}");

            return null;
        }

        return ['lat' => $lat, 'lng' => $lng];
    }

    /**
     * Ontario bounding box (generous): latitude 41.6–57.0, longitude -95.2–-74.0.
     */
    public function isWithinOntario(float $lat, float $lng): bool
    {
        return $lat >= 41.6 && $lat <= 57.0 && $lng >= -95.2 && $lng <= -74.0;
    }





    public function fetchProperties(Request $request)
    {
        $keyword = trim($request->keyword ?? '');

        if ($keyword === '') {
            return response()->json([]);
        }

        try {




            //Build AMP filter safely
            $filterQuery = $this->buildFilterQuery($keyword);

            $url = "https://query.ampre.ca/odata/Property?"
                . "\$filter={$filterQuery}"
                . "&\$top=5"
                . "&\$select=UnparsedAddress,BedroomsAboveGrade,BedroomsBelowGrade,BathroomsTotalInteger,ParkingTotal,LivingAreaRange,PropertySubType,TaxAnnualAmount,LotWidth,LotDepth";

            //dd($url);
            dd($this->ampCurl($url));
            $response = Cache::remember(
                'amp_search_' . md5($url),
                30,
                fn() => $this->ampCurl($url)
            );


            dd($response);


            $data = json_decode($response, true);
            $records = $data['value'] ?? [];

            if (!is_array($records)) {
                return response()->json([]);
            }





            return response()->json($records);

        } catch (\Throwable $e) {

            \Log::error('AMP Fetch Properties Error', [
                'message' => $e->getMessage()
            ]);

            return response()->json([]);
        }
    }




    private function applyLocalOntarioResidentialScope($query): void
    {
        // Previously used multiple leading-% LIKE patterns (%, ON %) which forced
        // full table scans (~180k rows). TREB inventory is Ontario-scoped at
        // ingest; bounding Meili/map to Ontario coords is enough. Keep only the
        // commercial subtype exclusion (sargable whereNotIn).
        $this->applyResidentialSubTypeScope($query);
    }

    private function applyResidentialSubTypeScope($query): void
    {
        $excluded = TrebPropertyHelper::excludedCommercialSubTypes();
        $excludedExpanded = array_values(array_unique(array_merge(
            $excluded,
            array_map(static fn ($v) => $v . ' ', $excluded)
        )));

        $query->where(function ($q) use ($excludedExpanded) {
            $q->whereNull('PropertySubType')
                ->orWhere('PropertySubType', '')
                ->orWhereNotIn('PropertySubType', $excludedExpanded);
        });
    }

    private function applyMapResidentialScope($query): void
    {
        $this->applyResidentialSubTypeScope($query);
    }

    private function clampMapBoundsToOntario(float $south, float $north, float $west, float $east): array
    {
        $onSouth = 41.6;
        $onNorth = 56.9;
        $onWest = -95.2;
        $onEast = -74.0;

        return [
            max($south, $onSouth),
            min($north, $onNorth),
            max($west, $onWest),
            min($east, $onEast),
        ];
    }

    /**
     * Pre-encode a map payload once (JSON + gzip bytes) so it can be cached and
     * streamed on warm requests without re-running json_encode / gzencode.
     */
    private function encodeMapPayload(array $data): array
    {
        $json = json_encode($data);

        // Store only the gzip bytes when available (~6x smaller cache blob =>
        // faster warm reads). The uncompressed JSON is reconstructed on the fly
        // for the rare client that does not accept gzip.
        if (function_exists('gzencode')) {
            return ['gz' => gzencode($json, 5), 'json' => null];
        }

        return ['gz' => null, 'json' => $json];
    }

    /**
     * Stream a pre-encoded map payload, using the cached gzip bytes when the
     * client supports it. The map GeoJSON compresses ~6x (2MB -> ~337KB), which
     * dominates real-network transfer time. Safe alongside Apache mod_deflate:
     * a response already carrying Content-Encoding is skipped (no double gzip).
     */
    private function respondMapPayload(Request $request, array $payload)
    {
        $acceptsGzip = ! empty($payload['gz'])
            && str_contains(strtolower((string) $request->header('Accept-Encoding', '')), 'gzip');

        if ($acceptsGzip) {
            return response($payload['gz'], 200, [
                'Content-Type' => 'application/json',
                'Content-Encoding' => 'gzip',
                'Vary' => 'Accept-Encoding',
            ]);
        }

        $json = $payload['json'] ?? (! empty($payload['gz']) ? gzdecode($payload['gz']) : '{}');

        return response($json, 200, ['Content-Type' => 'application/json']);
    }

    /**
     * Snap a viewport outward to a zoom-dependent grid so nearby pans/zooms map
     * to identical cache keys (turning ~800ms Meili calls into ~4ms cache hits).
     */
    private function snapMapBoundsToGrid(float $south, float $north, float $west, float $east, int $zoom): array
    {
        // Step ~= 30-40% of a typical viewport at each zoom: coarse enough that
        // small pans reuse the cache, fine enough to avoid over-fetching a much
        // larger area than the user is looking at.
        $step = match (true) {
            $zoom <= 7 => 0.4,
            $zoom <= 9 => 0.15,
            $zoom <= 11 => 0.06,
            $zoom <= 13 => 0.025,
            default => 0.012,
        };

        $snapDown = static fn (float $v): float => floor($v / $step) * $step;
        $snapUp = static fn (float $v): float => ceil($v / $step) * $step;

        return [
            $snapDown($south),
            $snapUp($north),
            $snapDown($west),
            $snapUp($east),
        ];
    }

    /**
     * Stable signature of the map filter inputs (everything except raw bounds)
     * so the grid cache key changes only when a filter actually changes.
     */
    private function mapFilterSignature(Request $request): string
    {
        $zoom = (int) $request->input('zoom', 10);
        $zoomBucket = match (true) {
            $zoom <= 7 => 'z7',
            $zoom <= 9 => 'z9',
            $zoom <= 11 => 'z11',
            $zoom <= 13 => 'z13',
            default => 'z14',
        };

        return implode(',', [
            $zoomBucket,
            'st=' . trim((string) $request->input('status', '')),
            'tx=' . trim((string) $request->input('transaction', '')),
            'city=' . strtolower(trim((string) $request->input('city', ''))),
            'sub=' . trim((string) $request->input('subtypes', '')),
            'minp=' . (float) $request->input('min_price', 0),
            'maxp=' . (float) $request->input('max_price', 0),
            'bed=' . trim((string) $request->input('bedrooms', '')),
            'bath=' . trim((string) $request->input('bathrooms', '')),
            'date=' . trim((string) $request->input('date', '')),
            'dsold=' . trim((string) $request->input('date_sold', '')),
            'auth=' . ((auth('account')->check() || auth()->check()) ? '1' : '0'),
        ]);
    }

    private function parseAddressSearchKeyword(string $keyword): ?array
    {
        $keyword = trim(preg_replace('/\s+/', ' ', $keyword) ?? '');

        if ($keyword === '') {
            return null;
        }

        // Drop city/region tails: "390 Bank St Ottawa - Centre Town" → "390 Bank St"
        if (str_contains($keyword, ',')) {
            $keyword = trim(explode(',', $keyword, 2)[0]);
        }
        if (str_contains($keyword, ' - ')) {
            $keyword = trim(explode(' - ', $keyword, 2)[0]);
        }
        // Strip trailing province / "Ontario"
        $keyword = trim(preg_replace('/\b(ON|Ontario|QC|Quebec|BC|AB|MB|SK|NS|NB|NL|PE|YT|NT|NU)\b.*$/i', '', $keyword) ?? $keyword);

        if (! preg_match('/^(\d+[A-Za-z]?)\s+(.+)$/', $keyword, $matches)) {
            return null;
        }

        $streetNumber = $matches[1];
        $rest = trim($matches[2]);
        $tokens = preg_split('/\s+/', $rest) ?: [];
        $streetTokens = [];

        foreach ($tokens as $token) {
            $streetTokens[] = $token;
            if (TrebPropertyHelper::isStreetSuffixWord($token)) {
                break;
            }
            // Without a suffix, keep at most 2 name tokens ("Bank", "Queen Mary")
            if (count($streetTokens) >= 2) {
                break;
            }
        }

        if ($streetTokens === []) {
            return null;
        }

        $streetPart = implode(' ', $streetTokens);
        $streetName = '';
        foreach ($streetTokens as $token) {
            if (! TrebPropertyHelper::isStreetSuffixWord($token)) {
                $streetName = $token;
                break;
            }
        }

        if ($streetName === '') {
            $streetName = $streetTokens[0];
        }

            return [
            'street_number' => $streetNumber,
            'street_part' => $streetPart,
            'street_name' => $streetName,
        ];
    }

    /**
     * Require street number + street name so "390 Bank" does not return "390 Cherry".
     */
    private function addressMatchesParsedSearch(string $address, array $parsed): bool
    {
        $address = strtolower(trim($address));
        if ($address === '') {
            return false;
        }

        $number = strtolower(trim((string) ($parsed['street_number'] ?? '')));
        $name = strtolower(trim((string) ($parsed['street_name'] ?? '')));

        if ($number === '' || $name === '') {
            return false;
        }

        if (! preg_match('/\b' . preg_quote($number, '/') . '\b/', $address)) {
            return false;
        }

        // Whole-word street name (Bank ≠ Cherry). Allow "Bank Street" / "Bank St".
        return (bool) preg_match('/\b' . preg_quote($name, '/') . '\b/', $address);
    }

    private function scoreSearchRelevance(array $item, string $keyword): int
    {
        $score = 0;
        $keyword = strtolower(trim($keyword));
        $address = strtolower((string) ($item['UnparsedAddress'] ?? $item['name'] ?? ''));
        $listingKey = strtolower((string) ($item['ListingKey'] ?? ''));

        if ($keyword !== '' && $address === $keyword) {
            $score += 200;
        }

        if ($keyword !== '' && str_contains($address, $keyword)) {
            $score += 120;
        }

        if (preg_match('/^([a-z]\d+)$/i', $keyword) && strcasecmp($listingKey, $keyword) === 0) {
            $score += 300;
        }

        if (preg_match('/(\d+)/', $keyword, $numberMatch) && str_contains($address, $numberMatch[1])) {
            $score += 60;
        }

        foreach (preg_split('/[\s,]+/', $keyword) as $token) {
            $token = trim($token);
            if (strlen($token) < 3) {
                continue;
            }
            if (str_contains($address, $token)) {
                $score += 20;
            }
        }

        if (($item['MlsStatus'] ?? '') === 'New') {
            $score += 5;
        }

        return $score;
    }

    private function sortSearchResults(array $results, string $keyword): array
    {
        usort($results, function ($a, $b) use ($keyword) {
            return $this->scoreSearchRelevance($b, $keyword) <=> $this->scoreSearchRelevance($a, $keyword);
        });

        return $results;
    }

    public function smartSearch(Request $request)
    {
        $keyword = trim($request->keyword ?? '');
        $skip = (int) ($request->skip ?? 0);
        $top = 10;

        if ($keyword === '' || mb_strlen($keyword) < 2) {
            return response()->json([]);
        }

        // Match AMP ListingKeys: C9250979, W4929276, CW12345678, etc.
        $isListingKey = (bool) preg_match('/^[a-z]{1,2}\d{5,}$/i', $keyword);

        if ($isListingKey) {
            $mlsCacheKey = 'smart_search_mls:' . strtoupper($keyword);
            $cachedMls = Cache::get($mlsCacheKey);
            if (is_array($cachedMls)) {
                return response()->json($this->ensureSearchResultSlugs($cachedMls));
            }
        }

        $searchCacheKey = 'smart_search_v1:' . md5(
            strtolower($keyword) . '|' . $skip . '|' . ($request->input('transaction', '')) . '|' . ($request->input('status', ''))
        );
        if (! $isListingKey && mb_strlen($keyword) >= 3 && preg_match('/\d/', $keyword)) {
            $cachedSearch = Cache::get($searchCacheKey);
            if (is_array($cachedSearch)) {
                return response()->json($this->ensureSearchResultSlugs($cachedSearch));
            }
        } elseif (! $isListingKey && mb_strlen($keyword) >= 4) {
            $cachedSearch = Cache::get($searchCacheKey);
            if (is_array($cachedSearch)) {
                return response()->json($this->ensureSearchResultSlugs($cachedSearch));
            }
        }

        $rememberSearch = function (array $payload) use ($isListingKey, $keyword, $searchCacheKey): array {
            $payload = $this->ensureSearchResultSlugs($payload);
            if ($payload === []) {
                return $payload;
            }
            if ($isListingKey) {
                Cache::put('smart_search_mls:' . strtoupper($keyword), $payload, 600);
            } elseif (mb_strlen($keyword) >= 3 && preg_match('/\d/', $keyword)) {
                Cache::put($searchCacheKey, $payload, 180);
            } elseif (mb_strlen($keyword) >= 4) {
                Cache::put($searchCacheKey, $payload, 180);
            }

            return $payload;
        };

        // Short alphabetic keywords are handled by client-side city suggestions.
        if (mb_strlen($keyword) < 5 && ! $isListingKey && ! preg_match('/\d/', $keyword)) {
            return response()->json([]);
        }

        $localQuery = DB::table('re_properties')
            ->select(PropertyFulltextSearch::SEARCH_COLUMNS)
            ->where('moderation_status', 'approved');

        $parsed = $this->parseAddressSearchKeyword($keyword);

        // --- Meilisearch fast path (typo-tolerant, ~10ms). When Meili is DOWN
        // (searchIds => null), do NOT fall through to leading-% LIKE on the full
        // table — that was measuring ~14s and saturating mysqld under sync load. ---
        $meiliTried = false;
        $meiliUnavailable = false;

        if (! $isListingKey) {
            try {
                $meiliTried = true;
                $meiliOpts = [
                    'limit' => max($top * 3, 30),
                    'offset' => $skip,
                    // Exact address search may need commercial hits (390 Bank St).
                    // Map/browse still uses residential_only elsewhere.
                    'residential_only' => ! $parsed,
                    'transaction' => $request->filled('transaction') ? $request->transaction : null,
                    'status' => $request->filled('status') ? $request->status : null,
                ];

                // Address queries: constrain Meili to the street number so
                // "390 Bank" cannot rank "390 Cherry Street" above Bank Street.
                $meiliKeyword = $keyword;
                if ($parsed) {
                    $meiliOpts['street_number'] = $parsed['street_number'];
                    $meiliOpts['limit'] = max($top * 5, 50);
                    $meiliKeyword = trim($parsed['street_number'] . ' ' . ($parsed['street_name'] ?? $parsed['street_part']));
                }

                $ids = app(\Botble\RealEstate\Services\PropertySearchService::class)->searchIds($meiliKeyword, $meiliOpts);

                if ($ids === null) {
                    $meiliUnavailable = true;
        } else {
                    // Meili answered — never fall through to AMP for free-text.
                    if ($ids === []) {
                        if (! $parsed) {
                            return response()->json([]);
                        }
                        // Parsed street address: allow FULLTEXT / AMP address ingest below.
                    } else {
                        $ordered = $this->hydrateSmartSearchRows($ids, max($top * 3, 30), (bool) $parsed);
                        if ($parsed) {
                            $ordered = array_values(array_filter(
                                $ordered,
                                fn (array $row) => $this->addressMatchesParsedSearch(
                                    (string) ($row['UnparsedAddress'] ?? ''),
                                    $parsed
                                )
                            ));
                        }

                        if ($ordered !== [] || ! $parsed) {
                            return response()->json(
                                $rememberSearch(TrebPropertyHelper::groupListingsByBuilding(array_slice($ordered, 0, $top)))
                            );
                        }
                    }
                }
            } catch (\Throwable $e) {
                $meiliUnavailable = true;
                \Log::warning('Meili smartSearch fast-path failed: ' . $e->getMessage());
            }
        }

        // Meili down + general keyword: refuse full-table LIKE (protects MySQL RAM).
        // Listing keys and parsed street searches still use FULLTEXT / cheap MySQL paths.
        if ($meiliUnavailable && ! $isListingKey && ! $parsed) {
            return response()->json([]);
        }

        // Meili up but zero hits for a free-text (non-address) query — skip LIKE thrash.
        if ($meiliTried && ! $meiliUnavailable && ! $isListingKey && ! $parsed) {
            return response()->json([]);
        }

        // Shared post-filters applied to whichever MySQL strategy wins.
        $applySearchFilters = function ($query) use ($request) {
            if ($request->filled('transaction')) {
                $query->where('TransactionType', $request->transaction);
            }
        if ($request->filled('status')) {
            if ($request->status === 'Sold') {
                    $query->whereIn('MlsStatus', [
                    'Sold',
                    'Leased',
                    'Sold Conditional',
                    'Sold Conditional Escape',
                ]);
            } else {
                    $query->where('MlsStatus', $request->status);
                }
            }

            return $query;
        };

        if ($isListingKey) {
            // Exact MLS lookup: never apply residential / transaction / status
            // filters — users typing a ListingKey expect that one row (incl. sold,
            // leased, and commercial) or a real-time AMP ingest.
            $localQuery->where(function ($q) use ($keyword) {
                $q->where('external_id', strtoupper($keyword))
                    ->orWhere('external_id', strtolower($keyword));
            });
        $localResults = $localQuery
            ->orderByDesc('updated_at')
            ->limit($top)
                ->offset(max(0, $skip))
            ->get();
        } elseif ($parsed) {
            // Meili already tried above. If empty/unavailable: FULLTEXT only —
            // never leading-% LIKE (scanned ~179k rows / ~4s).
            $search = app(\Botble\RealEstate\Services\PropertySearchService::class);
            $phrase = trim($parsed['street_number'] . ' ' . ($parsed['street_name'] ?? $parsed['street_part']));
            $meiliIds = $search->searchIds($phrase, [
                'limit' => max($top, 40),
                'offset' => $skip,
                'residential_only' => true,
                'street_number' => $parsed['street_number'],
                'transaction' => $request->filled('transaction') ? $request->transaction : null,
                'status' => $request->filled('status') ? $request->status : null,
            ]);

            $matchParsed = fn ($item) => $this->addressMatchesParsedSearch((string) ($item->name ?? ''), $parsed);

            if (is_array($meiliIds) && $meiliIds !== []) {
                $localResults = $search->hydrateIds($meiliIds, PropertyFulltextSearch::SEARCH_COLUMNS)
                    ->filter($matchParsed)
                    ->values()
                    ->take($top);
            } else {
                $ftQuery = PropertyFulltextSearch::baseQuery(PropertyFulltextSearch::SEARCH_COLUMNS);
                PropertyFulltextSearch::applyParsedAddressFulltext(
                    $ftQuery,
                    $parsed['street_number'],
                    $parsed['street_name'] ?? $parsed['street_part']
                );
                $this->applyResidentialSubTypeScope($ftQuery);
                $applySearchFilters($ftQuery);
                $localResults = $ftQuery
                    ->orderByDesc('updated_at')
                    ->limit(max($top * 5, 40))
                    ->offset(max(0, $skip))
                    ->get()
                    ->filter($matchParsed)
                    ->values()
                    ->take($top);
            }
        } else {
            // Free-text MySQL path should not run when Meili was available (early return).
            // If we land here Meili was down: FULLTEXT only — never %LIKE%.
            $ftQuery = PropertyFulltextSearch::baseQuery(PropertyFulltextSearch::SEARCH_COLUMNS);
            $this->applyLocalOntarioResidentialScope($ftQuery);
            PropertyFulltextSearch::applyKeywordFulltext($ftQuery, $keyword);
            $applySearchFilters($ftQuery);
            $localResults = $ftQuery
                ->orderByDesc('updated_at')
                ->limit($top)
                ->offset(max(0, $skip))
                ->get();
        }

        $mappedLocal = $this->mapLocalSearchCollection($localResults);

        // Exact MLS hit from local DB — return immediately (incl. sold/commercial).
        if ($isListingKey && $mappedLocal !== []) {
            return response()->json($rememberSearch(TrebPropertyHelper::groupListingsByBuilding(array_slice($mappedLocal, 0, $top))));
        }

        if (count($mappedLocal) > 0 && ! $isListingKey) {
            return response()->json($rememberSearch(TrebPropertyHelper::groupListingsByBuilding(array_slice($mappedLocal, 0, $top))));
        }

        // Address miss in local/Meili — pull exact street from AMP (AUTH/AUTH1),
        // ingest, then return. Fixes "390 Bank St Ottawa" when only Cherry "390"s
        // were indexed locally.
        if ($parsed && $mappedLocal === []) {
            $ingestedIds = app(\Botble\RealEstate\Services\LiveTrebPropertyFallbackService::class)
                ->ingestAddressSearchHits($parsed, $top);
            if ($ingestedIds !== []) {
                $ordered = $this->hydrateSmartSearchRows($ingestedIds, $top, true);

                return response()->json($rememberSearch(TrebPropertyHelper::groupListingsByBuilding($ordered)));
            }
        }

        // SMART TREB FALLBACK (Phase 2 / 5 / 11): an exact MLS lookup that is not
        // in the local database is fetched from AMP, persisted, geocoded and
        // indexed, then returned as a normal local row. The map can immediately
        // center / drop a marker on it and every future request is served
        // entirely from local storage.
        if ($isListingKey && $mappedLocal === []) {
            $ingested = app(\Botble\RealEstate\Services\LiveTrebPropertyFallbackService::class)
                ->ingestByListingKey($keyword, true, false);

            if ($ingested !== null) {
                // Allow commercial on exact MLS hit — subtype filter must not hide it.
                $ordered = $this->hydrateSmartSearchRows([(int) $ingested->id], $top, true);
                if ($ordered !== []) {
                    return response()->json($rememberSearch(TrebPropertyHelper::groupListingsByBuilding($ordered)));
                }
            }
        }

        $postal = app(\Botble\RealEstate\Services\LiveTrebPropertyFallbackService::class)->parsePostalCode($keyword);
        if ($postal !== null && $mappedLocal === []) {
            $ingested = app(\Botble\RealEstate\Services\LiveTrebPropertyFallbackService::class)
                ->searchAndIngestByKeyword($keyword);
            if ($ingested !== null) {
                $ordered = $this->hydrateSmartSearchRows([(int) $ingested->id], $top, true);
                if ($ordered !== []) {
                    return response()->json($rememberSearch(TrebPropertyHelper::groupListingsByBuilding($ordered)));
                }
            }
        }

        // Free-text / address: never fan out to live AMP (slow + commercial bleed).
        // Listing-key miss already handled above.
            return response()->json($rememberSearch(TrebPropertyHelper::groupListingsByBuilding(array_slice($mappedLocal, 0, $top))));
        }

    /**
     * Attach real slugs.key then map to smart-search JSON rows.
     *
     * @param  \Illuminate\Support\Collection<int, object>  $rows
     * @return array<int, array<string, mixed>>
     */
    private function mapLocalSearchCollection($rows): array
    {
        $list = collect($rows)->values();
        if ($list->isEmpty()) {
            return [];
        }

        $ids = $list->pluck('id')->filter()->map(fn ($id) => (int) $id)->all();
        $slugMap = [];
        if ($ids !== []) {
            $slugMap = DB::table('slugs')
                ->where('reference_type', Property::class)
                ->whereIn('reference_id', $ids)
                ->pluck('key', 'reference_id')
                ->all();
        }

        return $list->map(function ($item) use ($slugMap) {
            $item->slug_key = $slugMap[$item->id ?? 0] ?? ($item->slug_key ?? null);

            return $this->mapLocalSearchRow($item);
        })->values()->all();
    }

    /**
     * Pull street-matched AMP Property rows (live + historical tokens), ingest
     * missing ListingKeys, and return local property IDs in AMP order.
     *
     * @param  array{street_number:string,street_part?:string,street_name?:string}  $parsed
     * @return int[]
     */
    private function ingestAmpAddressSearchHits(array $parsed, int $limit = 10): array
    {
        $streetNumber = trim((string) ($parsed['street_number'] ?? ''));
        $streetName = trim((string) ($parsed['street_name'] ?? ''));
        if ($streetNumber === '' || $streetName === '') {
            return [];
        }

        $escapedNumber = str_replace("'", "''", $streetNumber);
        $escapedName = str_replace("'", "''", $streetName);
        $filter = "StreetNumber eq '{$escapedNumber}' and StreetName eq '{$escapedName}'";
        $select = 'ListingKey,UnparsedAddress,UnitNumber,StreetNumber,StreetName,StreetSuffix,ListPrice,ClosePrice,'
            . 'BedroomsTotal,BathroomsTotalInteger,PropertySubType,MlsStatus,TransactionType,ListingContractDate,'
            . 'ModificationTimestamp,StandardStatus,PostalCode,City';

            $url = 'https://query.ampre.ca/odata/Property?'
            . '$filter=' . rawurlencode($filter)
            . '&$orderby=' . rawurlencode('ListingContractDate desc')
            . '&$top=' . max(5, min(25, $limit * 2))
            . '&$select=' . rawurlencode($select);

        $unique = [];
        foreach (['live', 'historical'] as $profile) {
            $payload = $this->ampCurl($url, 12, $profile);
            foreach ($payload['value'] ?? [] as $item) {
                $key = strtoupper(trim((string) ($item['ListingKey'] ?? '')));
                if ($key === '') {
                    continue;
                }
                $addr = (string) ($item['UnparsedAddress'] ?? '');
                if (! $this->addressMatchesParsedSearch($addr, $parsed)) {
                    continue;
                }
                // Exact street search may legitimately hit commercial (e.g. 390 Bank St).
                $unique[$key] = $item;
            }
        }

        if ($unique === []) {
            return [];
        }

        $ids = [];
        foreach (array_slice(array_keys($unique), 0, $limit) as $listingKey) {
            $existingId = DB::table('re_properties')->where('external_id', $listingKey)->value('id');
            if ($existingId) {
                $ids[] = (int) $existingId;
                continue;
            }

            $ingested = $this->ingestListingFromAmp($listingKey, true, true);
            if ($ingested) {
                $ids[] = (int) $ingested->id;
            }
        }

        return $ids;
    }

    /**
     * Hydrate Meili IDs → local search rows with real DB slugs (prevents 404)
     * and residential-only filter (hides commercial).
     *
     * @param  int[]  $ids
     * @return array<int, array<string, mixed>>
     */
    private function hydrateSmartSearchRows(array $ids, int $limit = 10, bool $allowCommercial = false): array
    {
        $ids = array_values(array_filter(array_map('intval', $ids)));
        if ($ids === []) {
            return [];
        }

        $excluded = TrebPropertyHelper::excludedCommercialSubTypes();
        // Trailing-space MLS variants
        $excludedExpanded = array_values(array_unique(array_merge(
            $excluded,
            array_map(static fn ($v) => $v . ' ', $excluded)
        )));

        $query = DB::table('re_properties as p')
            ->leftJoin('slugs as s', function ($join) {
                $join->on('s.reference_id', '=', 'p.id')
                    ->where('s.reference_type', '=', Property::class);
            })
            ->select(array_merge(
                array_map(static fn ($c) => 'p.' . $c, PropertyFulltextSearch::SEARCH_COLUMNS),
                ['s.key as slug_key']
            ))
            ->whereIn('p.id', $ids)
            ->where('p.moderation_status', 'approved');

        if (! $allowCommercial) {
            $query->where(function ($q) use ($excludedExpanded) {
                $q->whereNull('p.PropertySubType')
                    ->orWhereNotIn('p.PropertySubType', $excludedExpanded);
            });
        }

        $rowsById = $query->get()->keyBy('id');

        return collect($ids)
            ->map(fn ($id) => $rowsById->get($id))
            ->filter()
            ->take($limit)
            ->map(fn ($item) => $this->mapLocalSearchRow($item))
            ->values()
            ->all();
    }

    private function mapLocalSearchRow(object $item): array
    {
        $record = TrebPropertyHelper::enrichRecordAddress([
            'UnparsedAddress' => $item->name ?? '',
            'name' => $item->name ?? '',
        ]);

        $listingKey = (string) ($item->external_id ?? $item->id);
        // Prefer the real slugs.key — inventing Str::slug(name) caused 404s when
        // the DB slug was missing or differed slightly.
        $slug = trim((string) ($item->slug_key ?? ''));
        if ($slug === '' && ! empty($item->id)) {
            $slug = $this->ensurePropertySlug(
                (int) $item->id,
                (string) ($item->name ?? ''),
                (string) ($item->external_id ?? '')
            );
        }
        if ($slug === '') {
            $slug = Str::slug((string) ($item->name ?? 'property')) . '-' . strtolower($listingKey);
        }

        return [
            'ListingKey' => $listingKey,
            'UnparsedAddress' => TrebPropertyHelper::formatDisplayAddress($record) ?: ($item->name ?? ''),
            'ListPrice' => $item->price ?? 0,
            'BedroomsTotal' => $item->number_bedroom ?? 0,
            'BathroomsTotalInteger' => $item->number_bathroom ?? 0,
            'PropertySubType' => $item->PropertySubType ?? $item->type ?? '',
            'MlsStatus' => $item->MlsStatus ?? $item->status ?? 'Active',
            'TransactionType' => $item->TransactionType ?? 'For Sale',
            'ParkingSpaces' => $item->ParkingSpaces ?? 0,
            'CoveredSpaces' => $item->CoveredSpaces ?? $item->ParkingSpaces ?? 0,
            'ParkingTotal' => ($item->CoveredSpaces ?? 0) + ($item->ParkingSpaces ?? 0),
            'lat' => $item->latitude ?? null,
            'lng' => $item->longitude ?? null,
            'URL' => $slug,
            'MediaURL' => \App\Support\SerikMediaUrl::toPublic($item->image_val ?? null),
            'source' => 'local',
        ];
    }

    /**
     * Guarantee a property has a slugs row so /properties/{slug} does not 404.
     */
    private function ensurePropertySlug(int $propertyId, ?string $name = null, ?string $externalId = null): string
    {
        if ($propertyId <= 0) {
            return '';
        }

        $existing = DB::table('slugs')
            ->where('reference_type', Property::class)
            ->where('reference_id', $propertyId)
            ->value('key');

        if (is_string($existing) && $existing !== '') {
            return $existing;
        }

        $property = Property::query()->find($propertyId);
        if (! $property) {
            return '';
        }

        $listingKey = strtolower((string) ($externalId ?: $property->external_id ?: $property->id));
        $slugKey = Str::slug((string) ($name ?: $property->name ?: 'property')) . '-' . $listingKey;

        SlugHelper::createSlug($property, $slugKey);

        return $slugKey;
    }

    private function trebImageStore(): TrebImageStore
    {
        return app(TrebImageStore::class);
    }

    private function assignTrebCoverImage(Property $property, string $listingKey, ?string $remoteUrl = null): bool
    {
        $store = $this->trebImageStore();

        if ($store->isStoredWebp($property->image_val)) {
            return false;
        }

        $remote = trim((string) ($remoteUrl ?: ''));
        if ($remote === '' && $store->isRemoteUrl($property->image_val)) {
            $remote = (string) $property->image_val;
        }
        if ($remote === '') {
            $remote = (string) ($this->getMediaUrl($listingKey) ?: '');
        }
        if ($remote === '') {
            return false;
        }

        $local = $store->persistFromRemoteUrl($listingKey, $remote, 'cover.webp');
        if ($local) {
            $property->image_val = $local;

            return true;
        }

        if ($store->isRemoteUrl($remote) && empty($property->image_val)) {
            $property->image_val = $remote;
        }

        return false;
    }

    private function assignTrebGallery(Property $property, string $listingKey): bool
    {
        $existing = is_array($property->images) ? $property->images : [];
        if (
            $existing !== []
            && collect($existing)->every(fn ($path) => $this->trebImageStore()->isStoredWebp(is_string($path) ? $path : null))
        ) {
            return false;
        }

        $remoteGallery = TrebPropertyHelper::getPropertyImages(
            $listingKey,
            $property->image_val,
            true
        );

        if ($remoteGallery === []) {
            return false;
        }

        $localGallery = $this->trebImageStore()->persistGallery($listingKey, $remoteGallery);
        if ($localGallery === []) {
            return false;
        }

        $property->images = $localGallery;

        if (empty($property->image_val) || $this->trebImageStore()->isRemoteUrl($property->image_val)) {
            $property->image_val = $localGallery[0];
        }

        return true;
    }

    public function persistTrebImagesForProperty(Property $property, bool $withGallery = false): bool
    {
        $listingKey = strtoupper(trim((string) $property->external_id));
        if ($listingKey === '') {
            return false;
        }

        $changed = $this->assignTrebCoverImage($property, $listingKey);
        if ($withGallery) {
            $changed = $this->assignTrebGallery($property, $listingKey) || $changed;
        }

        if ($changed) {
            $property->saveQuietly();
        }

        return $changed;
    }

    /**
     * @param  array<int, array<string, mixed>>  $rows
     * @return array<int, array<string, mixed>>
     */
    private function ensureSearchResultSlugs(array $rows): array
    {
        foreach ($rows as &$row) {
            if (! is_array($row)) {
                continue;
            }

            $listingKey = strtoupper(trim((string) ($row['ListingKey'] ?? '')));
            if ($listingKey === '') {
                continue;
            }

            $propertyId = (int) DB::table('re_properties')
                ->where('external_id', $listingKey)
                ->value('id');

            if ($propertyId <= 0) {
                continue;
            }

            $slug = $this->ensurePropertySlug(
                $propertyId,
                (string) ($row['UnparsedAddress'] ?? $row['building_address'] ?? ''),
                $listingKey
            );

            if ($slug !== '') {
                $row['URL'] = $slug;
            }

            if (! empty($row['units']) && is_array($row['units'])) {
                foreach ($row['units'] as &$unit) {
                    if (is_array($unit) && $slug !== '') {
                        $unit['URL'] = $slug;
                    }
                }
                unset($unit);
            }
        }
        unset($row);

        return $rows;
    }

    private function mergeSearchResults(array $local, array $remote): array
    {
        $seen = [];
        $merged = [];

        foreach (array_merge($local, $remote) as $row) {
            $key = strtoupper((string) ($row['ListingKey'] ?? ''));
            if ($key === '' || isset($seen[$key])) {
                continue;
            }
            $seen[$key] = true;
            $merged[] = $row;
        }

        return $merged;
    }



    private function buildFilterQuery(string $keyword, array $filters = []): string
    {
        $keyword = trim($keyword);
        $conditions = [];

        /*
        |--------------------------------------------------------------------------
        | 1️⃣ ListingKey Search
        |--------------------------------------------------------------------------
        */
        $isListingSearch = false;

        if (preg_match('/^[a-z]\d+$/i', $keyword)) {
            $keyword = strtoupper($keyword);
            $conditions[] = "ListingKey eq '{$keyword}'";
            $isListingSearch = true;
        }
        /*
        |--------------------------------------------------------------------------
        | 2️⃣ Postal Code Search
        |--------------------------------------------------------------------------
        */ elseif (preg_match('/^[A-Z]\d[A-Z]\s?\d[A-Z]\d$/i', $keyword)) {
            $conditions[] = "PostalCode eq '{$keyword}'";
        }

        /*
        |--------------------------------------------------------------------------
        | 3️⃣ City Search
        |--------------------------------------------------------------------------
        */ elseif (preg_match('/^[a-zA-Z\s]+$/', $keyword)) {

            if (!empty($keyword)) {

                // cities table only (tiny). CI collation — no LOWER() needed.
                $cityExists = DB::table('cities')
                    ->where('name', 'like', $keyword . '%')
                    ->exists();

                if ($cityExists) {
                    $keywordCity = ucwords(strtolower($keyword));
                    $conditions[] = "contains(City,'{$keywordCity}')";
                }
            }
        }

        $parsedAddress = $this->parseAddressSearchKeyword($keyword);
        if ($parsedAddress) {
            $streetNumber = str_replace("'", "''", $parsedAddress['street_number']);
            $streetPart = str_replace("'", "''", $parsedAddress['street_part']);
            $conditions[] = "StreetNumber eq '{$streetNumber}'";
            $conditions[] = "contains(UnparsedAddress,'{$streetPart}')";
        }

        /*
        |--------------------------------------------------------------------------
        | 4️⃣ Address Search (Default Fallback)
        |--------------------------------------------------------------------------
        */
        if (!empty($keyword) && empty($conditions)) {

            $cleanKeyword = str_replace("'", "''", $keyword);

            $variants = array_unique([
                $cleanKeyword,
                strtolower($cleanKeyword),
                strtoupper($cleanKeyword),
                ucwords(strtolower($cleanKeyword)),
            ]);

            $addressConditions = [];

            foreach ($variants as $word) {
                $addressConditions[] = "contains(UnparsedAddress,'{$word}')";
            }

            $conditions[] = '(' . implode(' or ', $addressConditions) . ')';
        }

        /*
        |--------------------------------------------------------------------------
        | 5️⃣ Transaction Filter
        |--------------------------------------------------------------------------
        */
        if (request()->filled('transaction')) {
            $transaction = str_replace("'", "''", request('transaction'));
            $conditions[] = "TransactionType eq '{$transaction}'";
        }

        /*
        |--------------------------------------------------------------------------
        | 6️⃣ Status Filter
        |--------------------------------------------------------------------------
        */
        if ($isListingSearch) {
            // do nothing (no status filter)
        } elseif (request()->filled('status')) {
            $status = str_replace("'", "''", request('status'));
            if ($status === 'Sold') {
                $conditions[] = "(MlsStatus eq 'Sold' or MlsStatus eq 'Leased' or MlsStatus eq 'Sold Conditional' or MlsStatus eq 'Sold Conditional Escape')";
            } else {
                $conditions[] = "MlsStatus eq '{$status}'";
            }
        }

        /*
        |--------------------------------------------------------------------------
        | 7️⃣ Ontario residential-only (TRREB / PropTX)
        |--------------------------------------------------------------------------
        */
        TrebPropertyHelper::appendOntarioResidentialAmpConditions($conditions);

        /*
        |--------------------------------------------------------------------------
        | Final Combine
        |--------------------------------------------------------------------------
        */
        if (empty($conditions)) {
            return '';
        }

        $finalFilter = implode(' and ', $conditions);

        return rawurlencode($finalFilter);
    }




    private function ampCurl($url, int $timeout = 6, string $tokenProfile = 'live')
    {
        // AMPRE: $expand=Media rejects $top > 100 (HTTP 400). Clamp before request
        // so live/cron/manual Property queries never send an invalid combo.
        $url = TrebPropertyHelper::clampAmpODataTopForMediaExpand($url);

        // live → TRREB_AUTH first (new/active). historical → TRREB_AUTH1 first (archive).
        $tokens = TrebPropertyHelper::ampTokens($tokenProfile);

        foreach ($tokens as $tokenIndex => $token) {

            $ch = curl_init();

            curl_setopt_array($ch, [
                CURLOPT_URL => $url,
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_ENCODING => '',
                CURLOPT_MAXREDIRS => 10,
                CURLOPT_TIMEOUT => $timeout,
                CURLOPT_CONNECTTIMEOUT => min(10, $timeout),
                CURLOPT_FOLLOWLOCATION => true,
                CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
                CURLOPT_CUSTOMREQUEST => 'GET',
                CURLOPT_TCP_KEEPALIVE => 1,
                CURLOPT_TCP_KEEPIDLE => 2,
                CURLOPT_TCP_KEEPINTVL => 2,
                CURLOPT_SSL_VERIFYPEER => false,
                CURLOPT_SSL_VERIFYHOST => false,
                CURLOPT_HTTPHEADER => $this->ampHeaders($token),
            ]);

            $response = curl_exec($ch);
            $httpCode = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);

            if (curl_errno($ch)) {
                curl_close($ch);
                continue;
            }

            curl_close($ch);

            $payload = json_decode($response, true);

            if (isset($payload['value']) && is_array($payload['value'])) {
                if ($payload['value'] === [] && $tokenIndex < (count($tokens) - 1)) {
                    continue; // AUTH empty archive window → try AUTH1
                }

                return $payload;
            }

            if (isset($payload['error']['message'])) {
                \Log::warning('AMP API error: ' . $payload['error']['message'], ['http' => $httpCode, 'url' => $url]);
            }
        }

        return null;
    }

    private function applyMapCityFilter($query, string $city, array $cityMap): void
    {
        if ($city === '' || strtolower($city) === 'ontario') {
            return;
        }

        $search = app(\Botble\RealEstate\Services\PropertySearchService::class);
        $cityNames = [];

            if (strtolower($city) === 'kwc' && isset($cityMap['KWC'])) {
            $cityNames = (array) $cityMap['KWC'];
            } elseif (isset($cityMap[$city])) {
            $cityNames = [(string) $cityMap[$city]];
            } else {
            $cityNames = [trim($city)];
        }

        $ids = [];
        $meiliOk = false;
        foreach ($cityNames as $name) {
            $hit = $search->searchCityIds((string) $name, 8000);
            if ($hit === null) {
                continue;
            }
            $meiliOk = true;
            $ids = array_merge($ids, $hit);
        }

        if (! $meiliOk) {
            // Meili down: skip city filter (show all pins in bounds) — never blank the map.
            return;
        }

        $ids = array_values(array_unique(array_map('intval', $ids)));
        if ($ids === []) {
            // City facet had no Meili hits — soft skip, same as above.
            return;
        }

        $query->whereIn('id', $ids);
    }


    private function ampHeaders($token)
    {
        return [
            'Authorization: Bearer ' . $token,
            'Accept: application/json',
            'OData-Version: 4.0',
            'OData-MaxVersion: 4.0',
            'User-Agent: Mozilla/5.0',
        ];
    }


    public function getMediaUrl($listingKey)
    {
        if (!$listingKey) {
            return null;
        }

        $url = "https://query.ampre.ca/odata/Media?"
            . "%24filter=ResourceRecordKey%20eq%20%27{$listingKey}%27"
            . "&%24top=10"
            . "&%24select=MediaURL,ImageSizeDescription";

        $response = $this->ampCurl($url);
        $mediaData = $response;

        if (!empty($mediaData['value'])) {
            foreach ($mediaData['value'] as $media) {

                if (
                    !empty($media['MediaURL']) &&
                    ($media['ImageSizeDescription'] ?? '') === 'LargestNoWatermark'
                ) {
                    return $media['MediaURL'];
                }
            }
        }

        return null; // no valid image found
    }



    public function getPropertyImage($listingKey)
    {
        try {
            $property = Property::query()
                ->where('external_id', $listingKey)
                ->orWhere('external_id', strtoupper($listingKey))
                ->first();

            $images = TrebPropertyHelper::getPropertyImages(
                $listingKey,
                $property?->image_val
            );

            $first = $images[0] ?? null;

            return response()->json([
                'media' => $first,
                'images' => $images,
            ]);
        } catch (\Throwable $e) {
            return response()->json([
                'media' => null,
                'images' => [],
            ]);
        }
    }










    /**
     * Get property by slug
     *
     * @group Real Estate
     * @urlParam slug string required The slug of the property.
     */
    public function findBySlug(string $slug)
    {
        $slug = SlugHelper::getSlug($slug, SlugHelper::getPrefix(Property::class));

        if (!$slug) {
            return $this
                ->httpResponse()
                ->setError()
                ->setCode(404)
                ->setMessage('Property not found');
        }

        $property = Property::query()
            ->where('id', $slug->reference_id)
            ->where(RealEstateHelper::getPropertyDisplayQueryConditions())
            ->with(RealEstateHelper::getPropertyRelationsQuery())
            ->first();

        if (!$property) {
            return $this
                ->httpResponse()
                ->setError()
                ->setCode(404)
                ->setMessage('Property not found');
        }

        return $this
            ->httpResponse()
            ->setData(new PropertyResource($property))
            ->toApiResponse();
    }

    /**
     * Get property by ID
     *
     * @group Real Estate
     * @urlParam id integer required The ID of the property.
     */
    public function show(int $id)
    {
        $property = Property::query()
            ->where('id', $id)
            ->where(RealEstateHelper::getPropertyDisplayQueryConditions())
            ->with(RealEstateHelper::getPropertyRelationsQuery())
            ->first();

        if (!$property) {
            return $this
                ->httpResponse()
                ->setError()
                ->setCode(404)
                ->setMessage('Property not found');
        }

        return $this
            ->httpResponse()
            ->setData(new PropertyResource($property))
            ->toApiResponse();
    }












    private function mapDateDaysMap(): array
    {
        return [
            'last_1_day' => 1,
            'last_3_day' => 3,
            'last_7_day' => 7,
            'last_30_day' => 30,
            'last_90_day' => 90,
            'last_180_day' => 180,
            'last_360_day' => 360,
        ];
    }

    private function mapDateMoreThanMap(): array
    {
        return [
            'more_than_15_days' => 15,
            'more_than_30_days' => 30,
            'more_than_60_days' => 60,
            'more_than_90_days' => 90,
        ];
    }

    private function parseAmpDateValue(?string $value): ?Carbon
    {
        if ($value === null || $value === '') {
            return null;
        }

        try {
            $parsed = Carbon::parse($value);
        } catch (\Throwable) {
            return null;
        }

        $year = (int) $parsed->format('Y');

        if ($year < 2000 || $year > ((int) date('Y') + 1)) {
            return null;
        }

        return $parsed;
    }

    private function parseAmpSoldDateValue(?string $value): ?Carbon
    {
        $parsed = $this->parseAmpDateValue($value);

        if ($parsed === null || $parsed->isFuture()) {
            return null;
        }

        return $parsed;
    }
    private function syncAmpListingDatesForMapFilter(int $days, int $maxPages = 3): void
    {
        if (! TrebPropertyHelper::canFetchRemoteAmp() || $days <= 0) {
            return;
        }

        $lockKey = 'map_amp_date_sync_' . $days;

        if (! Cache::add($lockKey, 1, 1800)) {
            return;
        }

        try {

        $cutoffDateTime = now()->subDays($days)->format('Y-m-d') . 'T00:00:00Z';
        $skip = 0;
        $top = 200;

        for ($page = 0; $page < $maxPages; $page++) {
            $url = 'https://query.ampre.ca/odata/Property?'
                . '$filter=' . rawurlencode("ModificationTimestamp ge {$cutoffDateTime}")
                . '&$select=ListingKey,ListingContractDate,ModificationTimestamp,PriceChangeTimestamp,OriginalEntryTimestamp,CloseDate,MlsStatus,ListPrice,CoveredSpaces,ParkingSpaces,BedroomsAboveGrade,BedroomsBelowGrade,ArchitecturalStyle'
                . '&$top=' . $top
                . '&$skip=' . $skip;

            $payload = $this->ampCurl($url);

            if (! is_array($payload) || empty($payload['value'])) {
                break;
            }

            $batch = [];

            foreach ($payload['value'] as $item) {
                $listingKey = strtoupper((string) ($item['ListingKey'] ?? ''));

                if ($listingKey === '') {
                    continue;
                }

                $contractDate = $this->parseAmpDateValue($item['ListingContractDate'] ?? $item['OriginalEntryTimestamp'] ?? null);
                $modifiedDate = $this->parseAmpDateValue($item['ModificationTimestamp'] ?? $item['PriceChangeTimestamp'] ?? null);
                $closeDate = $this->parseAmpDateValue($item['CloseDate'] ?? null);

                $updates = array_filter([
                    'listing_contract_date' => $contractDate,
                    'listing_modified_at' => $modifiedDate,
                    'close_date' => $closeDate,
                    'MlsStatus' => $item['MlsStatus'] ?? null,
                    'price' => isset($item['ListPrice']) ? (float) $item['ListPrice'] : null,
                    'CoveredSpaces' => isset($item['CoveredSpaces']) ? (int) $item['CoveredSpaces'] : null,
                    'ParkingSpaces' => isset($item['ParkingSpaces']) ? (int) $item['ParkingSpaces'] : null,
                    'number_bedroom' => $this->extractMainBedrooms($item) ?: null,
                    'BedroomsBelowGrade' => $this->extractBelowGradeBedrooms($item) ?: null,
                    'number_floor' => $this->extractNumberFloor($item) ?: null,
                ], fn ($value) => $value !== null);

                if ($updates === []) {
                    continue;
                }

                if ($modifiedDate !== null) {
                    $updates['updated_at'] = $modifiedDate;
                }

                $batch[$listingKey] = $updates;
            }

            if ($batch !== []) {
                $properties = Property::query()
                    ->whereIn(DB::raw('UPPER(external_id)'), array_keys($batch))
                    ->get(['id', 'external_id']);

                foreach ($properties as $property) {
                    $key = strtoupper((string) $property->external_id);

                    if (! isset($batch[$key])) {
                        continue;
                    }

                    Property::query()->where('id', $property->id)->update($batch[$key]);
                }
            }

            if (count($payload['value']) < $top) {
                break;
            }

            $skip += $top;
        }

        } finally {
            // Release the run-guard so sequential calls in the same cron run
            // (addpropertiescron + serik:sync-dates) and manual reruns are not
            // silently skipped for the 30-minute lock TTL.
            Cache::forget($lockKey);
        }
    }

    public function syncAmpListingDates(Request $request)
    {
        $days = max(1, min(360, (int) $request->input('days', 45)));
        $this->syncAmpListingDatesForMapFilter($days, 15);

        return response()->json([
            'status' => 'success',
            'days' => $days,
        ]);
    }

    /**
     * Import / refresh recently sold & leased listings from AMP (global feed).
     */
    public function syncRecentSoldListings(Request $request)
    {
        $days = max(1, min(120, (int) $request->input('days', 60)));
        $result = $this->syncRecentSoldListingsFromAmp($days);

        return response()->json(array_merge(['status' => 'success', 'days' => $days], $result));
    }

    private function syncRecentSoldListingsFromAmp(int $days, int $maxPages = 10): array
    {
        if (! TrebPropertyHelper::canFetchRemoteAmp()) {
            return ['imported' => 0, 'updated' => 0, 'geocoded' => 0, 'skipped' => 'amp unavailable'];
        }

        $cutoffDate = now()->subDays($days)->format('Y-m-d');
        $maxYear = (int) date('Y') + 1;
        $skip = 0;
        $top = 200;
        $imported = 0;
        $updated = 0;
        $needsGeocodeIds = [];

        $statusFilter = "(MlsStatus eq 'Sold' or MlsStatus eq 'Sold Conditional' or MlsStatus eq 'Sold Conditional Escape' or MlsStatus eq 'Leased' or MlsStatus eq 'Leased Conditional' or MlsStatus eq 'Terminated' or MlsStatus eq 'Expired' or MlsStatus eq 'Suspended')";

        $residentialParts = [];
        foreach (TrebPropertyHelper::excludedCommercialSubTypes() as $subtype) {
            $escaped = str_replace("'", "''", $subtype);
            $residentialParts[] = "PropertySubType ne '{$escaped}'";
        }
        $residential = $residentialParts !== []
            ? ' and ' . implode(' and ', $residentialParts)
            : '';

        // CloseDate / PurchaseContractDate are not filterable on AMP Property feed.
        $filterSets = [
            "ModificationTimestamp ge {$cutoffDate}T00:00:00Z and {$statusFilter}{$residential}",
            "ModificationTimestamp ge {$cutoffDate}T00:00:00Z and StandardStatus eq 'Active'{$residential}",
            "OriginalEntryTimestamp ge {$cutoffDate}T00:00:00Z{$residential}",
        ];

        foreach ($filterSets as $filterBody) {
            $skip = 0;

            for ($page = 0; $page < $maxPages; $page++) {
                $url = 'https://query.ampre.ca/odata/Property?'
                    . '$filter=' . rawurlencode($filterBody)
                    . '&$select=ListingKey,UnparsedAddress,PropertySubType,ListPrice,ClosePrice,PostalCode,'
                    . 'OriginalEntryTimestamp,ModificationTimestamp,ListingContractDate,CloseDate,PurchaseContractDate,'
                    . 'TransactionType,MlsStatus,StandardStatus,BedroomsTotal,BathroomsTotalInteger,LivingAreaRange,ListOfficeName'
                    . '&$top=' . $top
                    . '&$skip=' . $skip;

                // AUTH1 first — sold/archive inventory lives on historical token.
                $payload = $this->ampCurl($url, 45, 'historical');

                if (! is_array($payload) || empty($payload['value'])) {
                    break;
                }

                foreach ($payload['value'] as $item) {
                    $listingKey = strtoupper((string) ($item['ListingKey'] ?? ''));

                    if ($listingKey === '') {
                        continue;
                    }

                    $subtype = trim((string) ($item['PropertySubType'] ?? ''));
                    if ($subtype !== '' && in_array(rtrim($subtype), TrebPropertyHelper::excludedCommercialSubTypes(), true)) {
                        continue;
                    }

                    $contractDate = $this->parseAmpDateValue($item['ListingContractDate'] ?? $item['OriginalEntryTimestamp'] ?? null);
                    $modifiedDate = $this->parseAmpDateValue($item['ModificationTimestamp'] ?? null);
                    $purchaseDate = $this->parseAmpSoldDateValue($item['PurchaseContractDate'] ?? null);
                    $closeDate = $this->parseAmpSoldDateValue($item['CloseDate'] ?? null);
                    $soldDate = $modifiedDate ?? $purchaseDate ?? $closeDate;

                    $property = Property::firstOrNew(['external_id' => $listingKey]);
                    $isNew = ! $property->exists;

                    if ($isNew) {
                        $property->unique_id = $this->generateUniqueId();
                        $property->author_id = 1;
                        $property->author_type = 'Botble\ACL\Models\User';
                        $property->latitude = 0;
                        $property->longitude = 0;
                    }

                    $listPrice = (float) ($item['ListPrice'] ?? 0);
                    $closePrice = (float) ($item['ClosePrice'] ?? 0);

                    $property->fill([
                        'name' => $item['UnparsedAddress'] ?? $property->name,
                        'location' => $item['UnparsedAddress'] ?? $property->location,
                        'PropertySubType' => $item['PropertySubType'] ?? $property->PropertySubType ?? 'sell',
                        'price' => $listPrice > 0 ? $listPrice : ($property->price ?? 0),
                        'ClosePrice' => $closePrice > 0 ? $closePrice : ($property->ClosePrice ?? 0),
                        'zip_code' => $item['PostalCode'] ?? $property->zip_code,
                        'MlsStatus' => $item['MlsStatus'] ?? $property->MlsStatus,
                        'TransactionType' => $item['TransactionType'] ?? $property->TransactionType ?? '',
                        'broker' => $item['ListOfficeName'] ?? $property->broker,
                        'number_bedroom' => $this->extractMainBedrooms($item),
                        'BedroomsBelowGrade' => $this->extractBelowGradeBedrooms($item),
                        'number_bathroom' => (int) ($item['BathroomsTotalInteger'] ?? $property->number_bathroom ?? 0),
                        'square' => is_array($item['LivingAreaRange'] ?? null)
                            ? $property->square
                            : $this->normalizeSquare($item['LivingAreaRange'] ?? $property->square),
                        'status' => 'draft',
                        'moderation_status' => 'approved',
                        'listing_contract_date' => $contractDate ?? $property->listing_contract_date,
                        'listing_modified_at' => $modifiedDate ?? $property->listing_modified_at,
                        'close_date' => $soldDate ?? $property->close_date,
                        'purchase_contract_date' => $soldDate ?? $property->purchase_contract_date,
                        'updated_at' => $modifiedDate ?? now(),
                    ]);

                    $property->save();

                    if ((float) ($property->latitude ?? 0) === 0.0) {
                        $needsGeocodeIds[] = (int) $property->id;
                    }

                    if ($isNew) {
                        $imported++;
                    } else {
                        $updated++;
                    }
                }

                if (count($payload['value']) < $top) {
                    break;
                }

                $skip += $top;
            }
        }

        $geocoded = 0;

        if ($needsGeocodeIds !== []) {
            foreach (array_chunk(array_values(array_unique($needsGeocodeIds)), 200) as $chunk) {
                $geoResult = $this->geocodePropertyBatch(count($chunk), $chunk);
                $geocoded += $geoResult['geocoded'];
            }
        }

        return compact('imported', 'updated', 'geocoded');
    }

    /**
     * Historical AMP import — one paginated batch for a calendar year.
     * Used by serik:import-historical (30-year bootstrap, resumable).
     *
     * @return array{imported:int,updated:int,geocoded:int,fetched:int,has_more:bool,next_skip:int,filter:string,year:int}
     */
    public function importHistoricalAmpPage(int $year, int $skip, string $filterType = 'modification', int $top = 100, bool $geocodeInline = false, bool $skipExisting = false, bool $dryRun = false): array
    {
        $empty = [
            'imported' => 0,
            'updated' => 0,
            'skipped' => 0,
            'geocoded' => 0,
            'fetched' => 0,
            'has_more' => false,
            'next_skip' => $skip,
            'filter' => $filterType,
            'year' => $year,
        ];

        if (! TrebPropertyHelper::canFetchRemoteAmp()) {
            return array_merge($empty, ['skipped' => 'amp unavailable']);
        }

        $year = max(1990, min((int) date('Y'), $year));
        $skip = max(0, $skip);
        $top = max(25, min(200, $top));

        $start = sprintf('%04d-01-01', $year);
        $nextYearStart = sprintf('%04d-01-01', $year + 1);

        $residentialParts = [];
        foreach (TrebPropertyHelper::excludedCommercialSubTypes() as $subtype) {
            $escaped = str_replace("'", "''", $subtype);
            $residentialParts[] = "PropertySubType ne '{$escaped}'";
        }
        $residential = $residentialParts !== []
            ? implode(' and ', $residentialParts)
            : "PropertySubType ne 'Industrial'";

        $soldStatus = "(MlsStatus eq 'Sold' or MlsStatus eq 'Sold Conditional' or MlsStatus eq 'Sold Conditional Escape' or MlsStatus eq 'Leased' or MlsStatus eq 'Leased Conditional' or MlsStatus eq 'Terminated' or MlsStatus eq 'Expired' or MlsStatus eq 'Suspended')";

        // AMP OData does NOT allow CloseDate / ListingContractDate / PurchaseContractDate in $filter.
        $filterMap = [
            'modification' => "ModificationTimestamp ge {$start}T00:00:00Z and ModificationTimestamp lt {$nextYearStart}T00:00:00Z and {$residential}",
            'original_entry' => "OriginalEntryTimestamp ge {$start}T00:00:00Z and OriginalEntryTimestamp lt {$nextYearStart}T00:00:00Z and {$residential}",
            'sold_mls' => "ModificationTimestamp ge {$start}T00:00:00Z and ModificationTimestamp lt {$nextYearStart}T00:00:00Z and {$soldStatus} and {$residential}",
            'active' => "ModificationTimestamp ge {$start}T00:00:00Z and ModificationTimestamp lt {$nextYearStart}T00:00:00Z and StandardStatus eq 'Active' and {$residential}",
            // Legacy aliases (old checkpoints)
            'sold_close' => "ModificationTimestamp ge {$start}T00:00:00Z and ModificationTimestamp lt {$nextYearStart}T00:00:00Z and {$soldStatus} and {$residential}",
            'sold_contract' => "ModificationTimestamp ge {$start}T00:00:00Z and ModificationTimestamp lt {$nextYearStart}T00:00:00Z and {$soldStatus} and {$residential}",
            'listing_contract' => "OriginalEntryTimestamp ge {$start}T00:00:00Z and OriginalEntryTimestamp lt {$nextYearStart}T00:00:00Z and {$residential}",
        ];

        if (! isset($filterMap[$filterType])) {
            return array_merge($empty, ['skipped' => 'invalid filter']);
        }

        $select = 'ListingKey,UnparsedAddress,PropertySubType,PublicRemarks,PrivateRemarks,'
            . 'BedroomsTotal,BedroomsAboveGrade,BedroomsBelowGrade,BathroomsTotalInteger,KitchensTotal,LivingAreaRange,'
            . 'StandardStatus,ExpirationDate,ListPrice,PostalCode,OriginalEntryTimestamp,ModificationTimestamp,'
            . 'PriceChangeTimestamp,TransactionType,MlsStatus,ListOfficeName,'
            . 'ListingContractDate,CloseDate,PurchaseContractDate,Basement,ParkingSpaces,CoveredSpaces,ClosePrice,ArchitecturalStyle';

        // A stable, unique $orderby is REQUIRED for correct $skip pagination.
        // Without it, AMP/OData default ordering can shift between pages, which
        // silently skips or duplicates listings (e.g. new listing W13550014 was
        // never imported). ListingKey is unique so paging is fully deterministic.
        $url = 'https://query.ampre.ca/odata/Property?'
            . '$filter=' . rawurlencode($filterMap[$filterType])
            . '&$orderby=' . rawurlencode('ListingKey asc')
            . '&$select=' . rawurlencode($select)
            . '&$top=' . $top
            . '&$skip=' . $skip;

        $ampResponse = TrebPropertyHelper::ampRequest(
            $url,
            60,
            4,
            'importHistoricalAmpPage',
            null,
            'historical' // TRREB_AUTH1 first — archive years (2000+) AUTH cannot see
        );

        if (! $ampResponse['ok']) {
            return array_merge($empty, [
                'amp_error' => $ampResponse['error'] ?? 'no response',
                'amp_status' => $ampResponse['status'],
                'amp_url' => $ampResponse['url'],
            ]);
        }

        $payload = $ampResponse['data'];

        if (! is_array($payload)) {
            return array_merge($empty, ['amp_error' => 'invalid AMP payload']);
        }

        if (! empty($payload['error']['message'])) {
            Log::warning('AMP historical import OData error: ' . $payload['error']['message'], [
                'year' => $year,
                'filter' => $filterType,
                'url' => $url,
                'status' => $ampResponse['status'],
            ]);

            return array_merge($empty, [
                'amp_error' => $payload['error']['message'],
                'amp_status' => $ampResponse['status'],
            ]);
        }

        if (empty($payload['value'])) {
            return $empty;
        }

        $imported = 0;
        $updated = 0;
        $skipped = 0;
        $needsGeocodeIds = [];

        $listingKeys = [];

        foreach ($payload['value'] as $item) {
            $key = strtoupper((string) ($item['ListingKey'] ?? ''));

            if ($key !== '') {
                $listingKeys[] = $key;
            }
        }

        $prefetchStart = microtime(true);
        $existingByExternalId = $listingKeys === []
            ? collect()
            : Property::query()
                ->whereIn('external_id', array_values(array_unique($listingKeys)))
                ->get()
                ->keyBy(fn (Property $property): string => strtoupper((string) $property->external_id));
        $this->logPersistQueryTiming('batch_prefetch', null, microtime(true) - $prefetchStart, [
            'keys' => count($listingKeys),
            'found' => $existingByExternalId->count(),
        ]);

        // Bulk historical imports must not fan out a Meilisearch HTTP call
        // per row — that turns a 200-row page into minutes. Index afterward
        // via serik:search-index. Also disable PropertyHistoryRecorder during
        // bulk pages so every save is not "UPDATE + history INSERT".
        Property::withoutSyncingToSearch(function () use (
            $payload,
            $existingByExternalId,
            $dryRun,
            $skipExisting,
            &$imported,
            &$updated,
            &$skipped,
            &$needsGeocodeIds
        ) {
            $prevHistory = \Botble\RealEstate\Supports\PropertyHistoryRecorder::$enabled;
            \Botble\RealEstate\Supports\PropertyHistoryRecorder::$enabled = false;
            try {
        foreach ($payload['value'] as $item) {
            $listingKey = strtoupper((string) ($item['ListingKey'] ?? ''));
            $existing = $listingKey !== '' ? $existingByExternalId->get($listingKey) : null;

                if ($dryRun) {
                    if ($existing) {
                        $updated++;
                    } else {
                        $imported++;
                    }

                    continue;
                }

                if ($skipExisting && $existing !== null) {
                    $skipped++;

                    continue;
                }

                try {
                    $result = $this->persistHistoricalAmpPropertyRow($item, $existing);
                } catch (\Throwable $e) {
                    $skipped++;
                    Log::warning('persistHistoricalAmpPropertyRow failed: '.$e->getMessage(), [
                        'listing' => $listingKey,
                        'year' => $year,
                        'filter' => $filterType,
                    ]);

                    continue;
                }

            if ($result['is_new']) {
                $imported++;
            } elseif ($result['updated']) {
                $updated++;
                } else {
                    $skipped++;
            }

            if ($result['needs_geocode'] && $result['property_id']) {
                $needsGeocodeIds[] = (int) $result['property_id'];
            }
        }
            } finally {
                \Botble\RealEstate\Supports\PropertyHistoryRecorder::$enabled = $prevHistory;
            }
        });

        $geocoded = 0;

        if ($geocodeInline && $needsGeocodeIds !== []) {
            foreach (array_chunk(array_values(array_unique($needsGeocodeIds)), 50) as $chunk) {
                $geoResult = $this->geocodePropertyBatch(count($chunk), $chunk);
                $geocoded += (int) ($geoResult['geocoded'] ?? 0);
            }
        }

        $fetched = count($payload['value']);
        $hasMore = $fetched >= $top;

        return [
            'imported' => $imported,
            'updated' => $updated,
            'skipped' => $skipped,
            'geocoded' => $geocoded,
            'fetched' => $fetched,
            'has_more' => $hasMore,
            'next_skip' => $hasMore ? $skip + $top : 0,
            'filter' => $filterType,
            'year' => $year,
        ];
    }

    /**
     * @return array{is_new:bool,updated:bool,needs_geocode:bool,property_id:?int}
     */
    private function persistHistoricalAmpPropertyRow(array $item, ?Property $existing = null): array
    {
        $listingKey = strtoupper((string) ($item['ListingKey'] ?? ''));

        if ($listingKey === '') {
            return ['is_new' => false, 'updated' => false, 'needs_geocode' => false, 'property_id' => null];
        }

        $contractDate = $this->parseAmpDateValue($item['ListingContractDate'] ?? $item['OriginalEntryTimestamp'] ?? null);
        $modifiedDate = $this->parseAmpDateValue($item['ModificationTimestamp'] ?? $item['PriceChangeTimestamp'] ?? null);
        $purchaseDate = $this->parseAmpDateValue($item['PurchaseContractDate'] ?? null);
        $closeDate = $this->parseAmpDateValue($item['CloseDate'] ?? null);
        $soldDate = $purchaseDate ?? $closeDate ?? $modifiedDate;

        $lookupStart = microtime(true);

        if ($existing === null) {
            $existing = Property::query()
                ->where('external_id', $listingKey)
                ->first();
        }

        $this->logPersistQueryTiming('lookup', $listingKey, microtime(true) - $lookupStart);

        $isNew = $existing === null;
        $property = $existing ?? new Property(['external_id' => $listingKey]);

        if ($isNew) {
            $property->unique_id = $this->generateUniqueId();
            $property->author_id = 1;
            $property->author_type = 'Botble\ACL\Models\User';
            $property->latitude = 0;
            $property->longitude = 0;
        }

        $property->fill([
            'name' => $item['UnparsedAddress'] ?? $property->name,
            'PropertySubType' => $item['PropertySubType'] ?? $property->PropertySubType ?? 'sell',
            'description' => $item['PublicRemarks'] ?? $property->description ?? '',
            'content' => trim(($item['PublicRemarks'] ?? '') . '<br>' . ($item['PrivateRemarks'] ?? '')) ?: $property->content,
            'location' => $item['UnparsedAddress'] ?? $property->location,
            'number_bedroom' => $this->extractMainBedrooms($item) ?: ($property->number_bedroom ?? 0),
            'number_bathroom' => (int) ($item['BathroomsTotalInteger'] ?? $property->number_bathroom ?? 0),
            'number_floor' => (int) ($this->extractNumberFloor($item) ?: ($property->number_floor ?? 0)),
            'BedroomsBelowGrade' => (int) ($this->extractBelowGradeBedrooms($item) ?: ($property->BedroomsBelowGrade ?? 0)),
            'broker' => $item['ListOfficeName'] ?? $property->broker,
            'square' => is_array($item['LivingAreaRange'] ?? null)
                ? $property->square
                : $this->normalizeSquare($item['LivingAreaRange'] ?? $property->square),
            'price' => (float) ($item['ListPrice'] ?? $property->price ?? 0),
            'currency_id' => 1,
            'is_featured' => $property->is_featured ?? 0,
            'featured_priority' => $property->featured_priority ?? 0,
            'status' => ($item['StandardStatus'] ?? '') === 'Active' ? 'selling' : ($property->status ?: 'draft'),
            'moderation_status' => 'approved',
            'expire_date' => $item['ExpirationDate']
                ?? $property->expire_date
                ?? now()->addYear()->toDateTimeString(),
            'auto_renew' => 1,
            'never_expired' => 1,
            // AMP sometimes omits TransactionType — NOT NULL column, default For Sale.
            'TransactionType' => trim((string) ($item['TransactionType'] ?? $property->TransactionType ?? ''))
                ?: 'For Sale',
            'MlsStatus' => $item['MlsStatus'] ?? $property->MlsStatus ?? 'Active',
            'latitude' => $property->latitude ?: 0,
            'longitude' => $property->longitude ?: 0,
            'zip_code' => $item['PostalCode'] ?? $property->zip_code,
            'views' => $property->views ?? 0,
            'ParkingSpaces' => (int) ($item['ParkingSpaces'] ?? $property->ParkingSpaces ?? 0),
            'CoveredSpaces' => (int) ($item['CoveredSpaces'] ?? $property->CoveredSpaces ?? 0),
            'Basement' => is_array($item['Basement'] ?? null)
                ? implode(', ', $item['Basement'])
                : ($item['Basement'] ?? $property->Basement ?? '0'),
            'ClosePrice' => $item['ClosePrice'] ?? $property->ClosePrice ?? 0,
            'listing_contract_date' => $contractDate ?? $property->listing_contract_date,
            'listing_modified_at' => $modifiedDate ?? $property->listing_modified_at,
            'close_date' => $soldDate ?? $closeDate ?? $property->close_date,
            'purchase_contract_date' => $soldDate ?? $purchaseDate ?? $property->purchase_contract_date,
            'created_at' => $contractDate ?? $property->created_at ?? now(),
            'updated_at' => $modifiedDate ?? now(),
            'private_notes' => $item['PrivateRemarks'] ?? $property->private_notes ?? '',
        ]);

        $saveStart = microtime(true);

        try {
        $property->save();
        } catch (\Illuminate\Database\QueryException $e) {
            // Only treat DUPLICATE KEY (1062) as concurrent-insert race.
            // Other 23000 errors (e.g. NOT NULL 1048) must surface / be fixed above.
            $isDuplicate = str_contains($e->getMessage(), '1062')
                || str_contains($e->getMessage(), 'Duplicate entry');

            if (! $isDuplicate) {
                throw $e;
            }

            $existing = Property::query()->where('external_id', $listingKey)->first();
            if ($existing === null) {
                throw $e;
            }

            $isNew = false;
            $property = $existing;
            $property->fill([
                'name' => $item['UnparsedAddress'] ?? $property->name,
                'PropertySubType' => $item['PropertySubType'] ?? $property->PropertySubType ?? 'sell',
                'description' => $item['PublicRemarks'] ?? $property->description ?? '',
                'content' => trim(($item['PublicRemarks'] ?? '') . '<br>' . ($item['PrivateRemarks'] ?? '')) ?: $property->content,
                'location' => $item['UnparsedAddress'] ?? $property->location,
                'MlsStatus' => $item['MlsStatus'] ?? $property->MlsStatus,
                'price' => $item['ListPrice'] ?? $property->price,
                'ClosePrice' => $item['ClosePrice'] ?? $property->ClosePrice ?? 0,
                'listing_contract_date' => $contractDate ?? $property->listing_contract_date,
                'listing_modified_at' => $modifiedDate ?? $property->listing_modified_at,
                'close_date' => $soldDate ?? $closeDate ?? $property->close_date,
                'updated_at' => $modifiedDate ?? now(),
            ]);
            $property->save();
        }

        $this->logPersistQueryTiming('save', $listingKey, microtime(true) - $saveStart, [
            'is_new' => $isNew,
            'property_id' => $property->id,
        ]);

        $slugStart = microtime(true);
        $this->ensurePropertySlug(
            (int) $property->id,
            (string) ($item['UnparsedAddress'] ?? $property->name),
            $listingKey
        );
        $this->logPersistQueryTiming('createSlug', $listingKey, microtime(true) - $slugStart);

        return [
            'is_new' => $isNew,
            'updated' => ! $isNew,
            'needs_geocode' => (float) ($property->latitude ?? 0) === 0.0 && ! empty($property->location),
            'property_id' => (int) $property->id,
        ];
    }

    /**
     * SMART TREB FALLBACK (Phases 2 / 4 / 5 / 11).
     *
     * Fetch a single listing that is missing (or stale) locally from TREB AMP,
     * persist it exactly like the historical importer (property row + rooms/
     * washroom facts via the AMP snapshot + PropertyHistory through model
     * events), attach its primary image, geocode inline when coordinates are
     * missing, and index it into Meilisearch so EVERY future request is served
     * 100% from local storage. The user never perceives the round-trip.
     *
     * Verified AMP capability notes (query.ampre.ca, vendor feed 12667):
     *  - HistoryTransactional resource returns HTTP 403 ("Feed does not include
     *    HistoryTransactional resource") -> no transactional price/status log is
     *    available; history is reconstructed only from local re_property_history
     *    (model events) + separate re-listing ListingKey snapshots.
     *  - Latitude/Longitude are NOT returned by the Property resource, so TREB
     *    coordinates are effectively unavailable and MapLibre/Nominatim geocoding
     *    is always required.
     *  - Sold/expired listings eventually drop off the feed and return an empty
     *    OData value (cannot be fetched on demand once gone).
     *
     * Auto-cache (Phase 4 / 12): idempotent on external_id; an existing local
     * row is never overwritten unless AMP has a strictly newer
     * ModificationTimestamp.
     *
     * @return Property|null  Null only when AMP has no such listing at all.
     */
    public function ingestListingFromAmp(string $listingKey, bool $indexNow = true, bool $geocodeNow = true): ?Property
    {
        $listingKey = strtoupper(trim($listingKey));

        // AMP ListingKeys look like "C13559336" (letter + digits). Guard against
        // arbitrary keyword input reaching a remote fetch.
        if ($listingKey === '' || ! preg_match('/^[A-Z]{1,2}\d{5,}$/', $listingKey)) {
            return null;
        }

        if (! TrebPropertyHelper::canFetchRemoteAmp()) {
            return Property::query()->where('external_id', $listingKey)->first();
        }

        $existing = Property::query()->where('external_id', $listingKey)->first();

        // Negative cache: if we recently confirmed AMP has no such listing
        // (delisted / never existed), don't re-hammer AMP on repeated searches.
        if ($existing === null && Cache::get('serik:amp-miss:' . $listingKey)) {
            return null;
        }

        // Single-flight lock: concurrent map/search/detail requests for the same
        // missing key must not fan out duplicate AMP fetches or race the insert.
        $lock = Cache::lock('serik:amp-ingest:' . $listingKey, 30);

        if (! $lock->get()) {
            // Another request is already ingesting this key. Give it a moment,
            // then serve whatever is now local (unique index prevents dupes).
            usleep(400000);

            return Property::query()->where('external_id', $listingKey)->first() ?? $existing;
        }

        try {
            // Re-read inside the lock in case a sibling request just inserted it.
            $existing = Property::query()->where('external_id', $listingKey)->first();

            // Fetch the full AMP record. This also persists the detail-page AMP
            // snapshot cache (rooms/washrooms/facts) via persistAmpSnapshot().
            $record = TrebPropertyHelper::fetchAmpPropertyForResync($listingKey);

            if (! is_array($record) || $record === []) {
                // Listing genuinely not in AMP (e.g. dropped-off sold listing).
                if ($existing === null) {
                    Cache::put('serik:amp-miss:' . $listingKey, 1, 600);
                }

                return $existing;
            }

            // Auto-cache guard: never clobber newer local data.
            if ($existing !== null) {
                $ampModified = $this->parseAmpDateValue($record['ModificationTimestamp'] ?? null);
                $localModified = $existing->listing_modified_at
                    ? Carbon::parse($existing->listing_modified_at)
                    : null;

                if ($ampModified !== null && $localModified !== null && $ampModified->lessThanOrEqualTo($localModified)) {
                    return $existing;
                }
            }

            // Persist the row exactly like the historical importer. Meili
            // indexing is handled explicitly below so we can also geocode first.
            $result = Property::withoutSyncingToSearch(
                fn () => $this->persistHistoricalAmpPropertyRow($record, $existing)
            );

            $propertyId = $result['property_id'] ?? ($existing->id ?? null);

            if (! $propertyId) {
                return $existing;
            }

            $property = Property::find($propertyId);

            if ($property === null) {
                return null;
            }

            // Primary image so the map marker / popup / search card render
            // immediately. Failures are non-fatal (lazy endpoints still work).
            if (empty($property->image_val) || $this->trebImageStore()->isRemoteUrl($property->image_val)) {
                try {
                    if ($this->assignTrebCoverImage($property, $listingKey)) {
                        Property::withoutSyncingToSearch(fn () => $property->save());
                    }
                } catch (\Throwable $e) {
                    Log::warning('ingestListingFromAmp image failed: ' . $e->getMessage(), ['key' => $listingKey]);
                }
            }

            // Coordinates. TREB lat/lng are not exposed by this feed, so a single
            // inline geocode (Nominatim, ~0.5-1s) runs on the FIRST fallback only.
            // Bulk gap-import sets $geocodeNow=false and lets serik:geocode-all
            // fill coordinates asynchronously (avoids fighting Nominatim's 1 rps).
            if ($geocodeNow && (float) ($property->latitude ?? 0) === 0.0 && ! empty($property->location)) {
                try {
                    $this->geocodePropertyBatch(1, [(int) $property->id]);
                    $property->refresh();
                } catch (\Throwable $e) {
                    Log::warning('ingestListingFromAmp geocode failed: ' . $e->getMessage(), ['key' => $listingKey]);
                }
            }

            // Index into Meilisearch so all future requests are 100% local.
            if ($indexNow) {
                try {
                    $property->searchable();
                } catch (\Throwable $e) {
                    Log::warning('ingestListingFromAmp index failed: ' . $e->getMessage(), ['key' => $listingKey]);
                }
            }

            Log::info('Smart TREB fallback ingested listing', [
                'key' => $listingKey,
                'is_new' => $result['is_new'] ?? null,
                'property_id' => $property->id,
                'geocoded' => (float) ($property->latitude ?? 0) !== 0.0,
                'has_image' => ! empty($property->image_val),
            ]);

            return $property;
        } finally {
            optional($lock)->release();
        }
    }

    /**
     * SMART PROPERTY HYDRATION (Phases 1/2/5).
     *
     * Completes a listing's DB-backed sections that are missing locally, using
     * TREB only for the gaps, then records a hydration checkpoint keyed to the
     * listing's ModificationTimestamp so nothing is re-fetched until TREB
     * actually changes. Designed to run AFTER the HTTP response (see
     * getPropertyDetails) so the user is never blocked.
     *
     * Sections handled here (persisted to MySQL / warmed in cache):
     *  - image_val       (Media)      -> persisted
     *  - coordinates     (geocode)    -> persisted
     *  - listing history (merge cache)-> warmed so the lazy endpoint is instant
     *  - rooms           (PropertyRooms) -> warmed so the lazy endpoint is instant
     *
     * Rooms/history/media have no dedicated tables in the current architecture
     * (by design), so they are hydrated into the same long-lived caches the
     * existing detail endpoints already read from — we EXTEND, not replace.
     *
     * @return array{skipped:bool,image:bool,coords:bool,history:int,rooms:int}
     */
    public function ensureListingHydrated(string $listingKey, bool $force = false): array
    {
        $out = ['skipped' => true, 'image' => false, 'coords' => false, 'history' => 0, 'rooms' => 0, 'amp_checked' => false, 'changed_fields' => []];
        $listingKey = strtoupper(trim($listingKey));

        if ($listingKey === '') {
            return $out;
        }

        $property = Property::query()->where('external_id', $listingKey)->first();

        if ($property === null) {
            return $out;
        }

        // Smart-cache checkpoint (Phase 5): a hydration is valid for a given
        // ModificationTimestamp. Newer TREB data invalidates it automatically.
        $signature = (string) ($property->listing_modified_at ?: $property->updated_at ?: '0');
        $checkpointKey = 'serik:hydrated:' . $listingKey;

        if (! $force && Cache::get($checkpointKey) === $signature) {
            return $out;
        }

        // Concurrency guard: a burst of detail requests for the same key should
        // trigger exactly one hydration pass, not one per request.
        $lock = Cache::lock('serik:hydrate-lock:' . $listingKey, 60);

        if (! $lock->get()) {
            return $out;
        }

        $out['skipped'] = false;
        $searchableChanged = false;

        try {

        // --- field-by-field reconciliation against the latest TREB record ---
        // Runs once per local version (gated by the checkpoint above) and only
        // applies changes when AMP is genuinely newer. This is what makes each
        // property self-heal: price/status/remarks/features/etc. are pulled
        // forward without a full re-import and without blocking the UI.
        try {
            $recon = $this->reconcileListingWithAmp($property, $listingKey);
            $out['amp_checked'] = $recon['checked'];
            $out['changed_fields'] = $recon['changed'];
            $searchableChanged = $recon['updated'];
        } catch (\Throwable $e) {
            Log::warning('hydrate reconcile failed: ' . $e->getMessage(), ['key' => $listingKey]);
        }

        // --- image_val (persisted as local WebP) ---
        $imageChanged = false;
        if (empty($property->image_val) || $this->trebImageStore()->isRemoteUrl($property->image_val)) {
            try {
                if ($this->assignTrebCoverImage($property, $listingKey)) {
                    Property::withoutSyncingToSearch(fn () => $property->save());
                    $out['image'] = true;
                    $imageChanged = true;
                }
            } catch (\Throwable $e) {
                Log::warning('hydrate image failed: ' . $e->getMessage(), ['key' => $listingKey]);
            }
        }

        // --- full gallery (persisted to images JSON as WebP paths) ---
        try {
            if ($this->assignTrebGallery($property, $listingKey)) {
                Property::withoutSyncingToSearch(fn () => $property->save());
                $out['gallery'] = is_array($property->images) ? count($property->images) : 0;
                $imageChanged = true;
            }
        } catch (\Throwable $e) {
            Log::warning('hydrate gallery failed: ' . $e->getMessage(), ['key' => $listingKey]);
        }

        if ($imageChanged) {
            $out['image'] = true;
        }

        // --- coordinates (persisted) ---
        if ((float) ($property->latitude ?? 0) === 0.0 && ! empty($property->location)) {
            try {
                $this->geocodePropertyBatch(1, [(int) $property->id]);
                $property->refresh();
                $out['coords'] = (float) ($property->latitude ?? 0) !== 0.0;
            } catch (\Throwable $e) {
                Log::warning('hydrate geocode failed: ' . $e->getMessage(), ['key' => $listingKey]);
            }
        }

        // --- warm listing history + rooms caches (lazy endpoints read these) ---
        $local = TrebPropertyHelper::dbRowToLocalArray($property);

        try {
            $history = TrebPropertyHelper::fetchListingHistoryForDetail($listingKey, $local);
            $out['history'] = is_array($history) ? count($history) : 0;
        } catch (\Throwable $e) {
            Log::warning('hydrate history failed: ' . $e->getMessage(), ['key' => $listingKey]);
        }

        try {
            $rooms = TrebPropertyHelper::fetchPropertyRoomsForDetail($listingKey);
            $out['rooms'] = is_array($rooms) ? count($rooms) : 0;
        } catch (\Throwable $e) {
            Log::warning('hydrate rooms failed: ' . $e->getMessage(), ['key' => $listingKey]);
        }

            // Meilisearch: reindex ONLY this document when a searchable field
            // moved (price/status/remarks from the AMP diff, a repaired image,
            // or fresh coordinates). Never rebuilds the whole index.
            if ($searchableChanged || $out['coords'] || $out['image']) {
                try {
                    $property->searchable();
                } catch (\Throwable $e) {
                    Log::warning('hydrate reindex failed: ' . $e->getMessage(), ['key' => $listingKey]);
                }
            }

            // Record the checkpoint against the CURRENT signature (the AMP
            // reconcile may have bumped listing_modified_at) so this version is
            // never re-fetched until TREB changes again.
            $finalSignature = (string) ($property->listing_modified_at ?: $property->updated_at ?: '0');
            Cache::put($checkpointKey, $finalSignature, 86400 * 7);

            Log::info('Listing hydrated', array_merge(['key' => $listingKey], $out));

            return $out;
        } finally {
            optional($lock)->release();
        }
    }

    /**
     * Snapshot the scalar columns that matter for the diff engine + search index.
     * Used before/after an AMP reconcile to report exactly what changed and to
     * decide whether Meilisearch needs a single-document reindex.
     *
     * @return array<string, mixed>
     */
    private function snapshotListingFields(Property $p): array
    {
        return [
            'name' => (string) $p->name,
            'location' => (string) $p->location,
            'description' => (string) $p->getRawOriginal('description'),
            'price' => (float) ($p->price ?? 0),
            'ClosePrice' => (float) ($p->ClosePrice ?? 0),
            'status' => (string) $p->status,
            'MlsStatus' => (string) $p->MlsStatus,
            'TransactionType' => (string) $p->TransactionType,
            'number_bedroom' => (int) ($p->number_bedroom ?? 0),
            'number_bathroom' => (int) ($p->number_bathroom ?? 0),
            'BedroomsBelowGrade' => (int) ($p->BedroomsBelowGrade ?? 0),
            'square' => (string) ($p->square ?? ''),
            'ParkingSpaces' => (int) ($p->ParkingSpaces ?? 0),
            'CoveredSpaces' => (int) ($p->CoveredSpaces ?? 0),
            'Basement' => (string) ($p->Basement ?? ''),
            'zip_code' => (string) ($p->zip_code ?? ''),
            'image_val' => (string) ($p->image_val ?? ''),
            'latitude' => (float) ($p->latitude ?? 0),
            'longitude' => (float) ($p->longitude ?? 0),
            'listing_modified_at' => (string) ($p->listing_modified_at ?? ''),
        ];
    }

    /**
     * Reconcile a local listing against the latest TREB AMP record (Issue 2 /
     * Smart Difference Engine). Only runs when AMP is genuinely newer, never
     * overwrites newer local data, records changed fields for reporting, and
     * lets PropertyHistoryRecorder capture the diff into re_property_history.
     *
     * @return array{checked:bool, updated:bool, changed:array<int,string>}
     */
    private function reconcileListingWithAmp(Property $property, string $listingKey): array
    {
        $result = ['checked' => false, 'updated' => false, 'changed' => []];

        if (! TrebPropertyHelper::canFetchRemoteAmp()) {
            return $result;
        }

        try {
            $record = TrebPropertyHelper::fetchAmpPropertyForResync($listingKey);
        } catch (\Throwable $e) {
            Log::warning('reconcile AMP fetch failed: ' . $e->getMessage(), ['key' => $listingKey]);

            return $result;
        }

        if (! is_array($record) || $record === []) {
            return $result;
        }

        $result['checked'] = true;

        // Never overwrite newer local data — respect ModificationTimestamp.
        $ampModified = $this->parseAmpDateValue($record['ModificationTimestamp'] ?? null);
        $localModified = $property->listing_modified_at
            ? Carbon::parse($property->listing_modified_at)
            : null;

        if ($ampModified !== null && $localModified !== null && $ampModified->lessThanOrEqualTo($localModified)) {
            return $result; // AMP is not newer — nothing to reconcile.
        }

        $before = $this->snapshotListingFields($property);

        // Full upsert from the AMP record. Same-valued columns are untouched by
        // Eloquent, so only genuinely changed fields are written; the history
        // recorder (model events) captures the price/status/etc. transitions.
        Property::withoutSyncingToSearch(
            fn () => $this->persistHistoricalAmpPropertyRow($record, $property)
        );

        $property->refresh();
        $after = $this->snapshotListingFields($property);

        foreach ($after as $field => $value) {
            if (($before[$field] ?? null) !== $value) {
                $result['changed'][] = $field;
            }
        }

        $result['updated'] = $result['changed'] !== [];

        return $result;
    }

    /**
     * Whether a local listing still has gaps worth hydrating. Cheap DB-only
     * check used to decide if the (non-blocking) hydration pass is needed.
     */
    private function listingNeedsHydration(Property $property): bool
    {
        if (empty($property->image_val)) {
            return true;
        }

        if ((float) ($property->latitude ?? 0) === 0.0) {
            return true;
        }

        $signature = (string) ($property->listing_modified_at ?: $property->updated_at ?: '0');

        return Cache::get('serik:hydrated:' . strtoupper((string) $property->external_id)) !== $signature;
    }

    private function logPersistQueryTiming(string $step, ?string $listingKey, float $elapsedSeconds, array $extra = []): void
    {
        $elapsed = round($elapsedSeconds, 3);
        $slowThreshold = 1.0;
        $profile = filter_var(env('SERIK_PROFILE_HISTORICAL_IMPORT', false), FILTER_VALIDATE_BOOL);

        if (! $profile && $elapsed < $slowThreshold) {
            return;
        }

        Log::info('persistHistoricalAmpPropertyRow query timing', array_merge([
            'step' => $step,
            'listing_key' => $listingKey,
            'seconds' => $elapsed,
        ], $extra));
    }

    private function applyLocalMapDateFilter($query, string $finalDate, bool $usesSoldDate, array $statuses = []): void
    {
        $daysMap = $this->mapDateDaysMap();
        $moreThanMap = $this->mapDateMoreThanMap();
        $maxYear = (int) date('Y') + 1;

        $applyContractCutoff = function ($inner, Carbon $cutoff) {
            // Calendar-day cutoff on listing_contract_date (AMP "Listed On").
            // startOfDay() aligns "Last X Days" with listing date, not time-of-day.
            // No whereYear() — keeps listing_contract_date index-friendly for map speed.
            $cutoffDay = $cutoff->copy()->startOfDay();

            $inner->where(function ($dateQuery) use ($cutoffDay) {
                $dateQuery->where('listing_contract_date', '>=', $cutoffDay)
                    ->orWhere(function ($col) use ($cutoffDay) {
                        $col->whereNull('listing_contract_date')
                            ->where('created_at', '>=', $cutoffDay);
                    });
            });
        };

        $applyModifiedCutoff = function ($inner, Carbon $cutoff) use ($maxYear) {
            $inner->where(function ($dateQuery) use ($cutoff, $maxYear) {
                $dateQuery->where(function ($col) use ($cutoff, $maxYear) {
                    $col->where('listing_modified_at', '>=', $cutoff)
                        ->whereYear('listing_modified_at', '>=', 2000)
                        ->whereYear('listing_modified_at', '<=', $maxYear);
                })->orWhere(function ($col) use ($cutoff, $maxYear) {
                    $col->whereNull('listing_modified_at')
                        ->where('updated_at', '>=', $cutoff)
                        ->whereYear('updated_at', '>=', 2000)
                        ->whereYear('updated_at', '<=', $maxYear);
                });
            });
        };

        $applyCloseCutoff = function ($inner, Carbon $cutoff) use ($maxYear) {
            $inner->where(function ($dateQuery) use ($cutoff, $maxYear) {
                $dateQuery->where(function ($col) use ($cutoff, $maxYear) {
                    $col->where('purchase_contract_date', '>=', $cutoff)
                        ->whereYear('purchase_contract_date', '>=', 2000)
                        ->whereYear('purchase_contract_date', '<=', $maxYear);
                })->orWhere(function ($col) use ($cutoff, $maxYear) {
                    $col->where('close_date', '>=', $cutoff)
                        ->whereYear('close_date', '>=', 2000)
                        ->whereYear('close_date', '<=', $maxYear);
                });
            });
        };

        if (isset($daysMap[$finalDate])) {
            $cutoff = now()->subDays($daysMap[$finalDate]);

            if ($usesSoldDate) {
                $delistedStatuses = ['Expired', 'Terminated', 'Suspended'];
                $isDelisted = $statuses !== [] && ! empty(array_intersect($statuses, $delistedStatuses));

                if ($isDelisted) {
                    $applyModifiedCutoff($query, $cutoff);

                    return;
                }

                $applyCloseCutoff($query, $cutoff);

                return;
            }

            // Active listings are filtered by the "Listed On" date (listing_contract_date)
            // for every active status. The filter UI is labelled "Listing Date", so a
            // "last N days" selection must reflect when a property was listed, not when it
            // was last modified (a price change months later must not resurface it).
            $applyContractCutoff($query, $cutoff);

            return;
        }

        if (isset($moreThanMap[$finalDate])) {
            $cutoff = now()->subDays($moreThanMap[$finalDate]);

            if ($usesSoldDate) {
                $query->where(function ($q) use ($cutoff, $maxYear) {
                    $q->where(function ($inner) use ($cutoff, $maxYear) {
                        $inner->whereNotNull('purchase_contract_date')
                            ->where('purchase_contract_date', '<=', $cutoff)
                            ->whereYear('purchase_contract_date', '>=', 2000)
                            ->whereYear('purchase_contract_date', '<=', $maxYear);
                    })->orWhere(function ($inner) use ($cutoff, $maxYear) {
                        $inner->whereNotNull('close_date')
                            ->where('close_date', '<=', $cutoff)
                            ->whereYear('close_date', '>=', 2000)
                            ->whereYear('close_date', '<=', $maxYear);
                    });
                });
            } else {
                $query->where(function ($q) use ($cutoff, $maxYear) {
                    $q->where(function ($inner) use ($cutoff, $maxYear) {
                        $inner->whereNotNull('listing_contract_date')
                            ->where('listing_contract_date', '<=', $cutoff)
                            ->whereYear('listing_contract_date', '>=', 2000)
                            ->whereYear('listing_contract_date', '<=', $maxYear);
                    })->orWhere(function ($inner) use ($cutoff, $maxYear) {
                        $inner->where('created_at', '<=', $cutoff)
                            ->whereYear('created_at', '>=', 2000)
                            ->whereYear('created_at', '<=', $maxYear);
                    });
                });
            }

            return;
        }

        if (preg_match('/^year_(\d{4})$/', $finalDate, $yearMatch)) {
            $year = (int) $yearMatch[1];
            $dateColumn = $usesSoldDate ? 'close_date' : 'listing_contract_date';
            $fallbackColumn = $usesSoldDate ? 'updated_at' : 'created_at';

            $query->where(function ($q) use ($dateColumn, $fallbackColumn, $year) {
                $q->whereYear($dateColumn, $year)
                    ->orWhere(function ($inner) use ($fallbackColumn, $year) {
                        $inner->whereNull($dateColumn)
                            ->whereYear($fallbackColumn, $year);
                    });
            });
        }
    }

    private function fetchTrebListingKeysFromFilter(string $filter, int $top = 4000): array
    {
        $url = 'https://query.ampre.ca/odata/Property?'
            . '$filter=' . rawurlencode($filter)
            . '&$select=ListingKey'
            . '&$top=' . $top;

        $cacheKey = 'map_treb_date_v3_' . md5($url);
        $payload = Cache::remember($cacheKey, 300, fn () => $this->ampCurl($url));

        if (! is_array($payload) || empty($payload['value'])) {
            return [];
        }

        return collect($payload['value'])
            ->pluck('ListingKey')
            ->filter()
            ->map(fn ($key) => strtoupper((string) $key))
            ->all();
    }

    /**
     * @return array<int, string>
     */
    private function trebMapCommonConditions(?string $city, ?string $transaction, ?string $subtypes): array
    {
        $conditions = [
            "PropertySubType ne 'Industrial'",
            "PropertySubType ne 'Commercial Retail'",
        ];

        $city = trim((string) $city);
        if ($city !== '' && strtolower($city) !== 'ontario') {
            $escapedCity = str_replace("'", "''", $city);
            $conditions[] = "contains(City,'{$escapedCity}')";
        }

        if (! empty($transaction) && $transaction !== 'null') {
            $escapedTxn = str_replace("'", "''", $transaction);
            $conditions[] = "TransactionType eq '{$escapedTxn}'";
        }

        if (! empty($subtypes)) {
            $subtypeParts = [];
            foreach (array_filter(array_map('trim', explode(',', $subtypes))) as $subtype) {
                $escaped = str_replace("'", "''", trim($subtype));
                $subtypeParts[] = "PropertySubType eq '{$escaped}'";
                $subtypeParts[] = "PropertySubType eq '{$escaped} '";
            }
            if ($subtypeParts !== []) {
                $conditions[] = '(' . implode(' or ', array_unique($subtypeParts)) . ')';
            }
        }

        return $conditions;
    }

    /**
     * @param  array<int, string>  $statuses
     * @return array<int, string>
     */
    private function fetchTrebListingKeysForMapDateFilter(
        string $finalDate,
        bool $usesSoldDate,
        ?string $city,
        ?string $transaction,
        array $statuses,
        bool $isActiveFilter,
        ?string $subtypes = null
    ): array {
        if (! TrebPropertyHelper::canFetchRemoteAmp()) {
            return [];
        }

        $daysMap = $this->mapDateDaysMap();

        if (! isset($daysMap[$finalDate])) {
            return [];
        }

        $cutoffDate = now()->subDays($daysMap[$finalDate])->format('Y-m-d');
        $cutoffDateTime = $cutoffDate . 'T00:00:00Z';
        $common = $this->trebMapCommonConditions($city, $transaction, $subtypes);
        $keys = [];

        $delistedStatuses = ['Expired', 'Terminated', 'Suspended'];
        $soldStatuses = ['Sold', 'Sold Conditional', 'Sold Conditional Escape', 'Leased', 'Leased Conditional'];
        $maxCloseYear = (int) date('Y') + 1;

        if ($isActiveFilter || ! $usesSoldDate) {
            foreach ([
                "ListingContractDate ge {$cutoffDate}",
                "OriginalEntryTimestamp ge {$cutoffDateTime}",
                "PriceChangeTimestamp ge {$cutoffDateTime}",
                "ModificationTimestamp ge {$cutoffDateTime}",
            ] as $dateCondition) {
                $keys = array_merge(
                    $keys,
                    $this->fetchTrebListingKeysFromFilter(implode(' and ', array_merge($common, [$dateCondition])))
                );
            }
        }

        if ($usesSoldDate) {
            $isDelisted = $statuses !== [] && ! empty(array_intersect($statuses, $delistedStatuses));

            if ($isDelisted) {
                $statusParts = array_map(function ($status) {
                    $escaped = str_replace("'", "''", $status);

                    return "MlsStatus eq '{$escaped}'";
                }, array_values(array_intersect($statuses, $delistedStatuses)));

                $keys = array_merge(
                    $keys,
                    $this->fetchTrebListingKeysFromFilter(implode(' and ', array_merge($common, [
                        "ModificationTimestamp ge {$cutoffDateTime}",
                        '(' . implode(' or ', $statusParts) . ')',
                    ])))
                );
            } else {
                $matchedSold = $statuses !== []
                    ? array_values(array_intersect($statuses, $soldStatuses))
                    : $soldStatuses;

                $statusParts = array_map(function ($status) {
                    $escaped = str_replace("'", "''", $status);

                    return "MlsStatus eq '{$escaped}'";
                }, $matchedSold);

                $keys = array_merge(
                    $keys,
                    $this->fetchTrebListingKeysFromFilter(implode(' and ', array_merge($common, [
                        "PurchaseContractDate ge {$cutoffDate}",
                        "year(PurchaseContractDate) ge 2000 and year(PurchaseContractDate) le {$maxCloseYear}",
                        '(' . implode(' or ', $statusParts) . ')',
                    ])))
                );

                $keys = array_merge(
                    $keys,
                    $this->fetchTrebListingKeysFromFilter(implode(' and ', array_merge($common, [
                        "CloseDate ge {$cutoffDate}",
                        "year(CloseDate) ge 2000 and year(CloseDate) le {$maxCloseYear}",
                        '(' . implode(' or ', $statusParts) . ')',
                    ])))
                );

                $keys = array_merge(
                    $keys,
                    $this->fetchTrebListingKeysFromFilter(implode(' and ', array_merge($common, [
                        "ModificationTimestamp ge {$cutoffDateTime}",
                        '(' . implode(' or ', $statusParts) . ')',
                    ])))
                );

                $globalSoldFilter = implode(' and ', [
                    "ModificationTimestamp ge {$cutoffDateTime}",
                    '(' . implode(' or ', $statusParts) . ')',
                    "PropertySubType ne 'Industrial'",
                    "PropertySubType ne 'Commercial Retail'",
                ]);
                $keys = array_merge($keys, $this->fetchTrebListingKeysFromFilter($globalSoldFilter));
            }
        }

        return array_values(array_unique($keys));
    }


    public function fetchMapProperties(Request $request)
    {
        // --- Sanitize Bounds (clamp to Ontario for performance + accuracy) ---
        $south = (float) $request->input('south', -90);
        $north = (float) $request->input('north', 90);
        $west = (float) $request->input('west', -180);
        $east = (float) $request->input('east', 180);

        [$south, $north, $west, $east] = $this->clampMapBoundsToOntario($south, $north, $west, $east);

        if ($south > $north || $west > $east) {
            return response()->json(['error' => 'Invalid map bounds'], 400);
        }

        // Snap the viewport outward to a zoom-dependent grid so small pans/zooms
        // reuse the same cached GeoJSON (warm path ~4ms) instead of triggering a
        // fresh ~800ms Meili query on every mouse move. The tiny extra area
        // fetched at the grid edges is harmless (markers render slightly beyond
        // the viewport, exactly like map tiles).
        $zoom = (int) $request->input('zoom', 10);
        [$south, $north, $west, $east] = $this->snapMapBoundsToGrid($south, $north, $west, $east, $zoom);

        // Prefer Meilisearch for the common active-listing browse (~tens of ms).
        // Falls back to MySQL for complex sold/date/subtype filters, or when Meili
        // is unavailable. engine=mysql forces the MySQL path for A/B testing.
        if ($request->input('engine') !== 'mysql') {
            // v6: empty Meili results no longer cached — ignore stale v5 empty payloads.
            $meiliCacheKey = 'map_meili_v6_' . md5(implode('|', [
                round($south, 4), round($north, 4), round($west, 4), round($east, 4),
                $this->mapFilterSignature($request),
            ]));
            // Cache the fully-encoded payload (JSON + gzip bytes), not the raw
            // array, so warm requests skip json_encode + gzencode entirely and
            // just stream pre-built bytes.
            $payload = Cache::get($meiliCacheKey);

            if ($payload === null) {
                $meiliGeo = $this->fetchMapPropertiesViaMeili($request, $south, $north, $west, $east);

                // Meili healthy-but-empty (fresh deploy / index not built / no _geo)
                // must NOT short-circuit the map — fall through to MySQL.
                $meiliCount = is_array($meiliGeo) ? count($meiliGeo['features'] ?? []) : 0;
                if ($meiliGeo !== null && $meiliCount > 0) {
                    $payload = $this->encodeMapPayload($meiliGeo);
                    Cache::put($meiliCacheKey, $payload, 600);
                }
            }

            if ($payload !== null) {
                return $this->respondMapPayload($request, $payload);
            }
        }

        // v25: grid-snapped bounds + filter signature (matches the Meili key
        // strategy) so the MySQL fallback also benefits from pan/zoom cache reuse.
        $cacheKey = 'map_v25_' . md5(implode('|', [
            round($south, 4), round($north, 4), round($west, 4), round($east, 4),
            $this->mapFilterSignature($request),
        ]));

        $cachedPayload = Cache::get($cacheKey);
        if (is_array($cachedPayload) && (isset($cachedPayload['gz']) || isset($cachedPayload['json']))) {
            return $this->respondMapPayload($request, $cachedPayload);
        }

        $geojson = (function () use ($request, $south, $north, $west, $east) {
            $canViewSold = auth('account')->check() || auth()->check();

            $query = Property::query()
                ->select([
                    'id',
                    'name',
                    'external_id',
                    'latitude',
                    'longitude',
                    'price',
                    'number_bedroom',
                    'number_bathroom',
                    'square',
                    'broker',
                    'image_val',
                    'ClosePrice',
                    'TransactionType',
                    'MlsStatus',
                    'PropertySubType',
                    'ParkingSpaces',
                    'CoveredSpaces',
                    'created_at',
                    'updated_at'
                ])
                ->where('moderation_status', 'approved')
                ->whereNotNull('latitude')
                ->whereNotNull('longitude')
                ->where('latitude', '!=', 0)
                ->where('longitude', '!=', 0)
                ->whereBetween('latitude', [$south, $north])
                ->whereBetween('longitude', [$west, $east]);

            $this->applyMapResidentialScope($query);



            $city = trim($request->input('city'));

            if (strtolower($city) === 'niagarafalls') {
                $city = 'Niagara Falls';
            } else {
                $city = ucwords(strtolower($city));
            }
            $cityMap = [
                'Brampton' => 'Brampton',
                'Mississauga' => 'Mississauga',
                'Vaughan' => 'Vaughan',
                'Milton' => 'Milton',
                'Oakville' => 'Oakville',
                'NiagaraFalls' => 'Niagara Falls', // note the space
                'Toronto' => 'Toronto',
                'KWC' => ['Kitchener', 'Waterloo', 'Cambridge'], // array for multiple cities
            ];


            if (!empty($city) && strtolower($city) !== 'ontario') {
                $this->applyMapCityFilter($query, $city, $cityMap);
            }


            // --- Subtypes ---
            $subtypes = $request->input('subtypes');
            if (!empty($subtypes)) {
                $subtypesArray = array_values(array_filter(array_map('trim', explode(',', $subtypes))));
                if ($subtypesArray !== [] && ! in_array('All', $subtypesArray, true)) {
                    $expanded = [];
                    foreach ($subtypesArray as $subtype) {
                        $trimmed = trim($subtype);
                        $expanded[] = $trimmed;
                        $expanded[] = $trimmed . ' ';
                        $expanded[] = str_replace('-', ' ', $trimmed);
                        $expanded[] = str_replace('-', ' ', $trimmed) . ' ';
                        $expanded[] = str_replace(' ', '-', $trimmed);
                        $expanded[] = str_replace(' ', '-', $trimmed) . ' ';
                    }
                    $query->whereIn('PropertySubType', array_unique(array_filter($expanded)));
                }
            }

            // --- Status (parse early for transaction/date rules) ---
            $statusInput = trim((string) $request->input('status', ''));
            $statuses = $statusInput !== '' ? array_values(array_filter(array_map('trim', explode(',', $statusInput)))) : [];

            $activeStatuses = ['New', 'Price Change', 'Extension', 'Previous Status'];
            $soldStatuses = ['Sold', 'Sold Conditional', 'Sold Conditional Escape', 'Leased', 'Leased Conditional'];
            $delistedStatuses = ['Expired', 'Terminated', 'Suspended'];
            $isSoldOrDelistedFilter = $statuses !== [] && ! empty(array_intersect($statuses, array_merge($soldStatuses, $delistedStatuses)));
            $isActiveFilter = $statuses !== []
                && empty(array_diff($activeStatuses, $statuses))
                && count(array_intersect($statuses, $activeStatuses)) === count($activeStatuses);

            // --- Transaction (skip for sold/de-listed — MlsStatus is authoritative) ---
            $transaction = $request->input('transaction');
            if (!empty($transaction) && $transaction !== 'null' && !$isSoldOrDelistedFilter) {
                $query->where('TransactionType', $transaction);
            }

            // --- Price ---
            $minPrice = (float) $request->input('min_price', 0);
            $maxPrice = (float) $request->input('max_price', 100000000);
            $isLeaseTransaction = ($transaction ?? '') === 'For Lease';
            $defaultMaxPrice = $isLeaseTransaction ? 50000 : 5000000;

            if ($isSoldOrDelistedFilter && empty(array_intersect($statuses, $delistedStatuses))) {
                if ($maxPrice <= 0 || $maxPrice >= 500000000 || $maxPrice >= $defaultMaxPrice) {
                    if ($minPrice > 0) {
                        $query->where(function ($priceQ) use ($minPrice) {
                            $priceQ->where(function ($inner) use ($minPrice) {
                                $inner->where('ClosePrice', '>', 0)->where('ClosePrice', '>=', $minPrice);
                            })->orWhere(function ($inner) use ($minPrice) {
                                $inner->where(function ($z) {
                                    $z->whereNull('ClosePrice')->orWhere('ClosePrice', '<=', 0);
                                })->where('price', '>=', $minPrice);
                            });
                        });
                    }
                } elseif ($minPrice >= 0 && $maxPrice > 0) {
                    $query->where(function ($priceQ) use ($minPrice, $maxPrice) {
                        $priceQ->where(function ($inner) use ($minPrice, $maxPrice) {
                            $inner->where('ClosePrice', '>', 0)->whereBetween('ClosePrice', [$minPrice, $maxPrice]);
                        })->orWhere(function ($inner) use ($minPrice, $maxPrice) {
                            $inner->where(function ($z) {
                                $z->whereNull('ClosePrice')->orWhere('ClosePrice', '<=', 0);
                            })->whereBetween('price', [$minPrice, $maxPrice]);
                        });
                    });
                }
            } elseif ($maxPrice <= 0 || $maxPrice >= 500000000 || $maxPrice >= $defaultMaxPrice) {
                if ($minPrice > 0) {
                    $query->where('price', '>=', $minPrice);
                }
            } elseif ($minPrice >= 0 && $maxPrice > 0) {
                $query->whereBetween('price', [$minPrice, $maxPrice]);
            }

            // --- Date Filter ---
            $date = $request->input('date');
            $dateSold = $request->input('date_sold');

            $usesSoldDate = $isSoldOrDelistedFilter;

            $finalDate = $usesSoldDate ? $dateSold : $date;

            if ($isActiveFilter && (empty($finalDate) || $finalDate === 'all')) {
                $finalDate = null;
            }

            if ($isSoldOrDelistedFilter && (empty($finalDate) || $finalDate === 'all')) {
                $finalDate = null;
            }

            if (! empty($finalDate) && $finalDate !== 'all') {
                // Use local indexed date columns for deterministic map filtering.
                // Remote TREB key widening can re-introduce stale rows and slows map responses.
                $query->where(function ($localQuery) use ($finalDate, $usesSoldDate, $statuses) {
                    $this->applyLocalMapDateFilter($localQuery, $finalDate, $usesSoldDate, $statuses);
                });
            }

            if ($statuses !== []) {
                $query->where(function ($statusQuery) use ($statuses, $isActiveFilter, $activeStatuses) {
                    if ($isActiveFilter) {
                        $statusQuery->whereIn('MlsStatus', $activeStatuses);
                    } else {
                        $statusQuery->whereIn('MlsStatus', $statuses);
                    }
                });
            } elseif (! empty($transaction) && $transaction !== 'null' && ! $isSoldOrDelistedFilter) {
                $query->whereNotIn('MlsStatus', array_merge($soldStatuses, $delistedStatuses));
            }

            // --- Bedrooms ---
            $bedrooms = $request->input('bedrooms');
            if (!empty($bedrooms) && strtolower($bedrooms) !== 'null') {
                if (str_contains($bedrooms, '+')) {
                    $query->where('number_bedroom', '>=', (int) $bedrooms);
                } else {
                    $query->where('number_bedroom', (int) $bedrooms);
                }
            }

            // --- Bathrooms ---
            $bathrooms = $request->input('bathrooms');
            if (!empty($bathrooms) && strtolower($bathrooms) !== 'null') {
                if (str_contains($bathrooms, '+')) {
                    $query->where('number_bathroom', '>=', (int) $bathrooms);
                } else {
                    $query->where('number_bathroom', (int) $bathrooms);
                }
            }

            // --- Garage / covered parking ---
            $basement = $request->input('basement');
            if (!empty($basement) && strtolower($basement) !== 'null') {
                $garageValue = (int) str_replace('+', '', $basement);
                $query->where(function ($garageQuery) use ($garageValue, $basement) {
                    if (str_contains($basement, '+')) {
                        $garageQuery->where('CoveredSpaces', '>=', $garageValue)
                            ->orWhere(function ($legacy) use ($garageValue) {
                                $legacy->whereNull('CoveredSpaces')
                                    ->where('ParkingSpaces', '>=', $garageValue);
                            });
                    } else {
                        $garageQuery->where('CoveredSpaces', $garageValue)
                            ->orWhere(function ($legacy) use ($garageValue) {
                                $legacy->whereNull('CoveredSpaces')
                                    ->where('ParkingSpaces', $garageValue);
                            });
                    }
                });
            }

            // --- Basement ---
            $basement1 = $request->input('basement1');

            if (!empty($basement1) && strtolower($basement1) !== 'null') {
                $query->whereRaw("JSON_SEARCH(Basement, 'one', ?) IS NOT NULL", ["%$basement1%"]);
            }

            // --- Square ---
            $squareMin = (int) $request->input('square_min');
            $squareMax = (int) $request->input('square_max');

            if ($squareMin > 0) {
                $query->where('square', '>=', $squareMin);
            }

            if (!empty($squareMax)) {
                $query->where('square', '<=', (int) $squareMax);
            }



            $zoom = (int) $request->input('zoom', 10);

            // Hard caps keep payload + serialize under the 50ms target once the
            // query itself is indexed. Client-side MapLibre clustering still
            // works with a few thousand points and feels instant.
            // When Meilisearch is down this MySQL path is the only option —
            // keep limits tighter so the map stays responsive (~2-4s max)
            // instead of serializing 6–8k features for 10+ seconds.
            $limit = match (true) {
                $zoom <= 8 => 800,
                $zoom <= 10 => 1500,
                $zoom <= 12 => 2500,
                default => 4000,
            };

            $properties = $query
                ->limit($limit)
                ->toBase()
                ->get();

            $slugMap = [];
            if ($properties->isNotEmpty()) {
                $slugMap = DB::table('slugs')
                    ->where('reference_type', Property::class)
                    ->whereIn('reference_id', $properties->pluck('id'))
                    ->pluck('key', 'reference_id')
                    ->all();
            }

            return [
                'type' => 'FeatureCollection',
                'features' => $properties->map(function ($property) use ($slugMap, $canViewSold) {
                    $isSoldHistory = TrebPropertyHelper::isSoldHistoryMlsStatus($property->MlsStatus);
                    $requiresLogin = $isSoldHistory && !$canViewSold;
                    $displayDate = $isSoldHistory ? $property->updated_at : $property->created_at;

                    if ($requiresLogin) {
                        return [
                            'type' => 'Feature',
                            'geometry' => [
                                'type' => 'Point',
                                'coordinates' => [
                                    (float) $property->longitude,
                                    (float) $property->latitude,
                                ],
                            ],
                            'properties' => [
                                'id' => $property->id,
                                'name' => 'Sold Listing',
                                'external_id' => '',
                                'transaction' => $property->MlsStatus,
                                'mls_status' => $property->MlsStatus,
                                'image' => 'https://serik.ca/storage/avatars/1.jpg',
                                'price' => 0,
                                'ClosePrice' => null,
                                'bedrooms' => null,
                                'bathrooms' => null,
                                'basement' => null,
                                'url' => '',
                                'date' => null,
                                'area' => null,
                                'agency' => '',
                                'requires_login' => true,
                            ],
                        ];
                    }

                    return [
                        'type' => 'Feature',
                        'geometry' => [
                            'type' => 'Point',
                            'coordinates' => [
                                (float) $property->longitude,
                                (float) $property->latitude
                            ]
                        ],
                        'properties' => [
                            'id' => $property->id,
                            'name' => $property->name,
                            'external_id' => $property->external_id,
                            'transaction' => $property->MlsStatus === 'New'
                                ? ($property->TransactionType === 'For Lease' ? 'For Lease' : 'For Sale')
                                : $property->MlsStatus,
                            'mls_status' => $property->MlsStatus,
                            'image' => $property->image_val ?: 'https://serik.ca/storage/avatars/1.jpg',
                            'price' => $property->price,
                            'ClosePrice' => $property->ClosePrice,
                            'bedrooms' => $property->number_bedroom,
                            'bathrooms' => $property->number_bathroom,
                            'garage' => $property->CoveredSpaces ?? null,
                            'parking' => $property->ParkingSpaces ?? null,
                            'basement' => $property->CoveredSpaces ?? $property->ParkingSpaces ?? null,
                            'url' => $slugMap[$property->id]
                                ?? (Str::slug($property->name ?? 'property') . '-' . strtolower((string) $property->external_id)),
                            'date' => $displayDate ? date('Y-m-d', strtotime((string) $displayDate)) : null,
                            'area' => $property->square,
                            'agency' => $property->broker,
                            'requires_login' => false,
                        ]
                    ];
                })->toArray()
            ];
        })();

        $payload = $this->encodeMapPayload($geojson);
        Cache::put($cacheKey, $payload, 600);

        return $this->respondMapPayload($request, $payload);

    }

    /**
     * Meilisearch-backed map data for the common "browse active listings" case.
     * Returns a GeoJSON FeatureCollection identical in shape to the MySQL path,
     * or null to signal the caller to fall back (complex sold/date/subtype
     * filters, or Meilisearch unavailable).
     */
    private function fetchMapPropertiesViaMeili(Request $request, float $south, float $north, float $west, float $east): ?array
    {
        // Complex cases stay on the exact MySQL logic.
        $status = trim((string) $request->input('status', ''));
        $statuses = $status !== '' ? array_values(array_filter(array_map('trim', explode(',', $status)))) : [];
        $soldStatuses = ['Sold', 'Sold Conditional', 'Sold Conditional Escape', 'Leased', 'Leased Conditional'];
        $delistedStatuses = ['Expired', 'Terminated', 'Suspended'];
        $soldOrDelisted = array_merge($soldStatuses, $delistedStatuses);
        $subtypes = trim((string) $request->input('subtypes', ''));

        // Subtype chips + sold-date windows + "more than" / specific-year windows
        // still need the exact MySQL logic. Plain Sold/De-listed status chips use
        // Meili mls_status IN (verified accurate).
        $isSoldOrDelistedFilter = $statuses !== [] && ! empty(array_intersect($statuses, $soldOrDelisted));
        $activeDate = trim((string) $request->input('date', ''));
        $daysMap = $this->mapDateDaysMap();

        // "Last N days" for active listings resolves via the indexed
        // listing_contract_ts inside Meili (instant). Everything else defers.
        $activeDateCutoffTs = null;
        if ($activeDate !== '' && $activeDate !== 'all' && ! $isSoldOrDelistedFilter && isset($daysMap[$activeDate])) {
            $activeDateCutoffTs = now()->subDays($daysMap[$activeDate])->startOfDay()->getTimestamp();
        }

        $unhandledActiveDate = $activeDate !== '' && $activeDate !== 'all' && $activeDateCutoffTs === null;

        if (
            $request->filled('date_sold')
            || $unhandledActiveDate
            || ($subtypes !== '' && strtolower($subtypes) !== 'all')
        ) {
            return null;
        }

        $city = trim((string) $request->input('city'));
        if (strtolower($city) === 'ontario') {
            $city = '';
        } elseif (strtolower($city) === 'niagarafalls') {
            $city = 'Niagara Falls';
        } elseif ($city !== '') {
            $city = ucwords(strtolower($city));
        }

        $transaction = $request->input('transaction');
        $zoom = (int) $request->input('zoom', 10);
        $limit = match (true) {
            // Cold Meili responses dominate map latency. Client-side clustering
            // already coalesces overlapping pins, so over-fetching 6–8k features
            // adds JSON/gzip cost without visible benefit at city/metro zooms.
            $zoom <= 8 => 1200,
            $zoom <= 10 => 2500,
            $zoom <= 12 => 4000,
            default => 5500,
        };

        // Status filter parity with the MySQL path:
        //  - explicit active statuses => restrict to exactly those MlsStatus values
        //  - no status but a transaction => exclude sold/de-listed (active browse)
        $opts = [
            'residential_only' => true,
            'city' => $city ?: null,
            'transaction' => (! empty($transaction) && $transaction !== 'null') ? $transaction : null,
            'min_price' => (float) $request->input('min_price', 0),
            'max_price' => (float) $request->input('max_price', 0),
            'min_bedrooms' => str_contains((string) $request->input('bedrooms'), '+') ? (int) $request->input('bedrooms') : 0,
            'min_bathrooms' => str_contains((string) $request->input('bathrooms'), '+') ? (int) $request->input('bathrooms') : 0,
        ];

        if ($statuses !== []) {
            $opts['statuses'] = $statuses;
        } elseif (! empty($transaction) && $transaction !== 'null') {
            $opts['exclude_statuses'] = $soldOrDelisted;
        }

        if ($activeDateCutoffTs !== null) {
            $opts['listing_contract_ts_gte'] = $activeDateCutoffTs;
        }

        $hits = app(\Botble\RealEstate\Services\PropertySearchService::class)->geoSearch(
            $south,
            $north,
            $west,
            $east,
            $opts,
            $limit
        );

        if ($hits === null) {
            return null;
        }

        // City facet can miss (casing / empty city field) and return []. Never
        // blank the active map — soft-retry without city like the MySQL path.
        if ($hits === [] && ! empty($opts['city'])) {
            unset($opts['city']);
            $hits = app(\Botble\RealEstate\Services\PropertySearchService::class)->geoSearch(
                $south,
                $north,
                $west,
                $east,
                $opts,
                $limit
            );
            if ($hits === null) {
                return null;
            }
        }

        // Slugs are derived from name + ListingKey for map markers — skip the
        // N-row slug-table lookup so cold path stays lean. Popup and detail
        // pages still resolve the real slug from the slugs table.
        //
        // Images are intentionally omitted from the cold GeoJSON (a 3500-row
        // image_val pluck was measured at ~1.7s). Cluster/list cards hydrate
        // thumbnails on demand via /api/v1/map-thumbnails.
        $features = [];

        foreach ($hits as $h) {
            if (empty($h['_geo'])) {
                continue;
            }

            $mls = $h['mls_status'] ?? '';
            // Keep only fields the map UI actually reads for pins / list / cluster
            // cards. Full photos + facts load from map-property-bundle on click.
            // Truncate long agency names — ListOfficeName can be 80+ chars and is
            // the biggest variable string in the GeoJSON payload.
            $agency = (string) ($h['broker'] ?? '');
            if (mb_strlen($agency) > 40) {
                $agency = mb_substr($agency, 0, 37) . '...';
            }
            $name = (string) ($h['name'] ?? '');
            if (mb_strlen($name) > 80) {
                $name = mb_substr($name, 0, 77) . '...';
            }

            $features[] = [
                'type' => 'Feature',
                'geometry' => [
                    'type' => 'Point',
                    'coordinates' => [(float) $h['_geo']['lng'], (float) $h['_geo']['lat']],
                ],
                'properties' => [
                    'id' => $h['id'],
                    'name' => $name,
                    'external_id' => $h['external_id'] ?? '',
                    'transaction' => $mls === 'New'
                        ? (($h['transaction_type'] ?? '') === 'For Lease' ? 'For Lease' : 'For Sale')
                        : $mls,
                    'mls_status' => $mls,
                    'image' => '',
                    'price' => $h['price'] ?? 0,
                    'ClosePrice' => $h['close_price'] ?? null,
                    'bedrooms' => $h['number_bedroom'] ?? null,
                    'bathrooms' => $h['number_bathroom'] ?? null,
                    'garage' => $h['covered_spaces'] ?? null,
                    'area' => $h['square'] ?? null,
                    'agency' => $agency,
                    'date' => isset($h['created_ts']) ? date('Y-m-d', (int) $h['created_ts']) : null,
                    'url' => Str::slug($h['name'] ?? 'property') . '-' . strtolower((string) ($h['external_id'] ?? '')),
                    'requires_login' => false,
                ],
            ];
        }

        return ['type' => 'FeatureCollection', 'features' => $features];
    }

    public function getPropertyDetails($listingKey)
    {
        $property = Property::query()
            ->where(function ($query) use ($listingKey) {
                $query->where('external_id', $listingKey)
                    ->orWhere('external_id', strtoupper($listingKey));
            })
            ->first();

        // SMART TREB FALLBACK (Phase 2 / 7 / 11): a detail page opened for a
        // listing that is not local yet (direct URL, saved/favourite listing, a
        // freshly-shared MLS) is fetched from AMP, persisted, geocoded and
        // indexed on this first request, then served locally on every request
        // afterward. Browsing existing local listings never triggers this.
        if ($property === null) {
            $property = app(\Botble\RealEstate\Services\LiveTrebPropertyFallbackService::class)
                ->ingestByListingKey((string) $listingKey, true, false);
            if ($property !== null) {
                $property->refresh();
            }
        }

        if ($property && $property->isSoldHistory() && !(auth('account')->check() || auth()->check())) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthenticated access to sold property details',
                'data' => null
            ], 401);
        }

        if ($property && TrebPropertyHelper::isCommercialSubType($property->PropertySubType ?? null)) {
            return response()->json([
                'data' => null,
                'key_facts' => null,
                'message' => 'No data found',
            ], 404);
        }

        $local = $property ? TrebPropertyHelper::dbRowToLocalArray($property) : null;

        $isLocked = $property && $property->isSoldHistory() && ! (auth('account')->check() || auth()->check());

        $record = TrebPropertyHelper::resolveFactRecordForDetail(strtoupper($listingKey), $local);

        if ($record === []) {
            return response()->json([
                'data' => null,
                'key_facts' => null,
                'message' => 'No data found',
            ]);
        }

        $record['display_address'] = TrebPropertyHelper::formatDisplayAddress($record);
        $record['display_location'] = TrebPropertyHelper::formatLocationLine($record);

        $description = '';
        if ($property && $property->content) {
            $description = strip_tags((string) $property->content);
        } elseif (! empty($record['PublicRemarks'])) {
            $description = (string) $record['PublicRemarks'];
        }

        // Browsing must never block on AMP. History / rooms / remote images are
        // enrichment served by dedicated endpoints (listing-history,
        // property-rooms, getPropertyImages) that the detail page lazy-loads.
        // Here we only return what is instantly available (cache hit or local),
        // so the primary detail payload stays fast and local-first.
        $upperKey = strtoupper($listingKey);
        $authed = auth('account')->check() || auth()->check();
        $history = Cache::get('treb_listing_history_detail_v7_' . $upperKey . ($authed ? '_auth' : '_guest'), []);
        $rooms = Cache::get('treb_property_rooms_detail_v2_' . $upperKey, []);
        $images = TrebPropertyHelper::getPropertyImages($listingKey, $property->image_val ?? null, false);

        // SMART HYDRATION (Phase 1/2/7): if this local listing still has gaps
        // (missing image / coordinates / uncached history+rooms for the current
        // TREB version), complete them AFTER the response is sent so the detail
        // payload above is never blocked. Idempotent + checkpoint-guarded, so a
        // fully-hydrated listing does zero remote work.
        if ($property && $this->listingNeedsHydration($property)) {
            app(\Botble\RealEstate\Services\LiveTrebPropertyFallbackService::class)
                ->scheduleBackgroundImport($upperKey);
        }

        return response()->json([
            'success' => true,
            'data' => $record,
            'key_facts' => TrebPropertyHelper::buildKeyFacts($record, $local),
            'property_details' => TrebPropertyHelper::buildPropertyDetails($record, $local),
            'description' => $description,
            'listing_history' => is_array($history) ? $history : [],
            'rooms' => is_array($rooms) ? $rooms : [],
            'images' => $images,
            'property_id' => $property?->getKey(),
            'is_locked' => $isLocked,
        ]);
    }

    public function getPropertyRooms($listingKey)
    {
        $property = Property::where('external_id', $listingKey)->first();

        if ($property && $property->isSoldHistory() && ! (auth('account')->check() || auth()->check())) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthenticated',
                'data' => [],
            ], 401);
        }

        $rooms = TrebPropertyHelper::fetchPropertyRoomsForDetail($listingKey);

        return response()->json([
            'data' => $rooms,
            'count' => count($rooms),
        ]);
    }

    public function getListingHistory($listingKey)
    {
        $listingKey = strtoupper(trim((string) $listingKey));
        $property = Property::where('external_id', $listingKey)->first();

        if ($property && $property->isSoldHistory() && !(auth('account')->check() || auth()->check())) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthenticated',
                'data' => [],
            ], 401);
        }

        // On API requests AMP is allowed — pull same-unit siblings into local DB
        // so history can grow beyond the single current ListingKey.
        if (TrebPropertyHelper::canFetchRemoteAmp()) {
            try {
                TrebPropertyHelper::syncAddressHistoryForListing($listingKey);
            } catch (\Throwable $e) {
                report($e);
            }
        }

        $local = $property
            ? TrebPropertyHelper::dbRowToLocalArray($property->fresh() ?? $property)
            : TrebPropertyHelper::localPropertyArray($listingKey);

        $history = TrebPropertyHelper::fetchListingHistoryForDetail(
            $listingKey,
            $local,
            TrebPropertyHelper::resolveFactRecordForDetail($listingKey, $local)
        );

        return response()->json([
            'data' => $history,
            'count' => count($history),
        ]);
    }

    public function getPriceChanges($listingKey)
    {
        $property = Property::where('external_id', $listingKey)->first();

        if ($property && $property->isSoldHistory() && !(auth('account')->check() || auth()->check())) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthenticated',
                'data' => [],
            ], 401);
        }

        $changes = TrebPropertyHelper::fetchPriceChanges($listingKey);

        return response()->json([
            'data' => $changes,
            'count' => count($changes),
        ]);
    }

    /**
     * Lightweight batch thumbnail lookup for map list / cluster cards.
     * Keeping images out of the cold GeoJSON was measured to save ~1.7s on
     * a 3500-row viewport; this endpoint hydrates only the cards the user
     * can actually see (capped at 60).
     */
    public function getMapThumbnails(Request $request)
    {
        $ids = array_values(array_filter(array_map('intval', explode(',', (string) $request->input('ids', '')))));
        $keys = array_values(array_filter(array_map(static function ($v) {
            $v = strtoupper(trim((string) $v));

            return $v !== '' ? $v : null;
        }, explode(',', (string) $request->input('keys', '')))));

        if ($ids === [] && $keys === []) {
            return response()->json(['data' => (object) []]);
        }

        $ids = array_slice($ids, 0, 60);
        $keys = array_slice($keys, 0, 60);

        $query = Property::query()
            ->select(['id', 'external_id', 'image_val'])
            ->whereNotNull('image_val')
            ->where('image_val', '!=', '');

        if ($ids !== []) {
            $query->whereIn('id', $ids);
        } else {
            $query->whereIn('external_id', $keys);
        }

        $data = [];
        foreach ($query->get() as $row) {
            $img = \App\Support\SerikMediaUrl::toPublic((string) $row->image_val);
            if ($img === '') {
                continue;
            }
            $data[(string) $row->id] = $img;
            $data[strtoupper((string) $row->external_id)] = $img;
        }

        return response()->json(['data' => $data]);
    }

    public function getMapPropertyBundle($listingKey)
    {
        $property = Property::query()
            ->where(function ($query) use ($listingKey) {
                $query->where('external_id', $listingKey)
                    ->orWhere('external_id', strtoupper($listingKey));
            })
            ->first();

        if ($property && $property->isSoldHistory() && ! (auth('account')->check() || auth()->check())) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthenticated access to sold property details',
                'data' => null,
            ], 401);
        }

        $local = $property
            ? TrebPropertyHelper::dbRowToLocalArray($property)
            : TrebPropertyHelper::localPropertyArray($listingKey);

        $bundle = TrebPropertyHelper::fetchMapPopupBundle($listingKey, $local);

        if ($property) {
            $bundle['property_id'] = $property->getKey();
            $bundle['is_locked'] = $property->isSoldHistory() && ! (auth('account')->check() || auth()->check());
        }

        if (auth('account')->check()) {
            PropertyVisit::recordForAccount(
                (int) auth('account')->id(),
                $property,
                (string) $listingKey,
                'map',
                is_array($local) ? $local : null
            );
        }

        return response()->json($bundle);
    }


    public function getPropertyImages($listingKey)
    {
        $property = Property::where('external_id', $listingKey)->first();

        if ($property && $property->isSoldHistory() && !(auth('account')->check() || auth()->check())) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthenticated access to sold property images',
                'images' => [],
            ], 401);
        }

        $images = TrebPropertyHelper::getPropertyImages(
            $listingKey,
            $property->image_val ?? null
        );

        return response()->json([
            'images' => $images,
            'count' => count($images),
        ]);
    }


    public function getPropertyBasicDetails($listingKey)
    {
        $property = Property::where('external_id', $listingKey)->first();
        if ($property && $property->isSoldHistory() && !(auth('account')->check() || auth()->check())) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthenticated access to sold property details',
                'data' => null
            ], 401);
        }

        $cacheKey = 'treb_property_basic_' . $listingKey;
        if (Cache::has($cacheKey)) {
            return response()->json([
                'data' => Cache::get($cacheKey)
            ]);
        }

        $url = "https://query.ampre.ca/odata/Property?"
            . "\$filter=ListingKey%20eq%20%27{$listingKey}%27"
            . "&\$top=1"
            . "&\$select=City,CityRegion,PropertySubType,ListingContractDate,PurchaseContractDate,ClosePrice";

        // dd($url);
        $tokens = [
            'eyJhbGciOiJIUzI1NiJ9.eyJzdWIiOiJ2ZW5kb3IvdHJyZWIvMTAzMjQiLCJhdWQiOiJBbXBVc2Vyc1ByZCIsInJvbGVzIjpbIkFtcFZlbmRvciJdLCJpc3MiOiJwcm9kLmFtcHJlLmNhIiwiZXhwIjoyNTM0MDIzMDA3OTksImlhdCI6MTc2ODg2NzI4Miwic3ViamVjdFR5cGUiOiJ2ZW5kb3IiLCJzdWJqZWN0S2V5IjoiMTAzMjQiLCJqdGkiOiJjMDRkMzYwMDhlNzc0Zjc4IiwiY3VzdG9tZXJOYW1lIjoidHJyZWIifQ.IBqRgRDkr9eqSzkOqrYQN1m0V_difH0FqHRPM11vL9Y',
            'eyJhbGciOiJIUzI1NiJ9.eyJzdWIiOiJ2ZW5kb3IvdHJyZWIvMTAzMjQiLCJhdWQiOiJBbXBVc2Vyc1ByZCIsInJvbGVzIjpbIkFtcFZlbmRvciJdLCJpc3MiOiJwcm9kLmFtcHJlLmNhIiwiZXhwIjoyNTM0MDIzMDA3OTksImlhdCI6MTc3NTgyNzU2Mywic3ViamVjdFR5cGUiOiJ2ZW5kb3IiLCJzdWJqZWN0S2V5IjoiMTAzMjQiLCJqdGkiOiJjYTUyNDA2MzUxNDM2MDg4IiwiY3VzdG9tZXJOYW1lIjoidHJyZWIifQ.-DSLkpKUIymMWipYYNBmTfLA9SH58pToG-NhTWL-0rs',
        ];


        foreach ($tokens as $token) {

            $ch = curl_init($url);

            curl_setopt_array($ch, [
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_HTTPHEADER => [
                    "Authorization: Bearer {$token}",
                    'Accept: application/json',
                    'OData-Version: 4.0',
                    'OData-MaxVersion: 4.0',
                ],
            ]);

            $response = curl_exec($ch);

            if ($response === false) {
                curl_close($ch);
                continue; // try next token
            }

            curl_close($ch);

            $payload = json_decode($response, true);
            $item = $payload['value'][0] ?? null;

            // ✅ ONLY return if real data exists
            if (!empty($item)) {
                $basicData = [
                    'city' => $item['City'] ?? null,
                    'region' => $item['CityRegion'] ?? null,
                    'property_type' => $item['PropertySubType'] ?? null,
                    'listing_date' => $item['ListingContractDate'] ?? null,
                    'purchase_date' => $item['PurchaseContractDate'] ?? null,
                    'close_price' => $item['ClosePrice'] ?? null,
                ];
                Cache::put($cacheKey, $basicData, 86450);
                return response()->json([
                    'data' => $basicData
                ]);
            }

            // ❗ If empty → try next token
        }

        // ❌ If both tokens fail
        return response()->json([
            'data' => null,
            'message' => 'No data found with available tokens'
        ]);
    }

    public function getPropertyStatusDetails(Request $request)
    {
        $listingKey = $request->address;

        $property = Property::where('name', $listingKey)->orWhere('external_id', $listingKey)->first();
        if ($property && $property->isSoldHistory() && !(auth('account')->check() || auth()->check())) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthenticated',
                'data' => []
            ], 401);
        }

        $cacheKey = 'treb_status_details_' . md5((string) $listingKey);
        if (Cache::has($cacheKey)) {
            return response()->json([
                'data' => Cache::get($cacheKey)
            ]);
        }

        $escapedAddress = str_replace("'", "''", (string) $listingKey);
        $buildingKey = TrebPropertyHelper::normalizeBuildingAddress((string) $listingKey);

        $url = "https://query.ampre.ca/odata/Property?"
            . "\$filter=contains(UnparsedAddress,'{$escapedAddress}')"
            . "&\$select=ListingKey,ListPrice,PropertySubType,MlsStatus,TransactionType,ListingContractDate,ModificationTimestamp,UnparsedAddress,UnitNumber"
            . "&\$top=50";

        $response = $this->ampCurl($url);
        $items = $response['value'] ?? [];

        if (empty($items) && $buildingKey !== strtolower((string) $listingKey)) {
            $escapedBuilding = str_replace("'", "''", $buildingKey);
            $url = "https://query.ampre.ca/odata/Property?"
                . "\$filter=contains(UnparsedAddress,'{$escapedBuilding}')"
                . "&\$select=ListingKey,ListPrice,PropertySubType,MlsStatus,TransactionType,ListingContractDate,ModificationTimestamp,UnparsedAddress,UnitNumber"
                . "&\$top=50";
            $response = $this->ampCurl($url);
            $items = $response['value'] ?? [];
        }

        $mappedData = array_map(function ($item) {
            return [
                'listing_key' => $item['ListingKey'] ?? null,
                'status' => $item['MlsStatus'] ?? null,
                'transaction_type' => $item['TransactionType'] ?? null,
                'current_price' => $item['ListPrice'] ?? null,
                'original_price' => $item['OriginalListPrice'] ?? null,
                'updated_at' => $item['ModificationTimestamp'] ?? null,
                'date' => $item['ListingContractDate'] ?? null,
                'address' => $item['UnparsedAddress'] ?? null,
                'unit_number' => $item['UnitNumber'] ?? null,
            ];
        }, $items);

        usort($mappedData, function ($a, $b) {
            return strcmp((string) ($a['address'] ?? ''), (string) ($b['address'] ?? ''));
        });

        if (!empty($mappedData)) {
            Cache::put($cacheKey, $mappedData, 86450);
        }

        return response()->json([
            'data' => $mappedData,
            'unit_count' => count($mappedData),
            'grouped' => count($mappedData) > 1,
            'building_address' => TrebPropertyHelper::normalizeBuildingAddress((string) $listingKey),
        ]);
    }


    public function getPropertyStatusDetails1(Request $request)
    {
        $listingKey = $request->address;

        if (!$listingKey) {
            return response()->json([
                'success' => false,
                'message' => 'Address is required'
            ], 400);
        }

        $url = "https://query.ampre.ca/odata/Property?"
            . "\$filter=ListingKey eq '$listingKey'"
            . "&\$top=1";
        $tokens = TrebPropertyHelper::ampTokens();


        $response = null;
        $httpCode = null;

        foreach ($tokens as $token) {

            $ch = curl_init();

            curl_setopt_array($ch, [
                CURLOPT_URL => $url,
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_HTTPHEADER => [
                    "Authorization: Bearer $token",
                    "Accept: application/json",
                    "OData-Version: 4.0",
                    "OData-MaxVersion: 4.0",
                ],
            ]);

            $response = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);

            curl_close($ch);

            if ($httpCode == 200 && $response) {
                break;
            }
        }

        if (!$response) {
            return response()->json([
                'success' => false,
                'message' => 'Empty response from API'
            ]);
        }

        $payload = json_decode($response, true);

        $item = $payload['value'][0] ?? null;

        if (!$item) {
            return response()->json([
                'success' => false,
                'message' => 'No data found',
                'debug' => $payload
            ]);
        }

        return response()->json([
            'success' => true,
            'data' => $item
        ]);
    }

    public function trebRawData(Request $request)
    {
        if ($request->input('secret') !== 'proptex' && !(auth('account')->check() || auth()->check())) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthenticated access. Please login or provide correct credentials.'
            ], 401);
        }

        $listingKey = $request->input('listing_key');
        $address = $request->input('address');
        // AMPRE: $expand=Media requires $top <= 100
        $top = min(100, max(1, (int) $request->input('top', 5)));
        $filter = $request->input('filter');

        if ($listingKey) {
            $url = "https://query.ampre.ca/odata/Property?"
                . "\$filter=ListingKey eq '{$listingKey}'"
                . "&\$top=1"
                . "&\$expand=Media";
        } elseif ($address) {
            $url = "https://query.ampre.ca/odata/Property?"
                . "\$filter=contains(UnparsedAddress,'" . rawurlencode($address) . "')"
                . "&\$top=1"
                . "&\$expand=Media";
        } elseif ($filter) {
            $url = "https://query.ampre.ca/odata/Property?"
                . "\$filter=" . rawurlencode($filter)
                . "&\$top={$top}"
                . "&\$expand=Media";
        } else {
            $url = "https://query.ampre.ca/odata/Property?"
                . "\$top={$top}"
                . "&\$expand=Media";
        }

        $res = $this->ampCurl($url);

        return response()->json($res);
    }

}

