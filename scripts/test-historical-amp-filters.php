<?php

require __DIR__ . '/../vendor/autoload.php';
$app = require __DIR__ . '/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Botble\RealEstate\Http\Controllers\API\PropertyController;
use Illuminate\Support\Facades\Http;
use Theme\homzen\Supports\TrebPropertyHelper;

$token = TrebPropertyHelper::ampTokens()[0] ?? '';
$headers = [
    'Authorization' => 'Bearer ' . $token,
    'Accept' => 'application/json',
    'OData-Version' => '4.0',
    'OData-MaxVersion' => '4.0',
];

function testFilter(string $label, string $filter, array $headers): void
{
    $url = 'https://query.ampre.ca/odata/Property?$filter=' . rawurlencode($filter) . '&$top=3&$select=ListingKey,CloseDate,ListingContractDate,MlsStatus';
    $r = Http::timeout(30)->withHeaders($headers)->get($url);
    $count = count($r->json('value') ?? []);
    $err = $r->json('error.message') ?? '';
    echo "{$label}\n";
    echo "  HTTP {$r->status()} count={$count}" . ($err ? " error={$err}" : '') . "\n";
    if ($count > 0) {
        $first = $r->json('value.0');
        echo '  sample: ' . ($first['ListingKey'] ?? '?') . ' close=' . ($first['CloseDate'] ?? '?') . "\n";
    }
    echo "\n";
}

echo "=== Historical filter probe ===\n\n";
echo 'canFetchRemoteAmp: ' . (TrebPropertyHelper::canFetchRemoteAmp() ? 'yes' : 'NO') . "\n";
echo 'shouldSkipRemoteAmpFetch: ' . (TrebPropertyHelper::shouldSkipRemoteAmpFetch() ? 'yes' : 'no') . "\n\n";

// Field names on sample listing
$sampleUrl = 'https://query.ampre.ca/odata/Property?$filter=' . rawurlencode("ListingKey eq 'W13024458'") . '&$top=1';
$sample = Http::timeout(20)->withHeaders($headers)->get($sampleUrl)->json('value.0') ?? [];
foreach (['CloseDate', 'PurchaseContractDate', 'ListingContractDate', 'ModificationTimestamp', 'MlsStatus'] as $k) {
    echo "  {$k}=" . ($sample[$k] ?? 'MISSING') . "\n";
}
echo "\n";

// Working style from syncRecentSoldListings
testFilter('sold 120d (working style)', "CloseDate ge 2025-03-01 and year(CloseDate) ge 2000 and (MlsStatus eq 'Sold' or MlsStatus eq 'Leased')", $headers);

// Historical year range
testFilter('sold_close 2025 year range', "CloseDate ge 2025-01-01 and CloseDate le 2025-12-31 and (MlsStatus eq 'Sold' or MlsStatus eq 'Leased')", $headers);

testFilter('sold_close 2025 year() only', "year(CloseDate) eq 2025 and (MlsStatus eq 'Sold' or MlsStatus eq 'Leased')", $headers);

testFilter('listing_contract 2025', "ListingContractDate ge 2025-01-01 and ListingContractDate le 2025-12-31", $headers);

testFilter('listing_contract year 2025', "year(ListingContractDate) eq 2025", $headers);

testFilter('modification 2025', "ModificationTimestamp ge 2025-01-01T00:00:00Z and ModificationTimestamp le 2025-12-31T23:59:59Z", $headers);

testFilter('ModificationTimestamp ge only (cron style)', "ModificationTimestamp ge 2025-01-01T00:00:00Z", $headers);

testFilter('ListingContractDate ge only (cron style)', "ListingContractDate ge 2025-01-01", $headers);

testFilter('PurchaseContractDate ge 2025', "PurchaseContractDate ge 2025-01-01", $headers);

testFilter('year PurchaseContractDate 2025', "year(PurchaseContractDate) eq 2025", $headers);

testFilter('MlsStatus Sold only', "MlsStatus eq 'Sold'", $headers);

testFilter('StandardStatus Active', "StandardStatus eq 'Active'", $headers);

testFilter('mod + residential', "ModificationTimestamp ge 2025-01-01T00:00:00Z and ModificationTimestamp le 2025-12-31T23:59:59Z and PropertySubType ne 'Industrial' and PropertySubType ne 'Commercial Retail'", $headers);

testFilter('MlsStatus Sold', "MlsStatus eq 'Sold' and ModificationTimestamp ge 2020-01-01T00:00:00Z", $headers);

testFilter('MlsStatus Leased', "MlsStatus eq 'Leased' and ModificationTimestamp ge 2020-01-01T00:00:00Z", $headers);

testFilter('year ModificationTimestamp 2024', "year(ModificationTimestamp) eq 2024", $headers);

testFilter('OriginalEntryTimestamp', "OriginalEntryTimestamp ge 2024-01-01T00:00:00Z", $headers);

testFilter('with residential filter', "year(CloseDate) eq 2025 and (MlsStatus eq 'Sold' or MlsStatus eq 'Leased') and PropertySubType ne 'Industrial' and PropertySubType ne 'Commercial Retail'", $headers);

// Exact URL from importHistoricalAmpPage
$year = 2025;
$start = '2025-01-01';
$end = '2025-12-31';
$residential = "PropertySubType ne 'Industrial' and PropertySubType ne 'Commercial Retail'";
$filter = "ModificationTimestamp ge {$start}T00:00:00Z and ModificationTimestamp le {$end}T23:59:59Z and {$residential}";
$select = 'ListingKey,UnparsedAddress,PropertySubType,PublicRemarks,PrivateRemarks,'
    . 'BedroomsTotal,BedroomsAboveGrade,BedroomsBelowGrade,BathroomsTotalInteger,KitchensTotal,LivingAreaRange,'
    . 'StandardStatus,ExpirationDate,ListPrice,PostalCode,OriginalEntryTimestamp,ModificationTimestamp,'
    . 'PriceChangeTimestamp,TransactionType,MlsStatus,ListOfficeName,ListingContractDate,CloseDate,'
    . 'PurchaseContractDate,Basement,ParkingSpaces,CoveredSpaces,ClosePrice,StoriesTotal,Levels,ArchitecturalStyle';
$exactUrl = 'https://query.ampre.ca/odata/Property?$filter=' . rawurlencode($filter)
    . '&$select=' . $select . '&$top=5&$skip=0';
echo "Exact importHistorical URL test:\n";
$r = Http::timeout(45)->withHeaders($headers)->get($exactUrl);
echo '  HTTP ' . $r->status() . ' count=' . count($r->json('value') ?? []) . ' err=' . ($r->json('error.message') ?? '') . "\n\n";

$controller = app(PropertyController::class);
$result = $controller->importHistoricalAmpPage(2025, 0, 'modification', 5);
echo 'importHistoricalAmpPage modification: ' . json_encode($result) . "\n";

// syncRecentSoldListings test
$sold = $controller->syncRecentSoldListings(Illuminate\Http\Request::create('/', 'GET', ['days' => 30]));
echo 'syncRecentSoldListings 30d: ' . $sold->getContent() . "\n";
