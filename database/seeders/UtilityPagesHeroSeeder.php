<?php

namespace Database\Seeders;

use App\Support\PageHeroImage;
use Botble\Base\Facades\MetaBox;
use Botble\Media\Facades\RvMedia;
use Botble\Media\Models\MediaFile;
use Botble\Page\Models\Page;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;

/**
 * Inner utility page heroes from public/pictures.
 * Does not change homepage, webhook, or GHL mapping.
 *
 * php artisan db:seed --class="Database\\Seeders\\UtilityPagesHeroSeeder" --force
 */
class UtilityPagesHeroSeeder extends Seeder
{
    public function run(): void
    {
        $homepageId = (int) theme_option('homepage_id');

        foreach (PageHeroImage::pathMap() as $slug => $filename) {
            $source = public_path('pictures/' . $filename);
            if (! File::exists($source)) {
                $this->command?->warn('Missing picture: ' . $filename);

                continue;
            }

            $url = $this->uploadOrReuse($source, 'hero-banners');
            if (! $url) {
                $this->command?->warn('Upload failed: ' . $filename);

                continue;
            }

            $ids = DB::table('slugs')
                ->where('key', $slug)
                ->where('reference_type', Page::class)
                ->pluck('reference_id');

            if ($ids->isEmpty()) {
                continue;
            }

            $pages = Page::query()->whereIn('id', $ids)->get();
            foreach ($pages as $page) {
                if ($homepageId && (int) $page->id === $homepageId) {
                    continue;
                }

                $page->image = $url;
                $page->save();
                MetaBox::saveMetaBoxData($page, 'breadcrumb_background_image', $url);

                $this->command?->info(sprintf(
                    '/%s (id=%d) → %s',
                    $slug,
                    $page->id,
                    $url
                ));
            }
        }

        $this->command?->call('cache:clear');
        $this->command?->call('view:clear');
    }

    protected function uploadOrReuse(string $absolutePath, string $folderSlug): ?string
    {
        $basename = Str::slug(pathinfo($absolutePath, PATHINFO_FILENAME)) . '.' . strtolower(pathinfo($absolutePath, PATHINFO_EXTENSION));
        $expectedUrl = $folderSlug . '/' . $basename;

        $existing = MediaFile::query()->where('url', $expectedUrl)->first();
        if ($existing) {
            $realPath = RvMedia::getRealPath($existing->url);
            if ($realPath) {
                File::ensureDirectoryExists(dirname($realPath));
                File::copy($absolutePath, $realPath);
            }

            return $existing->url;
        }

        $tmp = sys_get_temp_dir() . DIRECTORY_SEPARATOR . $basename;
        File::copy($absolutePath, $tmp);

        $result = RvMedia::uploadFromPath($tmp, 0, $folderSlug);
        File::delete($tmp);

        if (! empty($result['error'])) {
            $this->command?->error(($result['message'] ?? 'Upload failed') . ' (' . $basename . ')');

            return null;
        }

        $data = $result['data'] ?? null;

        return is_object($data) ? ($data->url ?? null) : ($data['url'] ?? null);
    }
}
