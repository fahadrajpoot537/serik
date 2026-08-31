<?php

namespace Tests\Unit;

use App\Support\AgentProfile;
use App\Support\PageH1;
use Tests\TestCase;

class ServicesFaqTypographyTest extends TestCase
{
    public function test_services_and_faq_share_role_tokens(): void
    {
        $chrome = file_get_contents(base_path('platform/themes/homzen/public/css/site-chrome.css'));

        foreach ([
            '--serik-type-h1',
            '--serik-type-h2',
            '--serik-type-h3',
            '--serik-type-body',
            '--serik-type-label',
            '--serik-type-cta',
            '--serik-type-breadcrumb',
            '#page-faqs',
            '#page-our-services',
        ] as $token) {
            $this->assertStringContainsString($token, $chrome);
        }

        $this->assertStringContainsString('#page-faqs .faq-header', $chrome);
        $this->assertStringContainsString('#page-our-services .faq-header', $chrome);
        $this->assertStringContainsString('#page-faqs .faq-body', $chrome);
        $this->assertStringContainsString('#page-our-services .faq-body', $chrome);
    }

    public function test_faq_hero_has_one_h1_and_approved_intro(): void
    {
        $this->assertSame('Frequently Asked Questions', PageH1::utilityH1ForSlug('faqs'));
        $this->assertSame(
            'Find clear answers about buying, selling, leasing, financing, and working with Serik Realty.',
            PageH1::FAQ_INTRO
        );

        $breadcrumb = file_get_contents(base_path('platform/themes/homzen/partials/breadcrumb.blade.php'));
        $faqShortcode = file_get_contents(base_path('platform/themes/homzen/partials/shortcodes/faqs/index.blade.php'));
        $contactFaq = file_get_contents(base_path('platform/themes/homzen/partials/shortcodes/contact-form/styles/style-2.blade.php'));

        $this->assertStringContainsString("request()->is('faqs')", $breadcrumb);
        $this->assertStringContainsString('serik-page-intro', $breadcrumb);
        $this->assertStringContainsString("'headingTag' => 'h2'", $faqShortcode);
        $this->assertStringNotContainsString("'headingTag' => 'h1'", $faqShortcode);
        $this->assertStringNotContainsString('<h1 class="srk-privacy-title', $contactFaq);
        $this->assertStringNotContainsString('https://serik.ca/storage/faqs.jpg', $contactFaq);
        $this->assertStringContainsString('aria-expanded', $faqShortcode);
        $this->assertStringContainsString('aria-controls', $faqShortcode);
        $this->assertStringContainsString('width="640"', $contactFaq);
        $this->assertStringContainsString('height="283"', $contactFaq);
        $this->assertStringContainsString('fetchpriority="high"', $contactFaq);
    }

    public function test_faq_accordion_aria_remains_on_grouped_and_list_layouts(): void
    {
        $faqShortcode = file_get_contents(base_path('platform/themes/homzen/partials/shortcodes/faqs/index.blade.php'));
        $this->assertStringContainsString('role="region"', $faqShortcode);
        $this->assertStringContainsString('aria-labelledby', $faqShortcode);
    }

    public function test_agent_cards_render_structured_fields_without_nested_profile_links_in_bio(): void
    {
        $info = file_get_contents(base_path('platform/themes/homzen/partials/shortcodes/agents/partials/info.blade.php'));
        $this->assertStringContainsString('AgentProfile::title', $info);
        $this->assertStringContainsString('View Profile', $info);
        $this->assertStringContainsString('Contact', $info);
        $this->assertStringNotContainsString('<a href="{{ $account->url }}"> {{ $account->description }}</a>', $info);
        $this->assertContains('display_order', AgentProfile::PRIVILEGED_FIELDS);
        $this->assertContains('is_featured', AgentProfile::PRIVILEGED_FIELDS);
    }
}
