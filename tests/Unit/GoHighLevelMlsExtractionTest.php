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
}
