<?php

namespace Tests\Unit;

use App\Support\AgentInquiryFormContext;
use App\Support\AgentProfile;
use Illuminate\Http\Request;
use Tests\TestCase;

class AgentProfileTest extends TestCase
{
    public function test_sanitize_rejects_unsafe_and_oversized_items(): void
    {
        $clean = AgentProfile::sanitizeList("Toronto, <script>alert(1)</script>, " . str_repeat('a', 200) . ", Mississauga");

        $this->assertContains('Toronto', $clean);
        $this->assertContains('Mississauga', $clean);
        $this->assertNotContains('<script>alert(1)</script>', $clean);
        foreach ($clean as $item) {
            $this->assertLessThanOrEqual(80, mb_strlen($item));
        }
    }

    public function test_privileged_fields_cannot_be_self_assigned(): void
    {
        $this->assertContains('is_featured', AgentProfile::PRIVILEGED_FIELDS);
        $this->assertContains('is_public_profile', AgentProfile::PRIVILEGED_FIELDS);
        $this->assertContains('display_order', AgentProfile::PRIVILEGED_FIELDS);
        $this->assertContains('email', AgentProfile::PRIVILEGED_FIELDS);

        $controller = file_get_contents(base_path('platform/plugins/real-estate/src/Http/Controllers/Fronts/PublicAccountController.php'));
        $form = file_get_contents(base_path('platform/plugins/real-estate/src/Forms/Fronts/ProfileForm.php'));
        $this->assertStringContainsString('AgentProfile::PRIVILEGED_FIELDS', $controller);
        $this->assertStringContainsString('AgentProfile::sanitizeList', $controller);
        $this->assertStringContainsString("'is_featured'", $form);
        $this->assertStringContainsString("'display_order'", $form);
    }

    public function test_view_profile_and_contact_ctas_are_real_links(): void
    {
        $info = file_get_contents(base_path('platform/themes/homzen/partials/shortcodes/agents/partials/info.blade.php'));
        $profile = file_get_contents(base_path('platform/themes/homzen/views/real-estate/agent.blade.php'));

        $this->assertStringContainsString('href="{{ $profileUrl }}"', $info);
        $this->assertStringContainsString('href="{{ $contactUrl }}"', $info);
        $this->assertStringContainsString('AgentInquiryFormContext::contactUrl', $profile);
        $this->assertStringNotContainsString('https://serik.ca/contact-us', $profile);
    }

    public function test_agent_inquiry_does_not_trust_missing_or_foreign_ids(): void
    {
        $request = Request::create('/contact/send', 'POST', [
            AgentInquiryFormContext::REQUEST_CONTEXT_KEY => AgentInquiryFormContext::CONTEXT_KEY,
            AgentInquiryFormContext::REQUEST_AGENT_KEY => '999',
            'recipient' => 'other@example.com',
            'source' => 'evil',
        ]);

        $this->assertFalse(AgentInquiryFormContext::isActive($request));
        AgentInquiryFormContext::applyTrustedRequestOverrides($request);
        $this->assertSame('evil', $request->input('source'));
    }

    public function test_public_cache_invalidation_is_wired(): void
    {
        $admin = file_get_contents(base_path('platform/plugins/real-estate/src/Http/Controllers/AccountController.php'));
        $front = file_get_contents(base_path('platform/plugins/real-estate/src/Http/Controllers/Fronts/PublicAccountController.php'));
        $cache = file_get_contents(base_path('app/Support/AgentProfile.php'));

        $this->assertStringContainsString('AgentProfile::invalidatePublicCaches()', $admin);
        $this->assertStringContainsString('AgentProfile::invalidatePublicCaches()', $front);
        $this->assertStringContainsString("HomepageFragmentCache::bump('shortcode:agents')", $cache);
    }

    public function test_directory_eager_loads_avatar_and_counts(): void
    {
        $controller = file_get_contents(base_path('platform/plugins/real-estate/src/Http/Controllers/Fronts/PublicController.php'));
        $this->assertStringContainsString("->with(['avatar'])", $controller);
        $this->assertStringContainsString('withCount([', $controller);
        $this->assertStringContainsString('paginate(12)', $controller);
    }

    public function test_missing_optional_rows_are_hidden_in_cards(): void
    {
        $info = file_get_contents(base_path('platform/themes/homzen/partials/shortcodes/agents/partials/info.blade.php'));
        $this->assertStringContainsString('@if($specialties !== [])', $info);
        $this->assertStringContainsString('@if($areas !== [])', $info);
        $this->assertStringContainsString('@if($languages !== [])', $info);
        $this->assertStringContainsString('@if($title !== \'\')', $info);
        $this->assertStringContainsString('@if($bio !== \'\')', $info);
    }
}
