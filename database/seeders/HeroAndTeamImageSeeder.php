<?php

namespace Database\Seeders;

use Botble\Base\Facades\BaseHelper;
use Botble\Base\Facades\MetaBox;
use Botble\Blog\Models\Post;
use Botble\Media\Facades\RvMedia;
use Botble\Media\Models\MediaFile;
use Botble\Page\Models\Page;
use Botble\RealEstate\Models\Account;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;

/**
 * Safe to run on live. Does not truncate data.
 *
 * php artisan db:seed --class="Database\\Seeders\\HeroAndTeamImageSeeder" --force
 */
class HeroAndTeamImageSeeder extends Seeder
{
    public function run(): void
    {
        $this->updateHeroImages();
        $this->updateTeamAvatars();

        $this->command?->call('cache:clear');
        $this->command?->info('Hero and team images updated. Homepage was not changed.');
    }

    protected function updateHeroImages(): void
    {
        $homepageId = (int) theme_option('homepage_id');

        $maps = [
            [
                'file' => 'mortgage-calculator.webp',
                'slugs' => ['mortgage-calculator'],
                'names' => ['Mortgage Calculator'],
            ],
            [
                'file' => 'the-benefits-of-smart-home-technology.webp',
                'slugs' => ['the-benefits-of-smart-home-technology'],
                'names' => ['The Benefits of Smart Home Technology'],
            ],
            [
                'file' => 'tips-for-selling-out-your-property.webp',
                'slugs' => ['tips-for-selling-out-your-property', 'tips-for-renting-out-your-property'],
                'names' => ['Tips for Selling Out Your Property', 'Tips for Renting Out Your Property'],
            ],
            [
                'file' => 'understanding-property-taxes-and-how-to-lower-them.webp',
                'slugs' => ['understanding-property-taxes-and-how-to-lower-them'],
                'names' => ['Understanding Property Taxes and How to Lower Them'],
            ],
            [
                'file' => 'cost-of-selling-a-house-in-canada.webp',
                'slugs' => ['cost-of-selling-a-house-in-canada'],
                'names' => ['Cost of Selling a House in Canada'],
            ],
            [
                'file' => 'cost-of-selling-a-house-in-ontario-canada.webp',
                'slugs' => ['cost-of-selling-a-house-in-ontario-canada'],
                'names' => ['Cost of Selling a House in Ontario Canada', 'Cost of Selling a House in Ontario, Canada'],
            ],
            [
                'file' => 'how-to-buy-land-in-ontario-canada.webp',
                'slugs' => ['how-to-buy-land-in-ontario-canada'],
                'names' => ['How to Buy Land in Ontario Canada', 'How to Buy Land in Ontario, Canada'],
            ],
            [
                'file' => 'real-estate-investing-tips-ontario.webp',
                'slugs' => ['real-estate-investing-tips-ontario'],
                'names' => ['Real Estate Investing Tips Ontario', 'Real Estate Investing Tips for Ontario'],
            ],
        ];

        foreach ($maps as $map) {
            $path = database_path('seeders/files/hero-banners/' . $map['file']);
            $url = $this->uploadOrReuse($path, 'hero-banners');
            if (! $url) {
                $this->command?->warn('Skipped missing hero file: ' . $map['file']);

                continue;
            }

            $updatedPosts = $this->applyToPosts($map['slugs'], $map['names'], $url);
            $updatedPages = $this->applyToPages($map['slugs'], $map['names'], $url, $homepageId);

            $this->command?->info(sprintf(
                '%s → posts:%d pages:%d (%s)',
                $map['file'],
                $updatedPosts,
                $updatedPages,
                $url
            ));
        }
    }

    protected function updateTeamAvatars(): void
    {
        $maps = [
            [
                'file' => 'gary.webp',
                'name' => 'Gary Sodhi',
                'usernames' => ['gary', 'garysodhi', 'gary-sodhi'],
            ],
            [
                'file' => 'himmat.webp',
                'name' => 'Himmat Brar',
                'usernames' => ['himmat', 'himmatbrar', 'himmat-brar'],
            ],
            [
                'file' => 'harneet.webp',
                'name' => 'Harneet Jhajj',
                'usernames' => ['harneet', 'harneetjhajj', 'harneet-jhajj'],
            ],
            [
                'file' => 'sadaqat.webp',
                'name' => 'Sadaqat Sheikh',
                'usernames' => ['sadaqat', 'sadaqatsheikh', 'sadaqat-sheikh'],
            ],
        ];

        foreach ($maps as $map) {
            $path = database_path('seeders/files/team-avatars/' . $map['file']);
            $upload = $this->uploadOrReuse($path, 'team-avatars', true);
            if (! $upload) {
                $this->command?->warn('Skipped missing team file: ' . $map['file']);

                continue;
            }

            [$url, $fileId] = $upload;
            $accounts = $this->findAccounts($map['name'], $map['usernames']);

            if ($accounts->isEmpty()) {
                $this->command?->warn('No agent found for ' . $map['name'] . ' — photo uploaded as ' . $url);

                continue;
            }

            foreach ($accounts as $account) {
                $account->avatar_id = $fileId;
                $account->save();
                $this->command?->info('Updated avatar: ' . trim($account->first_name . ' ' . $account->last_name) . ' → ' . $url);
            }
        }
    }

