<?php
require __DIR__ . '/../vendor/autoload.php';
$app = require __DIR__ . '/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$key = 'W13024458';
foreach ([
    "https://query.ampre.ca/odata/PropertyRooms?\$filter=ListingKey eq '{$key}'&\$top=40",
    "https://query.ampre.ca/odata/PropertyRooms?\$filter=ResourceRecordKey eq '{$key}'&\$top=40",
] as $url) {
    $p = Theme\homzen\Supports\TrebPropertyHelper::ampGetFresh($url);
    echo $url . "\n count=" . count($p['value'] ?? []) . "\n";
    if (!empty($p['value'][0])) {
        echo json_encode($p['value'][0], JSON_PRETTY_PRINT) . "\n";
    }
}

$record = Theme\homzen\Supports\TrebPropertyHelper::fetchAmpPropertyForResync($key);
$street = $record['StreetNumber'] ?? '';
$name = $record['StreetName'] ?? '';
$url = 'https://query.ampre.ca/odata/Property?$filter=' . rawurlencode("StreetNumber eq '{$street}' and StreetName eq '{$name}'") . '&$orderby=ListingContractDate desc&$top=50';
$p = Theme\homzen\Supports\TrebPropertyHelper::ampGetFresh($url);
echo "address history count=" . count($p['value'] ?? []) . "\n";
foreach ($p['value'] ?? [] as $item) {
    echo ($item['ListingKey'] ?? '-') . ' ' . ($item['MlsStatus'] ?? '-') . ' ' . ($item['ListPrice'] ?? '-') . "\n";
}

$histUrl = 'https://query.ampre.ca/odata/HistoryTransactional?$filter=' . rawurlencode("ResourceRecordKey eq '{$key}'") . '&$top=20';
$hist = Theme\homzen\Supports\TrebPropertyHelper::ampGetFresh($histUrl);
echo "HistoryTransactional count=" . count($hist['value'] ?? []) . "\n";

$addrUrl = 'https://query.ampre.ca/odata/Property?$filter=' . rawurlencode("contains(UnparsedAddress,'222 Simmons')") . '&$orderby=ListingContractDate desc&$top=50';
$addr = Theme\homzen\Supports\TrebPropertyHelper::ampGetFresh($addrUrl);
echo "contains address count=" . count($addr['value'] ?? []) . "\n";
foreach ($addr['value'] ?? [] as $item) {
    echo ($item['ListingKey'] ?? '-') . ' ' . ($item['MlsStatus'] ?? '-') . "\n";
}

$local = DB::table('re_properties')->where('name', 'like', '%222 Simmons%')->get(['external_id','MlsStatus']);
echo "local DB count=" . $local->count() . "\n";
