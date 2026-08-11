<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Ensure "Terms & Conditions" appears in the footer Our Company simple menu
 * (same CoreSimpleMenu widget as Privacy Policy — not a new widget).
 */
return new class extends Migration
{
    public function up(): void
    {
        $rows = DB::table('widgets')
            ->where('sidebar_id', 'inner_footer_sidebar')
            ->where('widget_id', 'Botble\\Widget\\Widgets\\CoreSimpleMenu')
            ->get();

        foreach ($rows as $row) {
            $data = json_decode((string) $row->data, true);
            if (! is_array($data)) {
                continue;
            }

            $name = (string) ($data['name'] ?? '');
            if (strcasecmp($name, 'Our Company') !== 0) {
                continue;
            }

            $items = $data['items'] ?? null;
            if (! is_array($items)) {
                continue;
            }

            if ($this->menuHasTerms($items)) {
                continue;
            }

            $items[] = [
                ['key' => 'label', 'value' => 'Terms & Conditions'],
                ['key' => 'url', 'value' => '/terms-conditions'],
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

    public function down(): void
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

            $filtered = [];
            foreach ($items as $item) {
                if ($this->itemIsTerms($item)) {
                    continue;
                }
                $filtered[] = $item;
            }

            $data['items'] = array_values($filtered);

            DB::table('widgets')->where('id', $row->id)->update([
                'data' => json_encode($data, JSON_UNESCAPED_SLASHES),
                'updated_at' => now(),
            ]);
        }
    }

    /**
     * @param  array<int, mixed>  $items
     */
    private function menuHasTerms(array $items): bool
    {
        foreach ($items as $item) {
            if ($this->itemIsTerms($item)) {
                return true;
            }
        }

        return false;
    }

    private function itemIsTerms(mixed $item): bool
    {
        if (! is_array($item)) {
            return false;
        }

        $label = '';
        $url = '';
        foreach ($item as $field) {
            if (! is_array($field)) {
                continue;
            }
            $key = (string) ($field['key'] ?? '');
            $value = (string) ($field['value'] ?? '');
            if ($key === 'label') {
                $label = $value;
            }
            if ($key === 'url') {
                $url = $value;
            }
        }

        return str_contains(strtolower($label), 'terms')
            || str_contains(strtolower($url), 'terms-conditions')
            || str_contains(strtolower($url), 'terms-of-service');
    }
};
