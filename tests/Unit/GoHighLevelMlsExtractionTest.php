<?php

namespace Tests\Unit;

use App\Services\GoHighLevel\GoHighLevelMlsPendingService;
use Tests\TestCase;

class GoHighLevelMlsExtractionTest extends TestCase
{
    public function test_extracts_mls_from_ghl_contact_update_custom_field_id_only(): void
    {
        config([
            'gohighlevel.mls_sync.mls_field_key' => 'contact.mls_number',
            'gohighlevel.mls_sync.mls_field_id' => 'HsXi089pYk6OwbHXgUMf',
        ]);

        $pending = app(GoHighLevelMlsPendingService::class);

        $extracted = $pending->extractFromWebhookPayload([
            'type' => 'ContactUpdate',
            'locationId' => 'loc-1',
            'id' => 'contact-xyz',
            'customFields' => [
                ['id' => 'HsXi089pYk6OwbHXgUMf', 'value' => 'N12884704'],
            ],
        ]);

        $this->assertNotNull($extracted);
        $this->assertSame('contact-xyz', $extracted['contact_id']);
        $this->assertSame('N12884704', $extracted['mls_number']);
        $this->assertSame('loc-1', $extracted['location_id']);
    }

    public function test_extracts_mls_from_contact_api_custom_fields_id_only(): void
    {
        config([
            'gohighlevel.mls_sync.mls_field_id' => 'HsXi089pYk6OwbHXgUMf',
        ]);

        $pending = app(GoHighLevelMlsPendingService::class);

        $mls = $pending->extractMlsFromContact([
            'id' => 'contact-api',
            'customFields' => [
                ['id' => 'other', 'value' => 'nope'],
                ['id' => 'HsXi089pYk6OwbHXgUMf', 'value' => ['n12884704']],
            ],
        ]);

        $this->assertSame('N12884704', $mls);
    }

    public function test_preserves_legacy_field_key_extraction(): void
    {
        config(['gohighlevel.mls_sync.mls_field_id' => null]);

        $pending = app(GoHighLevelMlsPendingService::class);

        $extracted = $pending->extractFromWebhookPayload([
            'contact_id' => 'legacy-contact',
            'customFields' => [
                ['key' => 'contact.mls_number', 'value' => 'X111'],
            ],
        ]);

        $this->assertNotNull($extracted);
        $this->assertSame('X111', $extracted['mls_number']);
    }

    public function test_accepts_workflow_payload_with_full_name_as_contact_id(): void
    {
        $pending = app(GoHighLevelMlsPendingService::class);

        $extracted = $pending->extractFromWebhookPayload([
            'contact_id' => 'Jane Example',
            'mls_number' => 'N12884704',
        ]);

        $this->assertNotNull($extracted);
        $this->assertSame('Jane Example', $extracted['contact_id']);
        $this->assertSame('N12884704', $extracted['mls_number']);
    }

    public function test_accepts_mls_only_workflow_payload(): void
    {
        $pending = app(GoHighLevelMlsPendingService::class);

        $extracted = $pending->extractFromWebhookPayload([
            'mls_number' => 'N12884704',
        ]);

        $this->assertNotNull($extracted);
        $this->assertSame('', $extracted['contact_id']);
        $this->assertSame('N12884704', $extracted['mls_number']);
    }

    public function test_extracts_mls_from_custom_field_id_as_payload_key(): void
    {
        config(['gohighlevel.mls_sync.mls_field_id' => 'HsXi089pYk6OwbHXgUMf']);

        $pending = app(GoHighLevelMlsPendingService::class);

        $extracted = $pending->extractFromWebhookPayload([
            'Contact ID' => 'aHLNhFyN7yBpsZSqKQhd',
            'HsXi089pYk6OwbHXgUMf' => 'W13722054',
        ]);

        $this->assertNotNull($extracted);
        $this->assertSame('aHLNhFyN7yBpsZSqKQhd', $extracted['contact_id']);
        $this->assertSame('W13722054', $extracted['mls_number']);
    }

    public function test_extracts_mls_from_field_value_string(): void
    {
        config(['gohighlevel.mls_sync.mls_field_id' => 'HsXi089pYk6OwbHXgUMf']);

        $pending = app(GoHighLevelMlsPendingService::class);

        $extracted = $pending->extractFromWebhookPayload([
            'contactId' => 'aHLNhFyN7yBpsZSqKQhd',
            'customFields' => [
                ['id' => 'HsXi089pYk6OwbHXgUMf', 'fieldValueString' => 'W13722054'],
            ],
        ]);

        $this->assertNotNull($extracted);
        $this->assertSame('W13722054', $extracted['mls_number']);
    }

