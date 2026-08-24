<?php

namespace App\Support;

use Illuminate\Http\Request;

/**
 * Inner-page hero (breadcrumb banner) images from public/pictures.
 */
final class PageHeroImage
{
    /**
     * Request path => filename in public/pictures.
     *
     * @var array<string, string>
     */
    private const FILES = [
        'appointment-scheduler' => 'how-to-buy-land-in-ontario-canada.webp',
        'free-home-evaluation' => 'Mortgage Calculator.webp',
        'evaluation' => 'Mortgage Calculator.webp',
        'tips-for-home-selling' => 'how-to-buy-land-in-ontario-canada.webp',
        'cash-back-calculator' => 'The Benefits of Smart Home Technology.webp',
        'privacy-policy' => 'Understanding Property Taxes and How to Lower Them.webp',
        'term-and-conditions' => 'Cost of Selling a House in Canada.webp',
        'terms-conditions' => 'Cost of Selling a House in Canada.webp',
        'faqs' => 'real-estate-investing-tips-ontario.webp',
        'our-services' => 'Tips for Selling Out Your Property.webp',
        'contact-us' => 'cost-of-selling-a-house-in-ontario-canada.webp',
    ];

    public static function urlForRequest(?Request $request = null): ?string
    {
        $request ??= request();
        $path = trim($request->path(), '/');

        $file = self::FILES[$path] ?? null;
        if ($file === null) {
            return null;
        }

        $absolute = public_path('pictures/' . $file);
        if (! is_file($absolute)) {
            return null;
        }

        return asset('pictures/' . str_replace(' ', '%20', $file));
    }

    /**
     * @return array<string, string>
     */
    public static function pathMap(): array
    {
        return self::FILES;
    }
}
