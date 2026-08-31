<?php

namespace Tests\Unit;

use App\Services\GoHighLevel\GoHighLevelLeadService;
use App\Support\PhoneNumberNormalizer;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class ConsultScheduleViewingPhoneTest extends TestCase
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
        ]);
    }

    public function test_backend_consult_request_always_requires_and_normalizes_phone(): void
    {
        $request = file_get_contents(base_path('platform/plugins/real-estate/src/Http/Requests/SendConsultRequest.php'));

        $this->assertStringContainsString('ConsultPhoneNumber', $request);
        $this->assertStringContainsString('PhoneNumberNormalizer', $request);
        $this->assertStringContainsString("array_diff(\$hiddenFields, ['phone'])", $request);
        $this->assertStringContainsString('passedValidation', $request);
        $this->assertStringNotContainsString("'phone' => 'nullable|string|'", $request);
        $this->assertStringContainsString('PhoneNumberNormalizer::REQUIRED_MESSAGE', $request);
        $this->assertStringContainsString('PhoneNumberNormalizer::INVALID_MESSAGE', $request);
    }

    public function test_book_a_tour_and_schedule_viewing_markup_require_phone(): void
    {
        $form = file_get_contents(base_path('platform/plugins/real-estate/src/Forms/Fronts/ConsultForm.php'));
        $contact = file_get_contents(base_path('platform/themes/homzen/views/real-estate/single-layouts/partials/contact.blade.php'));
        $map = file_get_contents(base_path('platform/themes/homzen/partials/shortcodes/hero-banner/styles/style-4.blade.php'));
        $script = file_get_contents(base_path('platform/themes/homzen/assets/js/script.js'));

        $this->assertStringContainsString('TelField::class', $form);
        $this->assertStringContainsString('->required()', $form);
        $this->assertStringContainsString('wrapperAttributes', $form);
        $this->assertStringContainsString("'autocomplete', 'tel'", $form);
        $this->assertStringContainsString("'inputmode', 'tel'", $form);
        $this->assertStringContainsString('serik-schedule-viewing', $contact);
        $this->assertStringContainsString('Schedule Viewing', $contact);

        $this->assertStringContainsString('type="tel"', $map);
        $this->assertStringContainsString('autocomplete="tel"', $map);
        $this->assertStringContainsString('inputmode="tel"', $map);
        $this->assertStringContainsString('aria-required="true"', $map);
        $this->assertStringContainsString('Phone number is required.', $map);
        $this->assertStringContainsString('Please enter a valid phone number.', $map);
        $this->assertStringContainsString("form.dataset.serikSubmitting === '1'", $map);
        $this->assertStringContainsString("headers: { 'X-CSRF-TOKEN': csrf, 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' }", $map);

        $this->assertStringContainsString('serikConsultPhoneLooksValid', $script);
        $this->assertStringContainsString("\$form.data('serikSubmitting')", $script);
        $this->assertStringContainsString('serik-consult-phone-error', $script);
        $this->assertStringContainsString('aria-invalid', $script);
        $this->assertStringContainsString('aria-describedby', $script);
    }

    public function test_unrelated_forms_keep_existing_phone_rules(): void
    {
        $contact = file_get_contents(base_path('platform/plugins/contact/src/Http/Requests/ContactRequest.php'));
        $appointment = file_get_contents(base_path('app/Support/AppointmentScheduler.php'));
        $register = file_get_contents(base_path('platform/plugins/real-estate/src/Http/Requests/Fronts/Auth/RegisterRequest.php'));

        $this->assertStringContainsString("'phone' => ['nullable', new PhoneNumberRule()]", $contact);
        $this->assertStringContainsString("'phone' => ['required', 'string', 'max:50']", $appointment);
        $this->assertStringNotContainsString('ConsultPhoneNumber', $contact);
        $this->assertStringNotContainsString('ConsultPhoneNumber', $appointment);
        $this->assertStringNotContainsString('ConsultPhoneNumber', $register);
    }

    public function test_normalized_phone_is_sent_to_gohighlevel_and_invalid_phone_is_not(): void
    {
        $listener = file_get_contents(base_path('app/Listeners/PushConsultLeadToGoHighLevel.php'));
        $this->assertStringContainsString('PhoneNumberNormalizer::normalize', $listener);
        $this->assertStringContainsString("'Schedule Viewing'", $listener);
        $this->assertStringContainsString("'Property Inquiry'", $listener);

        $this->assertSame('+14165550123', PhoneNumberNormalizer::normalize('(416) 555-0123'));
        $this->assertNull(PhoneNumberNormalizer::normalize('123'));
        $this->assertNull(PhoneNumberNormalizer::normalize(''));
    }

    public function test_map_and_detail_source_tags_remain_unchanged(): void
    {
        $controller = file_get_contents(base_path('platform/plugins/real-estate/src/Http/Controllers/Fronts/PublicController.php'));
        $api = file_get_contents(base_path('platform/plugins/real-estate/src/Http/Controllers/API/ConsultController.php'));

        foreach ([$controller, $api] as $source) {
            $this->assertStringContainsString('Property Schedule / Inquiry (Map) — serik.ca', $source);
            $this->assertStringContainsString('Property Schedule / Inquiry (Detail) — serik.ca', $source);
            $this->assertStringContainsString('Map Inquiry Form', $source);
            $this->assertStringContainsString('Property Detail Inquiry Form', $source);
            $this->assertStringContainsString('Consult::query()->create($data)', $source);
        }
    }

    public function test_ghl_upsert_omits_empty_phone_and_does_not_overwrite_with_blank(): void
    {
        $captured = [];

        Http::fake(function ($request) use (&$captured) {
            if (str_contains($request->url(), '/contacts/upsert')) {
                $captured = $request->data();
            }

            return Http::response(['contact' => ['id' => 'existing-1']], 200);
        });

        app(GoHighLevelLeadService::class)->upsertLead([
            'name' => 'Jane Tester',
            'email' => 'jane@example.com',
            'phone' => '',
            'source' => 'Property Schedule / Inquiry (Detail) — serik.ca',
            'tags' => ['Website Lead'],
        ]);

        $this->assertSame('jane@example.com', $captured['email'] ?? null);
        $this->assertArrayNotHasKey('phone', $captured);
    }

    public function test_ghl_http_payload_uses_normalized_e164_phone(): void
    {
        $capturedLead = [
            'name' => 'Jane Tester',
            'email' => 'jane@example.com',
            'phone' => PhoneNumberNormalizer::normalize('(416) 555-0123'),
            'message' => 'Tour please',
            'property_name' => '123 Main St',
            'property_url' => 'https://serik.ca/properties/123-main',
            'source' => 'Property Schedule / Inquiry (Map) — serik.ca',
            'tags' => ['Website Lead', 'Property Inquiry', 'Schedule Viewing', 'Map Inquiry Form', 'Serik Realty'],
        ];

        $this->assertSame('+14165550123', $capturedLead['phone']);

        $httpPayload = [];
        Http::fake(function ($request) use (&$httpPayload) {
            if (str_contains($request->url(), '/contacts/upsert')) {
                $httpPayload = $request->data();
            }

            return Http::response(['contact' => ['id' => 'c1']], 200);
        });

        app(GoHighLevelLeadService::class)->upsertLead($capturedLead);

        $this->assertSame('+14165550123', $httpPayload['phone'] ?? null);
        $this->assertSame('Property Schedule / Inquiry (Map) — serik.ca', $httpPayload['source'] ?? null);
        $this->assertContains('Schedule Viewing', $httpPayload['tags'] ?? []);
        $this->assertContains('Map Inquiry Form', $httpPayload['tags'] ?? []);
        $this->assertSame('jane@example.com', $httpPayload['email'] ?? null);
    }

    public function test_invalid_phone_never_reaches_crm_payload(): void
    {
        $this->assertNull(PhoneNumberNormalizer::normalize(''));
        $this->assertNull(PhoneNumberNormalizer::normalize('123'));

        $controller = file_get_contents(base_path('platform/plugins/real-estate/src/Http/Controllers/Fronts/PublicController.php'));
        $this->assertStringContainsString('SendConsultRequest $request', $controller);
        $this->assertTrue(strpos($controller, 'SendConsultRequest $request') < strpos($controller, 'Consult::query()->create($data)'));
        $this->assertTrue(strpos($controller, 'Consult::query()->create($data)') < strpos($controller, 'ConsultSubmitted'));
        $this->assertTrue(strpos($controller, 'ConsultSubmitted') < strpos($controller, "sendUsingTemplate('notice'"));
    }

    public function test_ajax_contract_still_returns_field_errors_for_phone(): void
    {
        $script = file_get_contents(base_path('platform/themes/homzen/assets/js/script.js'));
        $map = file_get_contents(base_path('platform/themes/homzen/partials/shortcodes/hero-banner/styles/style-4.blade.php'));

        $this->assertStringContainsString('error.responseJSON && error.responseJSON.errors', $script);
        $this->assertStringContainsString('errors.phone', $script);
        $this->assertStringContainsString('data?.errors?.phone', $map);
        $this->assertStringContainsString("Accept': 'application/json'", $map);
    }
}
