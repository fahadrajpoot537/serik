<?php

namespace App\Support;

use Botble\Blog\Models\Tag;
use Botble\Page\Models\Page;
use Botble\RealEstate\Models\Account;
use Botble\SeoHelper\Facades\SeoHelper;
use Illuminate\Http\Request;

/**
 * Centralized meta descriptions and homepage canonical URL for Serik Realty.
 *
 * Mirrors the PageH1 pattern: path-keyed overrides applied at layout render time,
 * after plugin handlers set their defaults.
 */
final class SerikSeo
{
    /**
     * Path (no leading/trailing slash, lowercase) => meta description.
     *
     * @var array<string, string>
     */
    private const DESCRIPTIONS = [
        'agents' => 'Meet the Serik Realty team of licensed REALTORS® serving Toronto, Mississauga and Brampton. View agent profiles, specialties and direct contact details.',

        'agents/aju' => 'Connect with Aju, REALTOR® at Serik Realty, for personalized home buying and selling guidance across the Greater Toronto Area. View listings and book a call.',

        'agents/akash' => 'Akash (Micki) Luther is a REALTOR® at Serik Realty helping GTA buyers and sellers navigate the market with confidence. View his listings and contact info.',

        'agents/fahad' => 'Fahad Yakub, REALTOR® at Serik Realty, brings hands-on Greater Toronto Area market expertise to help you buy, sell or invest. View his active listings.',

        'agents/gary' => 'Gary Sodhi is Broker of Record and CEO of Serik Realty, leading a Mississauga-based team serving Toronto and the GTA. Learn about his experience and listings.',

        'agents/himanshu' => 'Himanshu Sood, REALTOR® at Serik Realty, helps buyers and sellers across Toronto and Mississauga reach their real estate goals. View listings and contact info.',

        'agents/kiran' => 'Kiran is a REALTOR® with Serik Realty, guiding GTA clients through buying, selling and investing in property. Browse current listings and schedule a call.',

        'agents/p' => 'Pooja Thakurel, Sales Representative at Serik Realty, offers dedicated support for GTA home buyers and sellers. View her current listings and book a call.',

        'blogs' => 'Explore Serik Realty\'s blog for GTA real estate market analysis, first-time buyer tips, DIY home advice and luxury listing trends, updated regularly.',

        'contact-us' => 'Get in touch with Serik Realty for a free consultation on buying, selling or investing in Toronto, Mississauga or Brampton real estate. Call or book online.',

        'faqs' => 'Answers to common questions about Serik Realty\'s cash-back program, free home evaluations, buyer consultations and real estate services across the GTA.',

        'properties' => 'Search active real estate listings across Toronto, Mississauga and Brampton with Serik Realty. Filter by location, price and property type to find your home.',

        'tag/diy' => 'Browse Serik Realty\'s DIY home improvement articles, from budget-friendly renovations to staging tips, written for GTA homeowners preparing to sell or upgrade.',

        'tag/first-time-buyers' => 'Read Serik Realty\'s guides for first-time home buyers in the GTA, covering budgeting, mortgages, down payments and the step-by-step buying process.',

        'tag/luxury-homes' => 'Discover Serik Realty\'s coverage of luxury homes and high-end real estate trends across Toronto, Mississauga and Brampton, curated for upscale buyers.',

        'tag/market-analysis' => 'Stay current with Serik Realty\'s GTA market analysis articles, covering price trends, inventory levels and forecasts for Toronto-area real estate.',

        'tag/tips' => 'Practical real estate tips from Serik Realty covering buying, selling, financing and home maintenance for Toronto and Mississauga homeowners.',
    ];

    public static function apply(?Request $request = null): void
    {
        $request ??= request();
        $path = self::normalizePath($request->path());

        self::applyCanonical($request, $path);

        if ($description = self::descriptionForPath($path)) {
            SeoHelper::meta()->setDescription($description);
        }
    }

    /**
     * Early hook for BASE_ACTION_PUBLIC_RENDER_SINGLE (pages, tags).
     */
    public static function applyForModel(string $screen, object $model): void
    {
        $path = match (true) {
            $screen === PAGE_MODULE_SCREEN_NAME && $model instanceof Page => self::normalizePath((string) $model->slug),
            $screen === TAG_MODULE_SCREEN_NAME && $model instanceof Tag => 'tag/' . self::normalizePath((string) $model->slug),
            default => null,
        };

        if ($path === null) {
            return;
        }

        if ($description = self::descriptionForPath($path)) {
            SeoHelper::meta()->setDescription($description);
        }
    }

    public static function homepageCanonical(): string
    {
        return rtrim(CanonicalUrl::origin(), '/') . '/';
    }

    private static function applyCanonical(Request $request, string $path): void
    {
        if (! $request->isMethod('GET')) {
            return;
        }

        if ($path === '') {
            SeoHelper::meta()->setUrl(self::homepageCanonical());

            return;
        }

        // Agent list/detail routes do not set a canonical in HandleFrontPages / PublicController.
        if (str_starts_with($path, 'agents')) {
            SeoHelper::meta()->setUrl(
                CanonicalUrl::normalize(rtrim(CanonicalUrl::origin(), '/') . '/' . $path)
            );
        }
    }

    private static function descriptionForPath(string $path): ?string
    {
        return self::DESCRIPTIONS[$path] ?? null;
    }

    private static function normalizePath(string $path): string
    {
        return trim(strtolower($path), '/');
    }
}
