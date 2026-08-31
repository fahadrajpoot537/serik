<?php

namespace Tests\Unit;

use App\Http\Middleware\ApplyServiceInquiryFormContext;
use App\Support\PageH1;
use App\Support\ServiceInquiryFormContext;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Validator;
use Tests\TestCase;

class ServiceInquiryFormContextTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        RateLimiter::clear('service-inquiry-form:' . sha1('127.0.0.1|'));
        RateLimiter::clear('service-inquiry-form:' . sha1('127.0.0.1|jane@example.com'));
    }

    public function test_central_mapping_has_approved_subject_and_source(): void
    {
        $this->assertSame('Commercial Leasing Inquiry', ServiceInquiryFormContext::subjectFor('commercial_leasing'));
        $this->assertSame('Serik.ca - Commercial Leasing', ServiceInquiryFormContext::sourceFor('commercial_leasing'));
        $this->assertSame('Pre-Construction Inquiry', ServiceInquiryFormContext::subjectFor('pre_construction'));
        $this->assertSame('Serik.ca - Pre-Construction', ServiceInquiryFormContext::sourceFor('pre_construction'));
        $this->assertSame('Custom Home-Build Inquiry', ServiceInquiryFormContext::subjectFor('custom_home_build'));
        $this->assertSame('Serik.ca - Custom Home-Build', ServiceInquiryFormContext::sourceFor('custom_home_build'));
    }

    public function test_cards_are_matched_from_titles_not_client_urls(): void
    {
        $leasing = ServiceInquiryFormContext::resolveCard([
            'title' => 'Commercial Leasing ',
            'button_url' => '/agents/aju',
            'button_label' => 'Leasing Options',
        ]);
        $this->assertSame('commercial_leasing', $leasing['service_key']);
        $this->assertStringContainsString('serik_service_key=commercial_leasing', $leasing['button_url']);
        $this->assertStringContainsString('serik_form_context=service_inquiry', $leasing['button_url']);

        $showing = ServiceInquiryFormContext::resolveCard([
            'title' => 'Showing Agents',
            'button_url' => '/appointment-scheduler',
        ]);
        $this->assertArrayNotHasKey('service_key', $showing);
        $this->assertSame('/appointment-scheduler', $showing['button_url']);
    }

    public function test_tampered_subject_and_source_are_overwritten(): void
    {
        $request = Request::create('/contact/send', 'POST', [
            ServiceInquiryFormContext::REQUEST_CONTEXT_KEY => ServiceInquiryFormContext::CONTEXT_KEY,
            ServiceInquiryFormContext::REQUEST_SERVICE_KEY => 'pre_construction',
            'subject' => 'Hacked subject',
            'source' => 'evil',
            'ghl_source' => 'evil-id',
        ]);

        ServiceInquiryFormContext::applyTrustedRequestOverrides($request);

        $this->assertSame('Pre-Construction Inquiry', $request->input('subject'));
        $this->assertSame('pre_construction', $request->input('serik_service_key'));
        $this->assertNull($request->input('source'));
        $this->assertNull($request->input('ghl_source'));
    }

    public function test_invalid_service_key_is_rejected(): void
    {
        $this->assertNull(ServiceInquiryFormContext::validatedKey('hack'));
        $this->assertNull(ServiceInquiryFormContext::validatedKey('Commercial Leasing Inquiry'));

        $request = Request::create('/contact/send', 'POST', [
            ServiceInquiryFormContext::REQUEST_CONTEXT_KEY => ServiceInquiryFormContext::CONTEXT_KEY,
            ServiceInquiryFormContext::REQUEST_SERVICE_KEY => 'hack',
        ]);
        $this->assertFalse(ServiceInquiryFormContext::isActive($request));

        $rules = ServiceInquiryFormContext::applyValidationRules(['name' => ['required']], Request::create('/contact/send', 'POST', [
            ServiceInquiryFormContext::REQUEST_CONTEXT_KEY => ServiceInquiryFormContext::CONTEXT_KEY,
            ServiceInquiryFormContext::REQUEST_SERVICE_KEY => 'commercial_leasing',
        ]));
        $this->assertTrue(Validator::make(
            [ServiceInquiryFormContext::REQUEST_SERVICE_KEY => 'hack'],
            [ServiceInquiryFormContext::REQUEST_SERVICE_KEY => $rules[ServiceInquiryFormContext::REQUEST_SERVICE_KEY]]
        )->fails());
    }

    public function test_switching_keys_does_not_keep_stale_subject(): void
    {
        $first = Request::create('/contact/send', 'POST', [
            ServiceInquiryFormContext::REQUEST_CONTEXT_KEY => ServiceInquiryFormContext::CONTEXT_KEY,
            ServiceInquiryFormContext::REQUEST_SERVICE_KEY => 'commercial_leasing',
            'subject' => 'Commercial Leasing Inquiry',
        ]);
        ServiceInquiryFormContext::applyTrustedRequestOverrides($first);
        $this->assertSame('Commercial Leasing Inquiry', $first->input('subject'));

        $second = Request::create('/contact/send', 'POST', [
            ServiceInquiryFormContext::REQUEST_CONTEXT_KEY => ServiceInquiryFormContext::CONTEXT_KEY,
            ServiceInquiryFormContext::REQUEST_SERVICE_KEY => 'custom_home_build',
            'subject' => 'Commercial Leasing Inquiry',
        ]);
        ServiceInquiryFormContext::applyTrustedRequestOverrides($second);
        $this->assertSame('Custom Home-Build Inquiry', $second->input('subject'));
    }

    public function test_middleware_overwrites_subject_on_contact_send(): void
    {
        $middleware = new ApplyServiceInquiryFormContext();
        $request = Request::create('/contact/send', 'POST', [
            ServiceInquiryFormContext::REQUEST_CONTEXT_KEY => ServiceInquiryFormContext::CONTEXT_KEY,
            ServiceInquiryFormContext::REQUEST_SERVICE_KEY => 'commercial_leasing',
            'subject' => 'nope',
            'email' => 'jane@example.com',
        ]);
        $request->setRouteResolver(fn () => new class {
            public function named($name): bool
            {
                return $name === 'public.send.contact';
            }
        });

        $middleware->handle($request, fn ($req) => response('ok'));

        $this->assertSame('Commercial Leasing Inquiry', $request->input('subject'));
    }

    public function test_contact_h1_uses_service_subject(): void
    {
        $this->app->instance('request', Request::create('/contact-us', 'GET', [
            ServiceInquiryFormContext::REQUEST_CONTEXT_KEY => ServiceInquiryFormContext::CONTEXT_KEY,
            ServiceInquiryFormContext::REQUEST_SERVICE_KEY => 'pre_construction',
        ]));

        $this->assertSame('Pre-Construction Inquiry', PageH1::utilityH1ForSlug('contact-us'));
    }

    public function test_slider_pauses_on_hover_focus_and_reduced_motion(): void
    {
        $blade = file_get_contents(base_path('platform/themes/homzen/partials/shortcodes/services/styles/style-1.blade.php'));

        $this->assertStringContainsString('prefers-reduced-motion', $blade);
        $this->assertStringContainsString('pointerenter', $blade);
        $this->assertStringContainsString('focusin', $blade);
        $this->assertStringContainsString('serik-service-slider__prev', $blade);
        $this->assertStringContainsString('serik-service-slider__next', $blade);
        $this->assertStringContainsString('serik-service-slider__pause', $blade);
        $this->assertStringContainsString('serik-service-card__link', $blade);
        $this->assertStringContainsString('preventClicks: true', $blade);
        $this->assertStringContainsString('instance.destroy', $blade);
        $this->assertStringContainsString('dx > 8', $blade);
        $this->assertStringContainsString('data-serik-service-card', $blade);
        $this->assertStringNotContainsString('disableOnInteraction: false', $blade);
    }

    public function test_full_card_uses_one_real_anchor_without_nested_links(): void
    {
        $blade = file_get_contents(base_path('platform/themes/homzen/partials/shortcodes/services/styles/style-1.blade.php'));

        $this->assertStringContainsString('class="serik-service-card__link"', $blade);
        $this->assertStringContainsString('class="btn-view style-1 serik-service-card__cta"', $blade);
        $this->assertStringContainsString('aria-hidden="true"', $blade);
        $this->assertStringNotContainsString('<a href="{{ $service[\'button_url\'] }}" class="btn-view style-1">', $blade);
    }
}
