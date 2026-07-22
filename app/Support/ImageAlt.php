<?php

namespace App\Support;

use Botble\Blog\Models\Post;
use Botble\Media\Models\MediaFile;
use Botble\RealEstate\Models\Account;
use Botble\RealEstate\Models\Project;
use Botble\RealEstate\Models\Property;
use Illuminate\Support\Str;

/**
 * Environment-agnostic alt text resolution for theme images.
 *
 * Priority: explicit alt → media record alt/name → model/context attributes → humanized path.
 */
final class ImageAlt
{
    /** @var array<string, ?MediaFile> */
    private static array $mediaCache = [];

    public static function resolve(
        ?string $explicit = null,
        ?string $mediaPathOrUrl = null,
        string|array|object|null $context = null,
        bool $decorative = false
    ): string {
        if ($decorative) {
            return '';
        }

        if (($text = self::clean($explicit)) !== '') {
            return $text;
        }

        if ($mediaPathOrUrl !== null && $mediaPathOrUrl !== '') {
            if (($fromMedia = self::fromMediaPath($mediaPathOrUrl)) !== '') {
                return $fromMedia;
            }
        }

        if ($context !== null) {
            if (is_object($context)) {
                return self::fromModel($context) ?? '';
            }

            if (is_array($context)) {
                $parts = array_filter(array_map(
                    static fn ($part) => self::clean(is_scalar($part) ? (string) $part : ''),
                    $context
                ));

                return $parts !== [] ? implode(' - ', array_unique($parts)) : '';
            }

            return self::clean((string) $context);
        }

        if ($mediaPathOrUrl !== null && $mediaPathOrUrl !== '') {
            return self::humanizeFromPath($mediaPathOrUrl);
        }

        return '';
    }

    public static function fromMediaPath(?string $pathOrUrl): string
    {
        $relative = self::normalizeMediaPath($pathOrUrl);

        if ($relative === null) {
            return '';
        }

        $file = self::findMediaFile($relative);

        if (! $file) {
            return self::humanizeFromPath($relative);
        }

        $alt = self::clean((string) ($file->alt ?? ''));

        if ($alt !== '' && ! self::isLikelyAutoFilename($alt, (string) $file->name)) {
            return $alt;
        }

        $name = self::humanizeName((string) $file->name);

        return $name !== '' ? $name : self::humanizeFromPath($relative);
    }

    public static function fromModel(?object $model): ?string
    {
        if ($model === null) {
            return null;
        }

        if ($model instanceof Property) {
            return self::forProperty($model);
        }

        if ($model instanceof Account) {
            return self::forAccount($model);
        }

        if ($model instanceof Post) {
            return self::forPost($model);
        }

        if ($model instanceof Project) {
            return self::forProject($model);
        }

        foreach (['name', 'title'] as $attribute) {
            if (! empty($model->{$attribute})) {
                return self::clean((string) $model->{$attribute});
            }
        }

        return null;
    }

    public static function forProperty(Property $property): string
    {
        $city = null;

        if (! empty($property->city?->name)) {
            $city = (string) $property->city->name;
        } elseif (! empty($property->city_name)) {
            $city = (string) $property->city_name;
        }

        $type = null;

        if ($property->type && method_exists($property->type, 'label')) {
            $type = (string) $property->type->label();
        } elseif (! empty($property->PropertySubType)) {
            $type = (string) $property->PropertySubType;
        }

        $parts = array_filter([
            self::clean((string) ($property->name ?? '')),
            $city,
            $type,
            $property->external_id ?? $property->unique_id ?? null,
        ]);

        return $parts !== [] ? implode(' - ', array_unique($parts)) : __('Property listing');
    }

    public static function forAccount(Account $account): string
    {
        $name = self::clean((string) ($account->name ?? ''));

        return $name !== ''
            ? __(':name - Serik Realty Agent', ['name' => $name])
            : __('Serik Realty Agent');
    }

    public static function forPost(Post $post): string
    {
        $title = self::clean((string) ($post->name ?? ''));

        return $title !== '' ? $title : __('Blog post image');
    }

    public static function forProject(Project $project): string
    {
        $name = self::clean((string) ($project->name ?? ''));

        return $name !== '' ? $name : __('Real estate project');
    }

    public static function humanizeFromPath(string $pathOrUrl): string
    {
        $path = basename(parse_url($pathOrUrl, PHP_URL_PATH) ?: $pathOrUrl);

        return self::humanizeName($path);
    }

    public static function humanizeName(string $name): string
    {
        $name = pathinfo($name, PATHINFO_FILENAME);
        $name = preg_replace('/-[0-9a-f]{8,}$/i', '', $name) ?? $name;
        $name = preg_replace('/\b\d{3,4}x\d{3,4}\b/i', '', $name) ?? $name;
        $name = preg_replace('/\b(jpg|jpeg|png|webp|gif|jfif)\b/i', '', $name) ?? $name;
        $name = trim(str_replace(['_', '-'], ' ', $name));

        if ($name === '') {
            return '';
        }

        return Str::title(preg_replace('/\s+/', ' ', $name) ?? $name);
    }

    public static function clean(?string $value): string
    {
        $value = trim(strip_tags((string) $value));

        return $value;
    }

    private static function normalizeMediaPath(?string $pathOrUrl): ?string
    {
        $path = trim((string) $pathOrUrl);

        if ($path === '') {
            return null;
        }

        if (str_starts_with($path, 'http://') || str_starts_with($path, 'https://') || str_starts_with($path, '//')) {
            if (preg_match('#/storage/(.+)$#i', $path, $matches)) {
                $path = $matches[1];
            } else {
                return null;
            }
        }

        $path = ltrim(str_replace('\\', '/', $path), '/');

        if (str_starts_with($path, 'storage/')) {
            $path = substr($path, strlen('storage/'));
        }

        return $path !== '' ? $path : null;
    }

    private static function findMediaFile(string $relative): ?MediaFile
    {
        if (array_key_exists($relative, self::$mediaCache)) {
            return self::$mediaCache[$relative];
        }

        $basename = basename($relative);

        $file = MediaFile::query()
            ->where(function ($query) use ($relative, $basename): void {
                $query->where('url', $relative)
                    ->orWhere('url', 'like', '%/' . $basename);
            })
            ->orderByRaw('CASE WHEN url = ? THEN 0 ELSE 1 END', [$relative])
            ->first();

        self::$mediaCache[$relative] = $file;

        return $file;
    }

    private static function isLikelyAutoFilename(string $alt, string $filename): bool
    {
        $altSlug = Str::slug($alt);
        $nameSlug = Str::slug(pathinfo($filename, PATHINFO_FILENAME));

        return $altSlug !== '' && $altSlug === $nameSlug;
    }
}
