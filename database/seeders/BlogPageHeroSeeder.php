<?php

namespace Database\Seeders;

use Botble\Base\Facades\MetaBox;
use Botble\Media\Facades\RvMedia;
use Botble\Media\Models\MediaFile;
use Botble\Page\Models\Page;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;

/**
 * Sets the /blogs page hero (breadcrumb background) to
 * real-estate-investing-tips-ontario.webp.
 *
 * Safe on live — does not truncate data. Homepage is not touched.
 *
 * php artisan db:seed --class="Database\\Seeders\\BlogPageHeroSeeder" --force
 */
class BlogPageHeroSeeder extends Seeder
{
    public const IMAGE_BASENAME = 'real-estate-investing-tips-ontario.webp';

    public function run(): void
    {
        $source = $this->resolveSourcePath();
        if (! $source) {
            $this->command?->error('Image not found. Place ' . self::IMAGE_BASENAME . ' in database/seeders/files/hero-banners/ or public/pictures/.');

            return;
        }

        $url = $this->uploadOrReuse($source, 'hero-banners');
        if (! $url) {
            $this->command?->error('Failed to upload hero image.');

            return;
        }

        $page = $this->findBlogPage();
        if (! $page) {
            $this->command?->error('Blog page not found (theme option blog_page_id / slug blogs / name Blog).');

            return;
        }

        $page->image = $url;
        $page->save();

        MetaBox::saveMetaBoxData($page, 'breadcrumb_background_image', $url);

        $this->command?->call('cache:clear');
        $this->command?->info(sprintf(
            'Blog page hero updated: id=%d name="%s" → %s',
            $page->id,
            $page->name,
            $url
        ));
    }

    protected function resolveSourcePath(): ?string
    {
        $candidates = [
            database_path('seeders/files/hero-banners/' . self::IMAGE_BASENAME),
            public_path('pictures/' . self::IMAGE_BASENAME),
            public_path('pictures/real-estate-investing-tips-ontario.webp'),
        ];

        foreach ($candidates as $path) {
            if (File::exists($path)) {
                return $path;
            }
        }

        return null;
    }

    protected function findBlogPage(): ?Page
    {
        $blogPageId = (int) theme_option('blog_page_id', setting('blog_page_id'));
        if ($blogPageId > 0) {
            $page = Page::query()->find($blogPageId);
            if ($page) {
                return $page;
            }
        }

        $ids = DB::table('slugs')
            ->whereIn('key', ['blogs', 'blog'])
            ->where('reference_type', Page::class)
            ->pluck('reference_id');

        if ($ids->isNotEmpty()) {
            $page = Page::query()->whereIn('id', $ids)->first();
            if ($page) {
                return $page;
            }
        }

        return Page::query()
            ->where(function ($query) {
                $query->whereRaw('LOWER(name) = ?', ['blog'])
                    ->orWhereRaw('LOWER(name) = ?', ['blogs']);
            })
            ->first();
    }

    protected function uploadOrReuse(string $absolutePath, string $folderSlug): ?string
    {
        $basename = File::basename($absolutePath);
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

        $result = RvMedia::uploadFromPath($absolutePath, 0, $folderSlug);
        if (! empty($result['error'])) {
            $this->command?->error(($result['message'] ?? 'Upload failed') . ' (' . $basename . ')');

            return null;
        }

        $data = $result['data'] ?? null;
        $url = is_object($data) ? $data->url : ($data['url'] ?? null);

        return $url ?: null;
    }
}
