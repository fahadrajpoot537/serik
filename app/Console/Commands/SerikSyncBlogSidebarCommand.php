<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Align blog_sidebar widgets with the local Homzen layout:
 * Search, Recent Posts, By Categories, Popular Tag.
 */
class SerikSyncBlogSidebarCommand extends Command
{
    protected $signature = 'serik:sync-blog-sidebar
        {--theme=homzen : Theme name}
        {--dry-run : Show planned changes without writing}';

    protected $description = 'Sync blog_sidebar widgets to Search + Recent Posts + By Categories + Popular Tag';

    public function handle(): int
    {
        if (! Schema::hasTable('widgets')) {
            $this->error('widgets table not found.');

            return self::FAILURE;
        }

        $theme = (string) $this->option('theme');
        $dry = (bool) $this->option('dry-run');
        $sidebar = 'blog_sidebar';

        $desired = [
            [
                'widget_id' => 'BlogSearchWidget',
                'position' => 1,
                'data' => ['name' => 'Search'],
            ],
            [
                'widget_id' => 'BlogPostsWidget',
                'position' => 2,
                'data' => ['name' => 'Recent Posts', 'limit' => 3],
            ],
            [
                'widget_id' => 'BlogCategoriesWidget',
                'position' => 3,
                'data' => ['name' => 'By Categories', 'number_display' => 8],
            ],
            [
                'widget_id' => 'BlogTagsWidget',
                'position' => 4,
                'data' => ['name' => 'Popular Tag', 'number_display' => 9],
            ],
        ];

        $existing = DB::table('widgets')
            ->where('sidebar_id', $sidebar)
            ->where('theme', $theme)
            ->orderBy('position')
            ->get();

        $this->info("Current {$theme}/{$sidebar} widgets: " . $existing->count());
        foreach ($existing as $row) {
            $this->line("  #{$row->id} {$row->widget_id} pos={$row->position} data={$row->data}");
        }

        if ($dry) {
            $this->warn('Dry run — no changes written.');

            return self::SUCCESS;
        }

        DB::transaction(function () use ($existing, $desired, $sidebar, $theme) {
            // Remove unknown / duplicate sidebar widgets for this theme.
            $keepIds = [];
            foreach ($desired as $item) {
                $match = $existing->firstWhere('widget_id', $item['widget_id']);
                $payload = [
                    'widget_id' => $item['widget_id'],
                    'sidebar_id' => $sidebar,
                    'theme' => $theme,
                    'position' => $item['position'],
                    'data' => json_encode($item['data'], JSON_UNESCAPED_UNICODE),
                    'updated_at' => now(),
                ];

                if ($match) {
                    DB::table('widgets')->where('id', $match->id)->update($payload);
                    $keepIds[] = (int) $match->id;
                    $this->line("Updated {$item['widget_id']} (#{$match->id})");
                } else {
                    $id = DB::table('widgets')->insertGetId($payload + [
                        'created_at' => now(),
                    ]);
                    $keepIds[] = (int) $id;
                    $this->line("Inserted {$item['widget_id']} (#{$id})");
                }
            }

            $removed = DB::table('widgets')
                ->where('sidebar_id', $sidebar)
                ->where('theme', $theme)
                ->whereNotIn('id', $keepIds)
                ->delete();

            if ($removed > 0) {
                $this->line("Removed {$removed} extra sidebar widget(s).");
            }
        });

        $this->info('Blog sidebar synced to local layout.');
        $this->comment('Run: php artisan view:clear && php artisan cache:clear');

        return self::SUCCESS;
    }
}
