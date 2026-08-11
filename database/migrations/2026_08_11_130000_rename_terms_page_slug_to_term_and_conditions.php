<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Rename Terms page slug terms-conditions → term-and-conditions,
 * and update footer Our Company menu link to match.
 */
return new class extends Migration
{
    private const OLD_SLUG = 'terms-conditions';

    private const NEW_SLUG = 'term-and-conditions';

    public function up(): void
    {
        $this->renameSlug(self::OLD_SLUG, self::NEW_SLUG);
        $this->updateFooterMenuUrl('/' . self::OLD_SLUG, '/' . self::NEW_SLUG);
        $this->ensureFooterTermsLink('/' . self::NEW_SLUG);
    }

    public function down(): void
    {
        $this->renameSlug(self::NEW_SLUG, self::OLD_SLUG);
        $this->updateFooterMenuUrl('/' . self::NEW_SLUG, '/' . self::OLD_SLUG);
    }

    private function renameSlug(string $from, string $to): void
    {
        $existing = DB::table('slugs')->where('key', $to)->first();
        if ($existing) {
            // Target already exists — drop old key if different row.
            DB::table('slugs')
                ->where('key', $from)
                ->where('id', '!=', $existing->id)
                ->delete();

            return;
        }

        DB::table('slugs')
            ->where('key', $from)
            ->where('reference_type', 'Botble\\Page\\Models\\Page')
            ->update([
                'key' => $to,
                'updated_at' => now(),
            ]);
    }

    private function updateFooterMenuUrl(string $fromUrl, string $toUrl): void
    {
        $rows = DB::table('widgets')
            ->where('sidebar_id', 'inner_footer_sidebar')
            ->where('widget_id', 'Botble\\Widget\\Widgets\\CoreSimpleMenu')
            ->get();

        foreach ($rows as $row) {
            $data = json_decode((string) $row->data, true);
            if (! is_array($data) || strcasecmp((string) ($data['name'] ?? ''), 'Our Company') !== 0) {
                continue;
            }

            $items = $data['items'] ?? null;
            if (! is_array($items)) {
                continue;
            }

            $changed = false;
            foreach ($items as $i => $item) {
                if (! is_array($item)) {
                    continue;
                }
                foreach ($item as $j => $field) {
                    if (! is_array($field)) {
                        continue;
                    }
                    if (($field['key'] ?? '') === 'url' && (string) ($field['value'] ?? '') === $fromUrl) {
                        $items[$i][$j]['value'] = $toUrl;
                        $changed = true;
                    }
                }
            }

            if (! $changed) {
                continue;
            }

            $data['items'] = array_values($items);
            DB::table('widgets')->where('id', $row->id)->update([
                'data' => json_encode($data, JSON_UNESCAPED_SLASHES),
                'updated_at' => now(),
            ]);
        }
    }

    private function ensureFooterTermsLink(string $url): void
    {
        $rows = DB::table('widgets')
            ->where('sidebar_id', 'inner_footer_sidebar')
            ->where('widget_id', 'Botble\\Widget\\Widgets\\CoreSimpleMenu')
            ->get();

        foreach ($rows as $row) {
            $data = json_decode((string) $row->data, true);
            if (! is_array($data) || strcasecmp((string) ($data['name'] ?? ''), 'Our Company') !== 0) {
                continue;
            }

            $items = $data['items'] ?? null;
            if (! is_array($items)) {
                continue;
            }

            foreach ($items as $item) {
                if (! is_array($item)) {
                    continue;
                }
                foreach ($item as $field) {
                    if (! is_array($field)) {
                        continue;
                    }
                    if (($field['key'] ?? '') === 'url' && str_contains(strtolower((string) ($field['value'] ?? '')), 'term')) {
                        return;
                    }
                }
            }

            $items[] = [
                ['key' => 'label', 'value' => 'Terms & Conditions'],
                ['key' => 'url', 'value' => $url],
                ['key' => 'attributes', 'value' => ''],
                ['key' => 'is_open_new_tab', 'value' => '0'],
            ];

            $data['items'] = array_values($items);
            DB::table('widgets')->where('id', $row->id)->update([
                'data' => json_encode($data, JSON_UNESCAPED_SLASHES),
                'updated_at' => now(),
            ]);
        }
    }
};
