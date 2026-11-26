<?php

namespace Database\Seeders;

use Botble\Media\Facades\RvMedia;
use Botble\Media\Models\MediaFile;
use Botble\Page\Models\Page;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;

/**
 * Homepage hero slider (right side) from public/Main Serik Banners.
 * Copies the previous slide files aside before replacing them.
 *
 * php artisan db:seed --class="Database\\Seeders\\MainSerikBannersSeeder" --force
 */
class MainSerikBannersSeeder extends Seeder
{
    /** @var list<string> */
    private const FILES = ['01.webp', '02.webp', '03.webp', '04.webp'];

    public function run(): void
    {
        $page = Page::query()
            ->where('content', 'like', '%slider_image_1%')
            ->orderBy('id')
            ->first();

        if (! $page || ! preg_match('/\[hero-banner\b[^\]]*\]/', (string) $page->content, $match)) {
            $this->command?->error('Homepage hero-banner shortcode was not found.');

            return;
        }

        $backupDir = storage_path('app/banner-backups/main-hero-' . now()->format('Ymd-His'));
        File::ensureDirectoryExists($backupDir);

        $urls = [];
        foreach (self::FILES as $index => $filename) {
            $source = public_path('Main Serik Banners/' . $filename);
            if (! File::exists($source)) {
                $this->command?->error('Missing banner: ' . $filename);

                return;
            }

            $url = $this->uploadOrReuse($source, 'main-serik-banners');
            if (! $url) {
                $this->command?->error('Upload failed: ' . $filename);

                return;
            }

            $urls[$index + 1] = $url;
        }

        $tag = $match[0];
        $updated = $tag;
        $previous = [];

        foreach ($urls as $slot => $url) {
            if (! preg_match('/slider_image_' . $slot . '="([^"]*)"/', $updated, $old)) {
                $this->command?->error('slider_image_' . $slot . ' is missing from the hero shortcode.');

                return;
            }

            $previous[$slot] = $old[1];
            $this->copyMediaFile($old[1], $backupDir, $slot);
            $updated = preg_replace(
                '/slider_image_' . $slot . '="[^"]*"/',
                'slider_image_' . $slot . '="' . $url . '"',
                $updated,
                1
            );
        }

        $page->content = str_replace($tag, $updated, (string) $page->content);
        $page->save();

        File::put($backupDir . '/manifest.json', json_encode([
            'page_id' => $page->id,
            'previous' => $previous,
            'next' => $urls,
        ], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));

        $this->command?->info('Homepage hero slides updated. Previous files saved in ' . $backupDir);
        foreach ($urls as $slot => $url) {
            $this->command?->info('slider_image_' . $slot . ' → ' . $url);
        }

        $this->command?->call('cache:clear');
        $this->command?->call('view:clear');
    }

    protected function copyMediaFile(string $url, string $backupDir, int $slot): void
    {
        $real = RvMedia::getRealPath($url);
        if (! $real || ! File::exists($real)) {
            return;
        }

        File::copy($real, $backupDir . DIRECTORY_SEPARATOR . $slot . '-' . File::basename($real));
    }

    protected function uploadOrReuse(string $absolutePath, string $folderSlug): ?string
    {
        $basename = Str::slug((string) pathinfo($absolutePath, PATHINFO_FILENAME))
            . '.' . strtolower((string) pathinfo($absolutePath, PATHINFO_EXTENSION));
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
