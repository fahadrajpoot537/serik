<?php

namespace Database\Seeders;

use Botble\Media\Facades\RvMedia;
use Botble\Media\Models\MediaFile;
use Botble\Page\Models\Page;
use Botble\RealEstate\Models\Account;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;

/**
 * Idempotent homepage “Meet Our Experts” lineup + avatar sync.
 *
 * Safe to re-run. Does not delete accounts (Himmat Brar kept in DB).
 * Only updates homepage agents shortcode account_ids + mapped avatars.
 *
 * php artisan db:seed --class="Database\\Seeders\\HomepageMeetOurExpertsSeeder" --force
 */
class HomepageMeetOurExpertsSeeder extends Seeder
{
    /**
     * Required homepage order. Each entry resolves by slug aliases / name hints.
     *
     * @var list<array{key:string,label:string,slugs:list<string>,names:list<string>,photo:?string}>
     */
    private const LINEUP = [
        [
            'key' => 'himanshu',
            'label' => 'Himanshu',
            'slugs' => ['himanshu'],
            'names' => ['Himanshu Sood', 'Himanshu'],
            'photo' => 'himanshu.webp',
        ],
        [
            'key' => 'pooja',
            'label' => 'Pooja',
            'slugs' => ['p', 'pooja'],
            'names' => ['Pooja Thakurel', 'Pooja'],
            'photo' => 'pooja.webp',
        ],
        [
            'key' => 'fahad',
            'label' => 'Fahad',
            'slugs' => ['fahad'],
            'names' => ['Fahad Yakub', 'Fahad'],
            'photo' => 'fahad.webp',
        ],
        [
            'key' => 'mickey',
            'label' => 'Mickey',
            'slugs' => ['mickey', 'micki'],
            'names' => ['Mickey', 'Micki', 'Micki Luther'],
            'photo' => 'micki.webp',
        ],
        [
            'key' => 'akash-gaba',
            'label' => 'Akash Gaba',
            'slugs' => ['akash-gaba', 'akashgaba'],
            'names' => ['Akash Gaba'],
            // Intentionally not "akash.webp" / agents/akash (Akash(Micki) Luther).
            'photo' => 'akash-gaba.webp',
        ],
        [
            'key' => 'gary',
            'label' => 'Gary',
            'slugs' => ['gary'],
            'names' => ['Gary Sodhi', 'Gary'],
            'photo' => 'gary.webp',
        ],
        [
            'key' => 'sadaqat',
            'label' => 'Sadaqat',
            'slugs' => ['sadaqat', 'sadaquat'],
            'names' => ['Sadaqat Sheikh', 'Sadaqat', 'Sadaquat'],
            'photo' => 'sadaqat.webp',
        ],
        [
            'key' => 'harmeet',
            'label' => 'Harmeet',
            // Live profile is Harneet Jhajj (/agents/harneet); no /agents/harmeet.
            'slugs' => ['harneet', 'harmeet'],
            'names' => ['Harneet Jhajj', 'Harmeet', 'Harneet'],
            'photo' => 'harneet.webp',
        ],
        [
            'key' => 'kiran',
            'label' => 'Kiran',
            'slugs' => ['kiran'],
            'names' => ['Kiran Kaur', 'Kiran'],
            'photo' => 'kiran.webp',
        ],
    ];

    public function run(): void
    {
        $resolved = [];
        $missingAgents = [];
        $missingPhotos = [];

        foreach (self::LINEUP as $row) {
            $photo = $row['photo'];
            $photoPath = $photo ? $this->resolvePhotoPath($photo) : null;
            if ($photo && ! $photoPath) {
                $missingPhotos[] = $photo . ' → ' . $row['label'];
            }

            $account = $this->findAccount($row['slugs'], $row['names']);
            if (! $account) {
                $missingAgents[] = $row['label'] . ' (slugs: ' . implode(', ', $row['slugs']) . ')';
                continue;
            }

            if ($photoPath) {
                $upload = $this->uploadOrReuse($photoPath);
                if ($upload) {
                    [$url, $fileId] = $upload;
                    if ((int) $account->avatar_id !== $fileId) {
                        $account->avatar_id = $fileId;
                        $account->save();
                    }
                    $this->command?->info('Avatar: ' . trim($account->first_name . ' ' . $account->last_name) . ' → ' . $url);
                }
            }

            $resolved[] = (int) $account->id;
        }

        if ($missingAgents !== []) {
            $this->command?->error('Missing agent profiles (homepage lineup incomplete):');
            foreach ($missingAgents as $line) {
                $this->command?->warn('  - ' . $line);
            }
        }

        if ($missingPhotos !== []) {
            $this->command?->error('Missing Circle image files (place under database/seeders/files/team-avatars/ or public/pictures/Brokers Team DPs/):');
            foreach ($missingPhotos as $line) {
                $this->command?->warn('  - ' . $line);
            }
        }

        if (count($resolved) !== count(self::LINEUP) || $missingPhotos !== []) {
            $this->command?->error(sprintf(
                'Refusing to update homepage shortcode: resolved %d/%d experts, missing photos %d. Fix missing profiles/photos, then re-run.',
                count($resolved),
                count(self::LINEUP),
                count($missingPhotos)
            ));

            return;
        }

        $idsCsv = implode(',', $resolved);
        $updated = $this->updateHomepageAgentsShortcode($idsCsv);

        if ($updated < 1) {
            $this->command?->warn('Homepage agents shortcode not found / unchanged.');
        } else {
            $this->command?->info('Homepage Meet Our Experts account_ids → ' . $idsCsv);
        }

        // Himmat stays in DB; only removed from homepage shortcode via account_ids replace.
        $this->command?->info('Himmat Brar account was not deleted (lineup-only change).');

        $this->command?->call('cache:clear');
        if (class_exists(\App\Support\HomepageFragmentCache::class)) {
            \App\Support\HomepageFragmentCache::bump('shortcode:agents');
        }
    }

