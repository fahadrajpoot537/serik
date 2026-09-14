<?php

namespace App\Support;

use Illuminate\Http\Request;

/**
 * Inner-page hero (breadcrumb banner) images from public/pictures.
 */
final class PageHeroImage
{
    private const TOP_BANNERS = 'Website Banners (TOp)';

    /**
     * Request path => relative path under public/pictures.
     *
     * @var array<string, string>
     */
    private const FILES = [
        'appointment-scheduler' => 'how-to-buy-land-in-ontario-canada.webp',
        'free-home-evaluation' => self::TOP_BANNERS . '/Home Evalutation.webp',
        'evaluation' => self::TOP_BANNERS . '/Home Evalutation.webp',
        'tips-for-home-selling' => self::TOP_BANNERS . '/Tips for Home Selling.webp',
        'cash-back-calculator' => self::TOP_BANNERS . '/Cashback Calculator.webp',
        'mortgage-calculator' => self::TOP_BANNERS . '/Mortgage Calculator.webp',
        'privacy-policy' => self::TOP_BANNERS . '/Cookies & Policies.webp',
        'cookie-policy' => self::TOP_BANNERS . '/Cookies & Policies.webp',
        'term-and-conditions' => 'Cost of Selling a House in Canada.webp',
        'terms-conditions' => 'Cost of Selling a House in Canada.webp',
        'faqs' => self::TOP_BANNERS . '/FAQs.webp',
        'our-services' => self::TOP_BANNERS . '/Our Services.webp',
        'contact-us' => self::TOP_BANNERS . '/Contact Us.webp',
        'blog' => self::TOP_BANNERS . '/Blogs.webp',
        'blogs' => self::TOP_BANNERS . '/Blogs.webp',
        'wishlist' => 'wishlist-banner.png',
    ];

    public static function urlForRequest(?Request $request = null): ?string
    {
        $request ??= request();
        $path = trim($request->path(), '/');

        $file = self::FILES[$path] ?? null;

        // All blog post detail pages share one hero banner.
        if ($file === null && (str_starts_with($path, 'blog/') || str_starts_with($path, 'blogs/'))) {
            $file = 'blog-hero-banner.png';
        }

        if ($file === null) {
            return null;
        }

        return self::assetUrl($file);
    }

    /**
     * @return array<string, string>
     */
    public static function pathMap(): array
    {
        return self::FILES;
    }

    private static function assetUrl(string $relative): ?string
    {
        $relative = str_replace('\\', '/', ltrim($relative, '/'));
        $absolute = public_path('pictures/' . $relative);

        if (! is_file($absolute)) {
            return null;
        }

        $encoded = implode('/', array_map('rawurlencode', explode('/', $relative)));

        return asset('pictures/' . $encoded);
    }
}
