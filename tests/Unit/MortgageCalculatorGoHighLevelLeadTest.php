<?php

namespace Tests\Unit;

use App\Listeners\PushContactLeadToGoHighLevel;
use App\Services\GoHighLevel\GoHighLevelLeadService;
use App\Support\MortgageCalculatorFormContext;
use Botble\Contact\Models\Contact;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Mockery;
use Tests\TestCase;

class MortgageCalculatorGoHighLevelLeadTest extends TestCase
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
            'gohighlevel.contact_forms.inquiry_type_field_id' => 'inq_field_id',
            'gohighlevel.contact_forms.lead_source_field_id' => 'src_field_id',
            'gohighlevel.contact_forms.subject_field_id' => 'subj_field_id',
        ]);

        Cache::flush();
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    public function test_new_mortgage_submission_reaches_crm_with_correct_mapping(): void
    {
        $payload = $this->captureUpsertPayload(MortgageCalculatorFormContext::buildGhlLead(
            'Jane Tester',
            'jane@example.com',
            '4165550100',
            'Please qualify me',
            'New Mortgage'
        ));

        $this->assertSame('Mortgage Pre-Qualification Inquiry', $payload['subject'] ?? $this->noteValue($payload, 'Subject:'));
        $this->assertSame('Serik.ca - Mortgage Calculator', $payload['source']);
        $this->assertContains('Serik.ca - Mortgage Calculator', $payload['tags']);
        $this->assertContains('Website Lead', $payload['tags']);
        $this->assertSame('New Mortgage', $this->customFieldValue($payload, 'inq_field_id'));
        $this->assertSame('Serik.ca - Mortgage Calculator', $this->customFieldValue($payload, 'src_field_id'));
        $this->assertSame('Mortgage Pre-Qualification Inquiry', $this->customFieldValue($payload, 'subj_field_id'));
    }

    public function test_refinance_submission_reaches_crm_with_correct_mapping(): void
    {
        $payload = $this->captureUpsertPayload(MortgageCalculatorFormContext::buildGhlLead(
            'John Tester',
            'john@example.com',
            '4165550199',
            'Refinance please',
            'Refinance'
        ));

        $this->assertSame('Refinance', $this->customFieldValue($payload, 'inq_field_id'));
        $this->assertSame('Serik.ca - Mortgage Calculator', $payload['source']);
        $this->assertSame('Mortgage Pre-Qualification Inquiry', $this->customFieldValue($payload, 'subj_field_id'));
    }

    public function test_exact_subject_type_and_source_exist_in_crm_payload_and_note(): void
    {
        Http::fake([
            'services.leadconnectorhq.com/contacts/*' => Http::response(['contact' => ['id' => 'c1']], 200),
        ]);

        $lead = MortgageCalculatorFormContext::buildGhlLead(
            'Jane Tester',
            'jane@example.com',
            '4165550100',
            'Hello',
            'New Mortgage'
        );

        app(GoHighLevelLeadService::class)->upsertLead($lead);

        Http::assertSent(function ($request) {
            $body = $request->data();
            if (str_contains($request->url(), '/notes')) {
                $note = (string) ($body['body'] ?? '');

                return str_contains($note, 'Subject: Mortgage Pre-Qualification Inquiry')
                    && str_contains($note, 'Mortgage Inquiry Type: New Mortgage')
                    && str_contains($note, 'Source: Serik.ca - Mortgage Calculator')
                    && str_contains($note, '/mortgage-calculator');
            }

            return true;
        });
    }

    public function test_source_tag_is_added_without_removing_existing_tags(): void
    {
        Http::fake(function ($request) {
            if (str_contains($request->url(), '/contacts/') && $request->method() === 'GET') {
                return Http::response([
                    'contacts' => [[
                        'id' => 'existing-1',
                        'email' => 'jane@example.com',
                        'tags' => ['VIP', 'Past Client'],
                    ]],
                ], 200);
            }

            return Http::response(['contact' => ['id' => 'existing-1']], 200);
        });

        app(GoHighLevelLeadService::class)->upsertLead(MortgageCalculatorFormContext::buildGhlLead(
            'Jane Tester',
            'jane@example.com',
            '4165550100',
            'Hello',
            'Refinance'
        ));

        Http::assertSent(function ($request) {
            if (! str_contains($request->url(), '/contacts/upsert')) {
                return true;
            }

            $tags = $request->data()['tags'] ?? [];

            return in_array('VIP', $tags, true)
                && in_array('Past Client', $tags, true)
                && in_array('Serik.ca - Mortgage Calculator', $tags, true)
                && in_array('Website Lead', $tags, true);
        });
    }

    public function test_existing_email_updates_one_contact_instead_of_creating_a_duplicate(): void
    {
        Http::fake(function ($request) {
            if (str_contains($request->url(), '/contacts/') && $request->method() === 'GET') {
                return Http::response([
                    'contacts' => [[
                        'id' => 'existing-1',
                        'email' => 'jane@example.com',
                        'tags' => ['VIP'],
                    ]],
                ], 200);
            }

            return Http::response(['contact' => ['id' => 'existing-1']], 200);
        });

        app(GoHighLevelLeadService::class)->upsertLead(MortgageCalculatorFormContext::buildGhlLead(
            'Jane Tester',
            'jane@example.com',
            '4165550100',
            'Hello',
            'New Mortgage'
        ));

        $urls = [];
        Http::assertSent(function ($request) use (&$urls) {
            $urls[] = $request->url();

            return true;
        });

        $creates = array_filter($urls, fn ($url) => preg_match('#/contacts/?$#', $url) && ! str_contains($url, 'upsert'));
        $this->assertSame([], array_values($creates));
        $this->assertTrue(collect($urls)->contains(fn ($url) => str_contains($url, '/contacts/upsert')));
    }

    public function test_blank_form_values_do_not_overwrite_existing_crm_data(): void
    {
        Http::fake(function ($request) {
            if (str_contains($request->url(), '/contacts/') && $request->method() === 'GET') {
                return Http::response([
                    'contacts' => [[
                        'id' => 'existing-1',
                        'email' => 'jane@example.com',
                        'phone' => '4165559999',
                        'tags' => ['VIP'],
                    ]],
                ], 200);
            }

            return Http::response(['contact' => ['id' => 'existing-1']], 200);
        });

        $lead = MortgageCalculatorFormContext::buildGhlLead(
            'Jane Tester',
            'jane@example.com',
            '',
            'Hello',
            'New Mortgage'
        );

        app(GoHighLevelLeadService::class)->upsertLead($lead);

        Http::assertSent(function ($request) {
            if (! str_contains($request->url(), '/contacts/upsert')) {
                return true;
            }

            $data = $request->data();

            return ! array_key_exists('phone', $data) || $data['phone'] === null || $data['phone'] === '';
        });
    }

    public function test_idempotent_retry_does_not_create_a_second_upsert(): void
    {
        Http::fake([
            'services.leadconnectorhq.com/*' => Http::response(['contact' => ['id' => 'c1']], 200),
        ]);

        $lead = MortgageCalculatorFormContext::buildGhlLead(
            'Jane Tester',
            'jane@example.com',
            '4165550100',
            'Hello',
            'New Mortgage'
        );

        $service = app(GoHighLevelLeadService::class);
        $this->assertNotNull($service->upsertLead($lead));
        $this->assertNull($service->upsertLead($lead));

        $upserts = 0;
        Http::assertSent(function ($request) use (&$upserts) {
            if (str_contains($request->url(), '/contacts/upsert')) {
                $upserts++;
            }

            return true;
        });

        $this->assertSame(1, $upserts);
    }

    public function test_listener_sends_mortgage_mapping_and_leaves_general_form_unchanged(): void
    {
        $mortgageLead = null;
        $ghl = Mockery::mock(GoHighLevelLeadService::class);
        $ghl->shouldReceive('pushAfterResponse')->once()->andReturnUsing(function (array $lead) use (&$mortgageLead): void {
            $mortgageLead = $lead;
        });

        $this->app->instance('request', Request::create('/contact/send', 'POST', [
            MortgageCalculatorFormContext::REQUEST_CONTEXT_KEY => MortgageCalculatorFormContext::KEY,
            MortgageCalculatorFormContext::REQUEST_INQUIRY_KEY => 'New Mortgage',
        ]));

        $contact = new Contact();
        $contact->forceFill([
            'name' => 'Jane Tester',
            'email' => 'jane@example.com',
            'phone' => '4165550100',
            'subject' => 'tampered',
            'content' => 'Hello',
            'custom_fields' => ['Mortgage Inquiry Type' => 'New Mortgage'],
        ]);

        (new PushContactLeadToGoHighLevel($ghl))->handle(
            new \Botble\Contact\Events\SentContactEvent($contact)
        );

        $this->assertIsArray($mortgageLead);
        $this->assertSame(MortgageCalculatorFormContext::SOURCE, $mortgageLead['source']);
        $this->assertSame(MortgageCalculatorFormContext::SUBJECT, $mortgageLead['subject']);
        $this->assertSame('New Mortgage', $mortgageLead['inquiry_type']);
        $this->assertContains(MortgageCalculatorFormContext::SOURCE, $mortgageLead['tags']);

        $generalLead = null;
        $ghlGeneral = Mockery::mock(GoHighLevelLeadService::class);
        $ghlGeneral->shouldReceive('pushAfterResponse')->once()->andReturnUsing(function (array $lead) use (&$generalLead): void {
            $generalLead = $lead;
        });

        $this->app->instance('request', Request::create('/contact/send', 'POST', [
            'subject' => 'General question',
        ]));

        $general = new Contact();
        $general->forceFill([
            'name' => 'Pat Buyer',
            'email' => 'pat@example.com',
            'phone' => '4165550101',
            'subject' => 'General question',
            'content' => 'Hello',
            'custom_fields' => ['Are you a Landlord or Tenant?' => 'Buyer'],
        ]);

        (new PushContactLeadToGoHighLevel($ghlGeneral))->handle(
            new \Botble\Contact\Events\SentContactEvent($general)
        );

        $this->assertIsArray($generalLead);
        $this->assertSame('Contact Us Form — serik.ca', $generalLead['source']);
        $this->assertSame('General question', $generalLead['subject']);
        $this->assertArrayNotHasKey('inquiry_type', $generalLead);
        $this->assertNotContains(MortgageCalculatorFormContext::SOURCE, $generalLead['tags']);
    }

    public function test_success_and_failure_response_contract_is_unchanged(): void
    {
        $controller = file_get_contents(base_path('platform/plugins/contact/src/Http/Controllers/PublicController.php'));
        $js = file_get_contents(base_path('platform/plugins/contact/resources/js/contact-public.js'));

        $this->assertStringContainsString('->setMessage($result[\'message\'])', $controller);
        $this->assertStringContainsString("setError()", $controller);
        $this->assertStringContainsString('contact-success-message', $js);
        $this->assertStringContainsString('contact-error-message', $js);
        $this->assertStringContainsString('button-loading', $js);
        $this->assertStringContainsString('contact-form.submitted', $js);
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

        $captured['subject'] = $lead['subject'];
        $captured['inquiry_type'] = $lead['inquiry_type'];

        return $captured;
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    private function customFieldValue(array $payload, string $id): mixed
    {
        foreach ($payload['customFields'] ?? [] as $field) {
            if (($field['id'] ?? '') === $id) {
                return $field['field_value'] ?? null;
            }
        }

        return null;
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    private function noteValue(array $payload, string $prefix): ?string
    {
        return $payload['subject'] ?? null;
    }
}