    public function test_extracts_from_wrapped_data_payload(): void
    {
        config(['gohighlevel.mls_sync.mls_field_id' => 'HsXi089pYk6OwbHXgUMf']);

        $pending = app(GoHighLevelMlsPendingService::class);

        $extracted = $pending->extractFromWebhookPayload([
            'data' => [
                'id' => 'aHLNhFyN7yBpsZSqKQhd',
                'customFields' => [
                    ['id' => 'HsXi089pYk6OwbHXgUMf', 'value' => 'W13722054'],
                ],
            ],
        ]);

        $this->assertNotNull($extracted);
        $this->assertSame('aHLNhFyN7yBpsZSqKQhd', $extracted['contact_id']);
        $this->assertSame('W13722054', $extracted['mls_number']);
    }

    public function test_extracts_contact_id_from_form_urlencoded_underscore_key(): void
    {
        config(['gohighlevel.mls_sync.mls_field_id' => 'HsXi089pYk6OwbHXgUMf']);

        $pending = app(GoHighLevelMlsPendingService::class);

        $extracted = $pending->extractFromWebhookPayload([
            'Contact_ID' => 'aHLNhFyN7yBpsZSqKQhd',
            'HsXi089pYk6OwbHXgUMf' => 'W13722054',
        ]);

        $this->assertNotNull($extracted);
        $this->assertSame('aHLNhFyN7yBpsZSqKQhd', $extracted['contact_id']);
        $this->assertSame('W13722054', $extracted['mls_number']);
    }

    public function test_extracts_showings_custom_object_webhook_payload(): void
    {
        $pending = app(GoHighLevelMlsPendingService::class);

        $extracted = $pending->extractFromWebhookPayload([
            'showing_record_id' => '6a9229e1cdc31ef7d18de59b',
            'mls_number' => 'N13483460',
            'location_id' => 'bZe1NETbUWb7znEqdzVU',
        ]);

        $this->assertNotNull($extracted);
        $this->assertSame('N13483460', $extracted['mls_number']);
        $this->assertSame('6a9229e1cdc31ef7d18de59b', $extracted['showing_record_id']);
        $this->assertSame('', $extracted['contact_id']);
        $this->assertSame('bZe1NETbUWb7znEqdzVU', $extracted['location_id']);
    }

    public function test_extracts_native_showings_object_event_without_treating_id_as_contact(): void
    {
        $pending = app(GoHighLevelMlsPendingService::class);

        $extracted = $pending->extractFromWebhookPayload([
            'type' => 'CustomObjectRecordUpdate',
            'objectKey' => 'custom_objects.showings',
            'locationId' => 'bZe1NETbUWb7znEqdzVU',
            'id' => '6a9229a5d87de2361ca31f47',
            'properties' => [
                'mls_number' => 'N13565154',
            ],
        ]);

        $this->assertNotNull($extracted);
        $this->assertSame('N13565154', $extracted['mls_number']);
        $this->assertSame('6a9229a5d87de2361ca31f47', $extracted['showing_record_id']);
        $this->assertSame('', $extracted['contact_id']);
    }

    public function test_showings_payload_prefers_object_mls_over_contact_custom_field(): void
    {
        config(['gohighlevel.mls_sync.mls_field_id' => 'HsXi089pYk6OwbHXgUMf']);

        $pending = app(GoHighLevelMlsPendingService::class);

        $extracted = $pending->extractFromWebhookPayload([
            'showing_record_id' => '6a9229e1cdc31ef7d18de59b',
            'properties' => ['mls_number' => 'N13483460'],
            'contact_id' => 'aHLNhFyN7yBpsZSqKQhd',
            'customFields' => [
                ['id' => 'HsXi089pYk6OwbHXgUMf', 'value' => 'W13709054'],
            ],
        ]);

        $this->assertNotNull($extracted);
        $this->assertSame('N13483460', $extracted['mls_number']);
        $this->assertSame('6a9229e1cdc31ef7d18de59b', $extracted['showing_record_id']);
        $this->assertSame('aHLNhFyN7yBpsZSqKQhd', $extracted['contact_id']);
    }

    public function test_contact_update_still_treats_top_level_id_as_contact(): void
    {
        config(['gohighlevel.mls_sync.mls_field_id' => 'HsXi089pYk6OwbHXgUMf']);

        $pending = app(GoHighLevelMlsPendingService::class);

        $extracted = $pending->extractFromWebhookPayload([
            'type' => 'ContactUpdate',
            'id' => 'aHLNhFyN7yBpsZSqKQhd',
            'customFields' => [
                ['id' => 'HsXi089pYk6OwbHXgUMf', 'value' => 'W13709054'],
            ],
        ]);

        $this->assertNotNull($extracted);
        $this->assertSame('aHLNhFyN7yBpsZSqKQhd', $extracted['contact_id']);
        $this->assertSame('', $extracted['showing_record_id']);
        $this->assertSame('W13709054', $extracted['mls_number']);
    }
}
