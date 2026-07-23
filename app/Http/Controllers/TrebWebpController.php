<?php

namespace App\Http\Controllers;

use App\Support\SerikMediaUrl;
use App\Support\TrebImagePersistence;
use App\Support\TrebImageStore;
use Botble\RealEstate\Models\Property;
use Theme\homzen\Supports\TrebPropertyHelper;
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
            $this->materializeOnDemand($listingKey, $filename);
        }

        if (! $disk->exists($relative)) {
            abort(404);
        }

        return response()->file($disk->path($relative), [
            'Content-Type' => 'image/webp',
            'Cache-Control' => 'public, max-age=31536000, immutable',
        ]);
    }

    private function materializeOnDemand(string $listingKey, string $filename): void
    {
        $store = app(TrebImageStore::class);
        $relative = TrebImageStore::relativePath($listingKey, $filename);

        if (Storage::disk('public')->exists($relative)) {
            return;
        }

        $property = Property::query()
            ->where('external_id', $listingKey)
            ->first();

        $persistence = app(TrebImagePersistence::class);

        if ($property !== null) {
            if (strcasecmp($filename, 'cover.webp') === 0) {
                $persistence->persistForProperty($property, false);
            } else {
                $persistence->persistForProperty($property, true);
            }

            if (Storage::disk('public')->exists($relative)) {
                return;
            }
        }

        $images = TrebPropertyHelper::getPropertyImages($listingKey, null, true);
        $remote = (string) ($images[0] ?? '');

        if ($remote === '') {
            return;
        }

        if (str_contains($remote, '/rs:') || str_contains($remote, 'rs:fit') || preg_match('/^L3RycmVi/i', $remote)) {
            $remote = SerikMediaUrl::resolveTrebRemoteUrl($remote) ?? '';
        }

        if ($remote === '') {
            return;
        }

        $path = $store->persistFromRemoteUrl($listingKey, $remote, $filename);

        if ($path && $property !== null && strcasecmp($filename, 'cover.webp') === 0 && ! $store->storedWebpExists($property->image_val)) {
            $property->image_val = $path;
            $property->saveQuietly();
        }
    }
}
