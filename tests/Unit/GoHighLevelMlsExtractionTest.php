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
}
