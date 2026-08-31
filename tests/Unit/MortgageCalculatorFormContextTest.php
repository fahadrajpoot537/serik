<?php

namespace Tests\Unit;

use App\Http\Middleware\ApplyMortgageCalculatorFormContext;
use App\Support\MortgageCalculatorFormContext;
use App\Support\PageH1;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Validator;
use Symfony\Component\HttpFoundation\Response;
use Tests\TestCase;

class MortgageCalculatorFormContextTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        RateLimiter::clear('mortgage-calculator-form:' . sha1('127.0.0.1|'));
        RateLimiter::clear('mortgage-calculator-form:' . sha1('127.0.0.1|jane@example.com'));
    }

    public function test_calculator_cta_opens_mortgage_specific_form(): void
    {
        $calc = file_get_contents(base_path('platform/themes/homzen/partials/shortcodes/blog-posts/styles/style-2.blade.php'));

        $this->assertStringContainsString('id="serik-mortgage-prequal-cta"', $calc);
        $this->assertStringContainsString('Open mortgage pre-qualification form', $calc);
        $this->assertStringContainsString('MortgageCalculatorFormContext::contactUrl()', $calc);
        $this->assertStringContainsString('function calculatePayment(M, r, years, freq)', $calc);
        $this->assertStringContainsString(MortgageCalculatorFormContext::KEY, MortgageCalculatorFormContext::contactUrl());
        $this->assertStringContainsString('/contact-us', MortgageCalculatorFormContext::contactUrl());
    }

    public function test_form_does_not_display_generic_landlord_tenant_or_buyer_seller_options(): void
    {
        foreach (MortgageCalculatorFormContext::FORBIDDEN_INQUIRY_LABELS as $label) {
            $this->assertNotContains($label, MortgageCalculatorFormContext::INQUIRY_TYPES);
            $this->assertTrue(MortgageCalculatorFormContext::looksLikeGenericQualificationText($label));
        }

        $this->assertTrue(MortgageCalculatorFormContext::looksLikeGenericQualificationText(
            'Are you a Landlord or Tenant?'
        ));

        $functions = file_get_contents(base_path('platform/themes/homzen/functions/functions.php'));
        $this->assertStringContainsString('MortgageCalculatorFormContext::applyToContactForm', $functions);
        $this->assertStringNotContainsString("'Buyer', 'Seller', 'Tenant', 'Landlord'", $functions);
    }

    public function test_form_displays_mortgage_inquiry_type_with_exact_options(): void
    {
        $this->assertSame('Mortgage Inquiry Type', MortgageCalculatorFormContext::INQUIRY_LABEL);
        $this->assertSame(['New Mortgage', 'Refinance'], MortgageCalculatorFormContext::INQUIRY_TYPES);

        $context = file_get_contents(base_path('app/Support/MortgageCalculatorFormContext.php'));
        $this->assertStringContainsString('Mortgage Inquiry Type', $context);
        $this->assertStringContainsString('New Mortgage', $context);
        $this->assertStringContainsString('Refinance', $context);
    }

    public function test_inquiry_type_is_required_and_unapproved_values_are_rejected(): void
    {
        $request = $this->mortgageRequest(['mortgage_inquiry_type' => '']);
        $rules = MortgageCalculatorFormContext::applyValidationRules(['name' => ['required']], $request);

        $this->assertArrayHasKey('mortgage_inquiry_type', $rules);
        $this->assertContains('required', $rules['mortgage_inquiry_type']);
        $this->assertContains('string', $rules['mortgage_inquiry_type']);

        $validator = Validator::make(
            ['mortgage_inquiry_type' => ''],
            ['mortgage_inquiry_type' => $rules['mortgage_inquiry_type']]
        );
        $this->assertTrue($validator->fails());

        foreach (['Buyer', 'Seller', 'Tenant', 'Landlord', 'Other', 'hack'] as $bad) {
            $this->assertNull(MortgageCalculatorFormContext::validatedInquiryType($bad));
            $this->assertTrue(Validator::make(
                ['mortgage_inquiry_type' => $bad],
                ['mortgage_inquiry_type' => $rules['mortgage_inquiry_type']]
            )->fails());
        }

        foreach (MortgageCalculatorFormContext::INQUIRY_TYPES as $ok) {
            $this->assertSame($ok, MortgageCalculatorFormContext::validatedInquiryType($ok));
            $this->assertFalse(Validator::make(
                ['mortgage_inquiry_type' => $ok],
                ['mortgage_inquiry_type' => $rules['mortgage_inquiry_type']]
            )->fails());
        }
    }

    public function test_backend_enforces_subject_and_ignores_tampered_subject(): void
    {
        $request = $this->mortgageRequest([
            'subject' => 'Please ignore this fake subject',
            'source' => 'injected-source',
        ]);

        MortgageCalculatorFormContext::applyTrustedRequestOverrides($request);

        $this->assertSame(MortgageCalculatorFormContext::SUBJECT, $request->input('subject'));
        $this->assertSame(MortgageCalculatorFormContext::KEY, $request->input('serik_form_context'));
        $this->assertNull($request->input('source'));
        $this->assertSame('Mortgage Pre-Qualification Inquiry', MortgageCalculatorFormContext::SUBJECT);
    }

    public function test_backend_enforces_source_and_ignores_tampered_source(): void
    {
        $request = $this->mortgageRequest([
            'source' => 'not-the-mortgage-source',
            'ghl_source' => 'attacker',
        ]);

        MortgageCalculatorFormContext::applyTrustedRequestOverrides($request);

        $this->assertNull($request->input('source'));
        $this->assertNull($request->input('ghl_source'));

        $lead = MortgageCalculatorFormContext::buildGhlLead(
            'Jane Tester',
            ' Jane@Example.com ',
            '4165550100',
            'Need help',
            'Refinance'
        );

        $this->assertSame('Serik.ca - Mortgage Calculator', $lead['source']);
        $this->assertSame('Serik.ca - Mortgage Calculator', MortgageCalculatorFormContext::SOURCE);
        $this->assertNotSame('not-the-mortgage-source', $lead['source']);
        $this->assertSame('jane@example.com', $lead['email']);
    }

    public function test_overlay_replaces_generic_qualification_with_mortgage_inquiry_type(): void
    {
        $fields = MortgageCalculatorFormContext::overlayCustomFields([
            'Are you a Landlord or Tenant?' => 'Buyer',
            'Notes' => 'Keep this',
        ], 'New Mortgage');

        $this->assertSame('New Mortgage', $fields['Mortgage Inquiry Type']);
        $this->assertSame('Keep this', $fields['Notes']);
        $this->assertArrayNotHasKey('Are you a Landlord or Tenant?', $fields);
        $this->assertFalse(in_array('Buyer', $fields, true));
    }

    public function test_general_form_context_is_not_mortgage_and_does_not_leak(): void
    {
        $general = Request::create('/contact-us', 'GET');
        $this->assertFalse(MortgageCalculatorFormContext::isActive($general));

        $rules = MortgageCalculatorFormContext::applyValidationRules([
            'contact_custom_fields' => ['required', 'array'],
            'contact_custom_fields.1' => ['required', 'string'],
            'name' => ['required'],
        ], $general);

        $this->assertArrayHasKey('contact_custom_fields', $rules);
        $this->assertArrayNotHasKey('mortgage_inquiry_type', $rules);
        $this->assertSame(['required'], $rules['name']);

        $mortgage = $this->mortgageRequest();
        $this->assertTrue(MortgageCalculatorFormContext::isActive($mortgage));
        $this->assertFalse(MortgageCalculatorFormContext::isWhitelistedContext($general));

        $other = Request::create('/contact-us', 'GET', ['serik_form_context' => 'property_inquiry']);
        $this->assertFalse(MortgageCalculatorFormContext::isActive($other));
    }

    public function test_validation_consent_csrf_and_recaptcha_rules_remain_for_mortgage_context(): void
    {
        $request = $this->mortgageRequest(['_token' => 'abc', 'agree_terms_and_policy' => '1']);
        $rules = MortgageCalculatorFormContext::applyValidationRules([
            'name' => ['required', 'string'],
            'email' => ['required'],
            'agree_terms_and_policy' => ['required', 'accepted:1'],
            'g-recaptcha-response' => ['required', 'string'],
        ], $request);

        $this->assertSame(['required', 'accepted:1'], $rules['agree_terms_and_policy']);
        $this->assertSame(['required', 'string'], $rules['g-recaptcha-response']);
        $this->assertSame('abc', $request->input('_token'));

        $functions = file_get_contents(base_path('platform/themes/homzen/functions/functions.php'));
        $this->assertStringContainsString("'g-recaptcha-response'", $functions);
        $this->assertStringContainsString('RecaptchaHelper::verify', $functions);
    }

    public function test_page_h1_is_mortgage_specific_only_for_mortgage_context(): void
    {
        $general = Request::create('/contact-us', 'GET');
        $this->app->instance('request', $general);
        $this->assertSame('Contact Serik Realty', PageH1::utilityH1ForSlug('contact-us'));

        $mortgage = Request::create('/contact-us', 'GET', [
            MortgageCalculatorFormContext::REQUEST_CONTEXT_KEY => MortgageCalculatorFormContext::KEY,
        ]);
        $this->app->instance('request', $mortgage);
        $this->assertSame(MortgageCalculatorFormContext::SUBJECT, PageH1::utilityH1ForSlug('contact-us'));
    }

    public function test_middleware_overwrites_subject_and_rate_limits_mortgage_posts(): void
    {
        $middleware = new ApplyMortgageCalculatorFormContext();

        $request = Request::create('/contact/send', 'POST', [
            'serik_form_context' => MortgageCalculatorFormContext::KEY,
            'subject' => 'tampered',
            'email' => 'jane@example.com',
            'mortgage_inquiry_type' => 'New Mortgage',
        ]);

        $response = $middleware->handle($request, fn () => response('ok'));
        $this->assertSame(200, $response->getStatusCode());
        $this->assertSame(MortgageCalculatorFormContext::SUBJECT, $request->input('subject'));

        $headerRequest = Request::create('/contact-us', 'GET');
        $headerResponse = $middleware->handle($headerRequest, fn () => response('general'));
        $this->assertSame('general', $headerResponse->getContent());

        $email = 'jane@example.com';
        $limitKey = 'mortgage-calculator-form:' . sha1('127.0.0.1|' . $email);
        RateLimiter::clear($limitKey);

        $last = null;
        for ($i = 0; $i < 9; $i++) {
            $hit = Request::create('/contact/send', 'POST', [
                'serik_form_context' => MortgageCalculatorFormContext::KEY,
                'email' => $email,
                'mortgage_inquiry_type' => 'Refinance',
            ]);
            $last = $middleware->handle($hit, fn () => response('ok'));
        }

        $this->assertInstanceOf(Response::class, $last);
        $this->assertSame(429, $last->getStatusCode());
        $this->assertTrue($last->getData(true)['error']);
    }

    public function test_missing_ghl_custom_field_config_is_reported_and_values_are_not_discarded(): void
    {
        config([
            'gohighlevel.contact_forms.inquiry_type_field_id' => '',
            'gohighlevel.contact_forms.lead_source_field_id' => '',
            'gohighlevel.contact_forms.subject_field_id' => '',
        ]);

        $lead = MortgageCalculatorFormContext::buildGhlLead(
            'Jane Tester',
            'jane@example.com',
            '4165550100',
            'Hello',
            'New Mortgage'
        );

        $this->assertSame('New Mortgage', $lead['inquiry_type']);
        $this->assertSame(MortgageCalculatorFormContext::SUBJECT, $lead['subject']);
        $this->assertSame(MortgageCalculatorFormContext::SOURCE, $lead['source']);
        $this->assertSame([], $lead['custom_fields']);
        $this->assertContains(MortgageCalculatorFormContext::SOURCE, $lead['tags']);

        $config = file_get_contents(base_path('config/gohighlevel.php'));
        $this->assertStringContainsString('GOHIGHLEVEL_CONTACT_INQUIRY_TYPE_FIELD_ID', $config);
        $this->assertStringContainsString('GOHIGHLEVEL_CONTACT_LEAD_SOURCE_FIELD_ID', $config);
        $this->assertStringContainsString('GOHIGHLEVEL_CONTACT_SUBJECT_FIELD_ID', $config);
    }

    /**
     * @param  array<string, mixed>  $extra
     */
    private function mortgageRequest(array $extra = []): Request
    {
        return Request::create('/contact/send', 'POST', array_merge([
            MortgageCalculatorFormContext::REQUEST_CONTEXT_KEY => MortgageCalculatorFormContext::KEY,
        ], $extra));
    }
}
