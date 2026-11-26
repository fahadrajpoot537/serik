<?php

namespace Database\Seeders;

use Botble\Base\Facades\MetaBox;
use Botble\Media\Facades\RvMedia;
use Botble\Media\Models\MediaFile;
use Botble\Page\Models\Page;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;

/**
 * Page-top banners from public/Top Banners, matched by file name.
 * Copies the previous banner files aside before replacing them.
 *
 * php artisan db:seed --class="Database\\Seeders\\TopPageBannersSeeder" --force
 */
class TopPageBannersSeeder extends Seeder
{
    /**
     * @var array<string, list<string>>
     */
    private const BANNERS = [
        'Blogs.webp' => ['blog', 'blogs'],
        'Cashback Calculator.webp' => ['cash-back-calculator'],
        'Contact Us.webp' => ['contact-us'],
        'Cookies & Policies.webp' => ['privacy-policy', 'cookie-policy'],
        'FAQs.webp' => ['faqs'],
        'Home Evalutation.webp' => ['free-home-evaluation', 'evaluation'],
        'Mortgage Calculator.webp' => ['mortgage-calculator'],
        'Our Services.webp' => ['our-services'],
        'Tips for Home Selling.webp' => ['tips-for-home-selling'],
    ];

    public function run(): void
    {
        $backupDir = storage_path('app/banner-backups/' . now()->format('Ymd-His'));
        File::ensureDirectoryExists($backupDir);
        $manifest = [];

        foreach (self::BANNERS as $filename => $slugs) {
            $source = public_path('Top Banners/' . $filename);
            if (! File::exists($source)) {
                $this->command?->warn('Missing banner: ' . $filename);

                continue;
            }

            $url = $this->uploadOrReuse($source, 'page-banners');
            if (! $url) {
                $this->command?->warn('Upload failed: ' . $filename);

                continue;
            }

            $ids = DB::table('slugs')
                ->whereIn('key', $slugs)
                ->where('reference_type', Page::class)
                ->pluck('reference_id');

            if ($ids->isEmpty()) {
                $this->command?->warn('No page for ' . $filename . ' (' . implode(', ', $slugs) . ')');

                continue;
            }

            foreach (Page::query()->whereIn('id', $ids)->get() as $page) {
                $manifest[] = $this->backupPageBanner($page, $backupDir, $filename);

                $page->image = $url;
                $page->save();
                MetaBox::saveMetaBoxData($page, 'breadcrumb_background_image', $url);

                $this->command?->info(sprintf('page %d → %s', $page->id, $url));
            }
        }

        File::put($backupDir . '/manifest.json', json_encode($manifest, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
        $this->command?->info('Previous banners saved in ' . $backupDir);

        $this->command?->call('cache:clear');
        $this->command?->call('view:clear');
    }

    /**
     * @return array<string, mixed>
     */
    protected function backupPageBanner(Page $page, string $backupDir, string $newFile): array
    {
        $meta = $page->getMetaData('breadcrumb_background_image', true);
        $meta = is_string($meta) ? $meta : null;
        $slug = (string) (DB::table('slugs')
            ->where('reference_id', $page->id)
            ->where('reference_type', Page::class)
            ->value('key') ?: $page->id);

        $saved = [];
        foreach (array_unique(array_filter([$page->image, $meta])) as $previous) {
            $copied = $this->copyMediaFile((string) $previous, $backupDir, $slug);
            $saved[] = [
                'url' => $previous,
                'file' => $copied,
            ];
        }

        return [
            'page_id' => $page->id,
            'name' => $page->name,
            'slug' => $slug,
            'previous_image' => $page->image,
            'previous_breadcrumb_image' => $meta,
            'replaced_with' => $newFile,
            'saved_files' => $saved,
        ];
    }

    protected function copyMediaFile(string $url, string $backupDir, string $slug): ?string
    {
        $real = RvMedia::getRealPath($url);
        if (! $real || ! File::exists($real)) {
            return null;
        }

        $target = $backupDir . DIRECTORY_SEPARATOR . Str::slug($slug) . '-' . File::basename($real);
        File::copy($real, $target);

        return $target;
    }

    protected function uploadOrReuse(string $absolutePath, string $folderSlug): ?string
    {
        $basename = Str::slug(pathinfo($absolutePath, PATHINFO_FILENAME)) . '.' . strtolower((string) pathinfo($absolutePath, PATHINFO_EXTENSION));
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