    /**
     * @return string|array{0:string,1:int}|null
     */
    protected function uploadOrReuse(string $absolutePath, string $folderSlug, bool $withId = false): string|array|null
    {
        if (! File::exists($absolutePath)) {
            return null;
        }

        $basename = File::basename($absolutePath);
        $expectedUrl = $folderSlug . '/' . $basename;

        $existing = MediaFile::query()->where('url', $expectedUrl)->first();
        if ($existing) {
            $realPath = RvMedia::getRealPath($existing->url);
            if ($realPath) {
                File::ensureDirectoryExists(dirname($realPath));
                File::copy($absolutePath, $realPath);
            }

            return $withId ? [$existing->url, (int) $existing->id] : $existing->url;
        }

        $result = RvMedia::uploadFromPath($absolutePath, 0, $folderSlug);
        if (! empty($result['error'])) {
            $this->command?->error(($result['message'] ?? 'Upload failed') . ' (' . $basename . ')');

            return null;
        }

        $data = $result['data'] ?? null;
        $url = is_object($data) ? $data->url : ($data['url'] ?? null);
        $id = is_object($data) ? (int) $data->id : (int) ($data['id'] ?? 0);

        if (! $url) {
            return null;
        }

        if ($id < 1) {
            $id = (int) MediaFile::query()->where('url', $url)->value('id');
        }

        return $withId ? [$url, $id] : $url;
    }

    protected function applyToPosts(array $slugs, array $names, string $url): int
    {
        $ids = $this->idsFromSlugs($slugs, Post::class);
        $posts = Post::query()
            ->where(function ($query) use ($ids, $names) {
                if ($ids->isNotEmpty()) {
                    $query->orWhereIn('id', $ids);
                }
                foreach ($names as $name) {
                    $query->orWhereRaw('LOWER(name) = ?', [mb_strtolower($name)]);
                }
            })
            ->get();

        foreach ($posts as $post) {
            $post->image = $url;
            $post->save();
        }

        return $posts->count();
    }

    protected function applyToPages(array $slugs, array $names, string $url, int $homepageId): int
    {
        $ids = $this->idsFromSlugs($slugs, Page::class);
        $pages = Page::query()
            ->where(function ($query) use ($ids, $names) {
                if ($ids->isNotEmpty()) {
                    $query->orWhereIn('id', $ids);
                }
                foreach ($names as $name) {
                    $query->orWhereRaw('LOWER(name) = ?', [mb_strtolower($name)]);
                }
            })
            ->get()
            ->filter(function (Page $page) use ($homepageId) {
                if ($homepageId && (int) $page->id === $homepageId) {
                    return false;
                }
                if (BaseHelper::isHomepage($page->id)) {
                    return false;
                }
                $name = html_entity_decode((string) $page->name, ENT_QUOTES | ENT_HTML5);
                if (Str::startsWith(mb_strtolower($name), 'homepage')) {
                    return false;
                }

                return true;
            });

        foreach ($pages as $page) {
            $page->image = $url;
            $page->content = $this->replaceHeroBannerImage((string) $page->content, $url);
            $page->save();

            MetaBox::saveMetaBoxData($page, 'breadcrumb_background_image', $url);
        }

        return $pages->count();
    }

    protected function replaceHeroBannerImage(string $content, string $url): string
    {
        if ($content === '' || ! str_contains($content, 'hero-banner')) {
            return $content;
        }

        $updated = preg_replace(
            '/(\[hero-banner[^\]]*?\sbackground_image=")[^"]*(")/s',
            '$1' . $url . '$2',
            $content,
            1
        );

        if (is_string($updated) && $updated !== $content) {
            return $updated;
        }

        $injected = preg_replace(
            '/\[hero-banner(\s)/',
            '[hero-banner background_image="' . $url . '"$1',
            $content,
            1
        );

        return is_string($injected) ? $injected : $content;
    }

    protected function idsFromSlugs(array $slugs, string $type): \Illuminate\Support\Collection
    {
        return DB::table('slugs')
            ->whereIn('key', $slugs)
            ->where('reference_type', $type)
            ->pluck('reference_id');
    }

    protected function findAccounts(string $fullName, array $usernames)
    {
        $parts = preg_split('/\s+/', trim($fullName)) ?: [];
        $first = $parts[0] ?? '';
        $last = implode(' ', array_slice($parts, 1));

        return Account::query()
            ->where(function ($query) use ($fullName, $first, $last, $usernames) {
                $query->whereRaw(
                    "LOWER(TRIM(CONCAT(IFNULL(first_name,''), ' ', IFNULL(last_name,'')))) = ?",
                    [mb_strtolower($fullName)]
                );
                if ($first !== '' && $last !== '') {
                    $query->orWhere(function ($inner) use ($first, $last) {
                        $inner->whereRaw('LOWER(TRIM(first_name)) = ?', [mb_strtolower($first)])
                            ->whereRaw('LOWER(TRIM(last_name)) = ?', [mb_strtolower($last)]);
                    });
                }
                foreach ($usernames as $username) {
                    $query->orWhereRaw('LOWER(username) = ?', [mb_strtolower($username)]);
                }
            })
            ->get();
    }
}
