<?php

namespace Tests\Unit;

use App\Services\GoHighLevel\GoHighLevelLeadService;
use App\Support\ServiceInquiryFormContext;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class ServiceInquiryGoHighLevelLeadTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        config([
            'services.gohighlevel.enabled' => true,
            'services.gohighlevel.api_token' => 'test-token',
            'services.gohighlevel.location_id' => 'loc_test',
            'services.gohighlevel.base_url' => 'https://services.leadconnectorhq.com',
            'services.gohighlevel.api_version' => '2021-07-28',
            'gohighlevel.contact_forms.lead_source_field_id' => 'src_field_id',
            'gohighlevel.contact_forms.subject_field_id' => 'subj_field_id',
        ]);

        Cache::flush();
    }

    /**
     * @dataProvider serviceKeys
     */
    public function test_service_submission_uses_exact_subject_and_source(string $key, string $subject, string $source): void
    {
        $payload = $this->captureUpsertPayload(ServiceInquiryFormContext::buildGhlLead(
            'Jane Tester',
            'jane@example.com',
            '4165550100',
            'Please help',
            $key
        ));

        $this->assertSame($source, $payload['source']);
        $this->assertContains($source, $payload['tags']);
        $this->assertContains('Website Lead', $payload['tags']);
        $this->assertSame($source, $this->customFieldValue($payload, 'src_field_id'));
        $this->assertSame($subject, $this->customFieldValue($payload, 'subj_field_id'));
    }

    /**
     * @return array<string, list<string>>
     */
    public static function serviceKeys(): array
    {
        return [
            'commercial_leasing' => ['commercial_leasing', 'Commercial Leasing Inquiry', 'Serik.ca - Commercial Leasing'],
            'pre_construction' => ['pre_construction', 'Pre-Construction Inquiry', 'Serik.ca - Pre-Construction'],
            'custom_home_build' => ['custom_home_build', 'Custom Home-Build Inquiry', 'Serik.ca - Custom Home-Build'],
        ];
    }

    public function test_existing_tags_are_merged_and_blanks_are_omitted(): void
    {
        Http::fake(function ($request) {
            if (str_contains($request->url(), '/contacts/') && $request->method() === 'GET') {
                return Http::response([
                    'contacts' => [[
                        'id' => 'existing',
                        'email' => 'jane@example.com',
                        'tags' => ['Existing Tag', 'Website Lead'],
                    ]],
                ], 200);
            }

            return Http::response(['contact' => ['id' => 'existing']], 200);
        });

        $lead = ServiceInquiryFormContext::buildGhlLead(
            'Jane Tester',
            'jane@example.com',
            '',
            'Hello',
            'commercial_leasing'
        );

        app(GoHighLevelLeadService::class)->upsertLead($lead);

        Http::assertSent(function ($request) {
            if (! str_contains($request->url(), '/contacts/upsert')) {
                return true;
            }

            $data = $request->data();
            $tags = $data['tags'] ?? [];

            return in_array('Existing Tag', $tags, true)
                && in_array('Serik.ca - Commercial Leasing', $tags, true)
                && (! array_key_exists('phone', $data) || $data['phone'] === null || $data['phone'] === '');
        });
    }

    public function test_duplicate_submission_is_idempotent(): void
    {
        Http::fake([
            'services.leadconnectorhq.com/*' => Http::response(['contact' => ['id' => 'c1']], 200),
        ]);

        $lead = ServiceInquiryFormContext::buildGhlLead(
            'Jane Tester',
            'jane@example.com',
            '4165550100',
            'Hello',
            'pre_construction'
        );

        $service = app(GoHighLevelLeadService::class);
        $this->assertNotNull($service->upsertLead($lead));
        $this->assertNull($service->upsertLead($lead));
    }

    public function test_listener_routes_service_context(): void
    {
        $listener = file_get_contents(base_path('app/Listeners/PushContactLeadToGoHighLevel.php'));

        $this->assertStringContainsString('ServiceInquiryFormContext::activeKey()', $listener);
        $this->assertStringContainsString('ServiceInquiryFormContext::buildGhlLead(', $listener);
        $this->assertStringContainsString('AgentInquiryFormContext::buildGhlLead(', $listener);
        $this->assertStringContainsString("'source' => 'Contact Us Form — serik.ca'", $listener);
    }

    /**
     * @param  array<string, mixed>  $lead
     * @return array<string, mixed>
     */
    private function captureUpsertPayload(array $lead): array
    {
        $captured = [];

        Http::fake(function ($request) use (&$captured) {
            if (str_contains($request->url(), '/contacts/upsert')) {
                $captured = $request->data();
            }

            return Http::response(['contact' => ['id' => 'c1']], 200);
        });

        app(GoHighLevelLeadService::class)->upsertLead($lead);

        $this->assertNotSame([], $captured);

        return $captured;
    }

    private function customFieldValue(array $payload, string $id): mixed
    {
        foreach ($payload['customFields'] ?? $payload['custom_fields'] ?? [] as $field) {
            if (($field['id'] ?? null) === $id) {
                return $field['field_value'] ?? $field['value'] ?? null;
            }
        }

        return null;
    }
}
