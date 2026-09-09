<?php

namespace Tests\Unit;

use App\Services\Treb\AmpLivePropertyBatchWriter;
use App\Support\PropertySearchSync;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class AmpLivePropertyBatchWriterTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Schema::dropIfExists('re_property_history');
        Schema::dropIfExists('re_properties');

        Schema::create('re_properties', function (Blueprint $table): void {
            $table->id();
            $table->string('external_id', 50)->unique();
            $table->string('unique_id')->nullable();
            $table->unsignedBigInteger('author_id')->nullable();
            $table->string('author_type')->nullable();
            $table->string('name', 300);
            $table->string('PropertySubType', 50)->default('sell');
            $table->string('description', 4000)->nullable();
            $table->longText('content')->nullable();
            $table->string('location')->nullable();
            $table->integer('number_bedroom')->nullable();
            $table->integer('number_bathroom')->nullable();
            $table->integer('number_floor')->nullable();
            $table->string('BedroomsBelowGrade', 2)->default('0');
            $table->string('broker', 500)->nullable();
            $table->string('square', 15)->nullable();
            $table->decimal('price', 15, 2)->nullable();
            $table->unsignedBigInteger('currency_id')->nullable();
            $table->boolean('is_featured')->default(0);
            $table->integer('featured_priority')->default(0);
            $table->string('status', 60)->default('selling');
            $table->string('moderation_status', 60)->default('approved');
            $table->string('expire_date')->nullable();
            $table->boolean('auto_renew')->default(1);
            $table->boolean('never_expired')->default(1);
            $table->string('TransactionType', 50)->default('For Sale');
            $table->string('MlsStatus')->nullable();
            $table->double('latitude')->default(0);
            $table->double('longitude')->default(0);
            $table->string('image_val', 1000)->nullable();
            $table->string('zip_code', 20)->nullable();
            $table->unsignedInteger('views')->default(0);
            $table->string('ParkingSpaces', 20)->nullable();
            $table->unsignedTinyInteger('CoveredSpaces')->nullable();
            $table->string('Basement', 500)->nullable();
            $table->integer('ClosePrice')->default(0);
            $table->dateTime('listing_contract_date')->nullable();
            $table->dateTime('listing_modified_at')->nullable();
            $table->dateTime('close_date')->nullable();
            $table->dateTime('purchase_contract_date')->nullable();
            $table->string('created_at')->nullable();
            $table->timestamp('updated_at')->nullable();
            $table->text('private_notes')->nullable();
            $table->string('period', 30)->default('month');
            $table->string('geocoding_status', 20)->default('pending');
            $table->unsignedBigInteger('country_id')->default(1);
        });

        Schema::create('re_property_history', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('property_id');
            $table->string('external_id')->nullable();
            $table->string('event')->nullable();
            $table->decimal('price', 15, 2)->nullable();
            $table->decimal('close_price', 15, 2)->nullable();
            $table->string('mls_status')->nullable();
            $table->string('transaction_type')->nullable();
            $table->string('status')->nullable();
            $table->dateTime('listing_contract_date')->nullable();
            $table->dateTime('listing_modified_at')->nullable();
            $table->dateTime('close_date')->nullable();
            $table->dateTime('purchase_contract_date')->nullable();
            $table->json('changed')->nullable();
            $table->json('snapshot')->nullable();
            $table->string('source')->nullable();
            $table->timestamp('recorded_at')->nullable();
            $table->timestamps();
        });
    }

    protected function tearDown(): void
    {
        Schema::dropIfExists('re_property_history');
        Schema::dropIfExists('re_properties');
        parent::tearDown();
    }

    public function test_batched_upsert_defaults_off(): void
    {
        $this->assertFalse((bool) config('serik.sync.batched_upsert'));
        $this->assertFalse(AmpLivePropertyBatchWriter::enabled());
    }

    public function test_batch_inserts_new_and_updates_existing_preserving_coords(): void
    {
        config(['serik.sync.batched_upsert' => true]);

        DB::table('re_properties')->insert([
            'external_id' => 'W10000001',
            'unique_id' => 'ABCDEFGHJK',
            'author_id' => 1,
            'author_type' => 'Botble\ACL\Models\User',
            'name' => 'Old Address',
            'PropertySubType' => 'Condo Apartment',
            'location' => 'Old Address',
            'price' => 500000,
            'MlsStatus' => 'Active',
            'TransactionType' => 'For Sale',
            'latitude' => 43.65,
            'longitude' => -79.38,
            'views' => 7,
            'moderation_status' => 'approved',
            'status' => 'selling',
            'listing_modified_at' => '2026-01-01 00:00:00',
            'created_at' => '2026-01-01 00:00:00',
            'updated_at' => '2026-01-01 00:00:00',
        ]);

        $sync = new PropertySearchSync();
        Cache::put(PropertySearchSync::PENDING_CACHE_KEY, []);

        $writer = new AmpLivePropertyBatchWriter($sync);

        $fixture = [
            // update existing
            [
                'ListingKey' => 'W10000001',
                'UnparsedAddress' => '123 King St W, Toronto, ON',
                'PropertySubType' => 'Condo Apartment',
                'PublicRemarks' => 'Updated remarks',
                'PrivateRemarks' => '',
                'BedroomsTotal' => 2,
                'BathroomsTotalInteger' => 2,
                'ListPrice' => 625000,
                'StandardStatus' => 'Active',
                'TransactionType' => 'For Sale',
                'MlsStatus' => 'Active',
                'PostalCode' => 'M5H1A1',
                'ModificationTimestamp' => '2026-09-01T12:00:00Z',
                'ListingContractDate' => '2026-01-01T00:00:00Z',
            ],
            // insert new
            [
                'ListingKey' => 'N20000002',
                'UnparsedAddress' => '10 Queen St E, Toronto, ON',
                'PropertySubType' => 'Detached',
                'PublicRemarks' => 'Brand new',
                'PrivateRemarks' => '',
                'BedroomsAboveGrade' => 3,
                'BathroomsTotalInteger' => 3,
                'ListPrice' => 999000,
                'StandardStatus' => 'Active',
                'TransactionType' => 'For Sale',
                'MlsStatus' => 'New',
                'PostalCode' => 'M5C1G6',
                'ModificationTimestamp' => '2026-09-02T12:00:00Z',
                'ListingContractDate' => '2026-09-02T00:00:00Z',
            ],
            // malformed / skipped
            [
                'ListingKey' => '',
                'UnparsedAddress' => 'Missing key',
            ],
        ];

        $result = $writer->write($fixture);

        $this->assertSame(1, $result['created']);
        $this->assertSame(1, $result['updated']);
        $this->assertSame(1, $result['skipped']);
        $this->assertCount(1, $result['new_ids']);
        $this->assertCount(2, $result['search_ids']);

        $updated = DB::table('re_properties')->where('external_id', 'W10000001')->first();
        $this->assertSame('123 King St W, Toronto, ON', $updated->name);
        $this->assertEquals(625000.0, (float) $updated->price);
        $this->assertEquals(43.65, (float) $updated->latitude);
        $this->assertEquals(-79.38, (float) $updated->longitude);
        $this->assertSame(7, (int) $updated->views);

        $created = DB::table('re_properties')->where('external_id', 'N20000002')->first();
        $this->assertNotNull($created);
        $this->assertSame('10 Queen St E, Toronto, ON', $created->name);
        $this->assertSame(3, (int) $created->number_bedroom);
        $this->assertEquals(0.0, (float) $created->latitude);

        $this->assertSame(2, (int) DB::table('re_property_history')->count());
    }

    public function test_map_item_matches_eloquent_fill_semantics_for_price_and_beds(): void
    {
        $writer = new AmpLivePropertyBatchWriter(new PropertySearchSync());

        $row = $writer->mapItemToRow([
            'ListingKey' => 'C30000003',
            'UnparsedAddress' => '1 Test Ave',
            'BedroomsTotal' => 4,
            'BedroomsBelowGrade' => 1,
            'BathroomsTotalInteger' => 2,
            'ListPrice' => '850000',
            'StandardStatus' => 'Active',
            'MlsStatus' => 'Active',
            'TransactionType' => 'For Sale',
            'PublicRemarks' => 'Hi',
        ], null);

        $this->assertNotNull($row);
        $this->assertSame(3, $row['number_bedroom']); // 4 total - 1 below
        $this->assertSame(850000.0, $row['price']);
        $this->assertSame('selling', $row['status']);
        $this->assertSame(0.0, $row['latitude']);
    }
}
