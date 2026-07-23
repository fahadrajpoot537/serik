<?php

namespace App\Http\Controllers;

use App\Support\TrebImageProxy;
use Botble\RealEstate\Models\Property;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Streams TREB CDN images through same-origin URLs (trreb-image.ampre.ca blocks browsers).
 */
final class TrebWebpController extends Controller
{
    public function __invoke(Request $request, string $listingKey, string $filename): Response
    {
        if (! preg_match('/^(cover|\d{2})\.webp$/i', $filename)) {
            abort(404);
        }

        $listingKey = strtoupper(trim($listingKey));
        $property = Property::query()
            ->where('external_id', $listingKey)
            ->first(['image_val', 'external_id']);

        return TrebImageProxy::stream(
            $listingKey,
            $filename,
            $property?->image_val
        );
    }
}
