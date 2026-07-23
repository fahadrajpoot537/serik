<?php

namespace App\Http\Controllers;

use App\Support\ListingImagePipeline;
use App\Support\TrebImageStore;
use Botble\RealEstate\Models\Property;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

final class TrebWebpController extends Controller
{
    public function __invoke(Request $request, string $listingKey, string $filename): BinaryFileResponse
    {
        if (! preg_match('/^(cover|\d{2})\.webp$/i', $filename)) {
            abort(404);
        }

        $listingKey = strtoupper(trim($listingKey));
        $relative = TrebImageStore::relativePath($listingKey, $filename);
        $disk = Storage::disk('public');

        if (! $disk->exists($relative)) {
            $this->materializeOnDemand($listingKey);
        }

        if (! $disk->exists($relative)) {
            abort(404);
        }

        return response()->file($disk->path($relative), [
            'Content-Type' => 'image/webp',
            'Cache-Control' => 'public, max-age=31536000, immutable',
        ]);
    }

    private function materializeOnDemand(string $listingKey): void
    {
        $property = Property::query()
            ->where('external_id', $listingKey)
            ->first();

        if ($property === null) {
            return;
        }

        $pipeline = app(ListingImagePipeline::class);

        if ($pipeline->needsProcessing($property)) {
            $pipeline->persist($property, true);
        }
    }
}