    /**
     * @param  list<string>  $slugs
     * @param  list<string>  $names
     */
    protected function findAccount(array $slugs, array $names): ?Account
    {
        $ids = DB::table('slugs')
            ->where('prefix', 'agents')
            ->whereIn('key', $slugs)
            ->where('reference_type', Account::class)
            ->pluck('reference_id');

        if ($ids->isNotEmpty()) {
            $account = Account::query()->whereIn('id', $ids)->orderBy('id')->first();
            if ($account) {
                return $account;
            }
        }

        foreach ($slugs as $slug) {
            $account = Account::query()->whereRaw('LOWER(username) = ?', [mb_strtolower($slug)])->first();
            if ($account) {
                return $account;
            }
        }

        foreach ($names as $fullName) {
            $parts = preg_split('/\s+/', trim($fullName)) ?: [];
            $first = $parts[0] ?? '';
            $last = implode(' ', array_slice($parts, 1));
            // Require a last name for name matching so stubs like first-only "Fahad" are ignored.
            if ($first === '' || $last === '') {
                continue;
            }
            $account = Account::query()
                ->where(function ($q) use ($fullName, $first, $last) {
                    $q->whereRaw(
                        "LOWER(TRIM(CONCAT(IFNULL(first_name,''), ' ', IFNULL(last_name,'')))) = ?",
                        [mb_strtolower($fullName)]
                    )->orWhere(function ($inner) use ($first, $last) {
                        $inner->whereRaw('LOWER(TRIM(first_name)) = ?', [mb_strtolower($first)])
                            ->whereRaw('LOWER(TRIM(last_name)) = ?', [mb_strtolower($last)]);
                    });
                })
                ->first();
            if ($account) {
                return $account;
            }
        }

        return null;
    }

    protected function resolvePhotoPath(string $basename): ?string
    {
        $candidates = [
            database_path('seeders/files/team-avatars/' . $basename),
            public_path('pictures/Brokers Team DPs/' . $basename),
        ];

        foreach ($candidates as $path) {
            if (File::isFile($path)) {
                return $path;
            }
        }

        return null;
    }

    /**
     * @return array{0:string,1:int}|null
     */
    protected function uploadOrReuse(string $absolutePath): ?array
    {
        $basename = File::basename($absolutePath);
        $folderSlug = 'team-avatars';
        $expectedUrl = $folderSlug . '/' . $basename;

        $existing = MediaFile::query()->where('url', $expectedUrl)->first();
        if ($existing) {
            $realPath = RvMedia::getRealPath($existing->url);
            if ($realPath) {
                File::ensureDirectoryExists(dirname($realPath));
                File::copy($absolutePath, $realPath);
            }

            return [$existing->url, (int) $existing->id];
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

        return [$url, $id];
    }

    protected function updateHomepageAgentsShortcode(string $idsCsv): int
    {
        $homepageId = (int) theme_option('homepage_id');
        $pages = Page::query()
            ->when($homepageId > 0, fn ($q) => $q->where('id', $homepageId))
            ->orWhere('name', 'like', 'Homepage%')
            ->get();

        $updated = 0;
        foreach ($pages as $page) {
            $content = (string) $page->content;
            if ($content === '' || ! str_contains($content, '[agents')) {
                continue;
            }

            $next = preg_replace(
                '/(\[agents\b[^\]]*?\saccount_ids=")[^"]*(")/s',
                '$1' . $idsCsv . '$2',
                $content,
                1,
                $count
            );

            if (! is_string($next) || $count < 1) {
                // Inject account_ids if missing on the Meet Our Experts shortcode.
                $next = preg_replace(
                    '/(\[agents\b[^\]]*?\btitle="Meet Our Experts"[^\]]*)(\])/s',
                    '$1 account_ids="' . $idsCsv . '"$2',
                    $content,
                    1,
                    $count2
                );
                if (! is_string($next) || $count2 < 1) {
                    continue;
                }
            }

            if ($next === $content) {
                continue;
            }

            $page->content = $next;
            $page->save();
            $updated++;
        }

        return $updated;
    }
}
