<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

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
            if (! is_array($data) || ($data['name'] ?? '') !== 'Our Company') {
                continue;
            }

            $items = $data['items'] ?? [];
            if (! is_array($items)) {
                continue;
            }

            foreach ($items as $item) {
                if (! is_array($item)) {
                    continue;
                }
                $label = null;
                $url = null;
                foreach ($item as $field) {
                    if (! is_array($field)) {
                        continue;
                    }
                    if (($field['key'] ?? '') === 'label') {
                        $label = (string) ($field['value'] ?? '');
                    }
                    if (($field['key'] ?? '') === 'url') {
                        $url = (string) ($field['value'] ?? '');
                    }
                }
                if (
                    strcasecmp((string) $label, 'Terms & Conditions') === 0
                    || strcasecmp((string) $label, 'Terms and Conditions') === 0
                    || $url === '/terms-conditions'
                    || $url === '/terms-of-service'
                ) {
                    return;
                }
            }

            $items[] = [
                [
                    'key' => 'label',
                    'value' => 'Terms & Conditions',
                ],
                [
                    'key' => 'url',
                    'value' => '/terms-conditions',
                ],
                [
                    'key' => 'attributes',
                    'value' => '',
                ],
                [
                    'key' => 'is_open_new_tab',
                    'value' => '0',
                ],
            ];

            $data['items'] = array_values($items);

            DB::table('widgets')->where('id', $row->id)->update([
                'data' => json_encode($data, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE),
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
            if (! is_array($data) || ($data['name'] ?? '') !== 'Our Company') {
                continue;
            }

            $items = $data['items'] ?? [];
            if (! is_array($items)) {
                continue;
            }

            $filtered = [];
            foreach ($items as $item) {
                if (! is_array($item)) {
                    $filtered[] = $item;
                    continue;
                }
                $label = null;
                $url = null;
                foreach ($item as $field) {
                    if (! is_array($field)) {
                        continue;
                    }
                    if (($field['key'] ?? '') === 'label') {
                        $label = (string) ($field['value'] ?? '');
                    }
                    if (($field['key'] ?? '') === 'url') {
                        $url = (string) ($field['value'] ?? '');
                    }
                }
                if (
                    strcasecmp((string) $label, 'Terms & Conditions') === 0
                    || strcasecmp((string) $label, 'Terms and Conditions') === 0
                    || $url === '/terms-conditions'
                ) {
                    continue;
                }
                $filtered[] = $item;
            }

            $data['items'] = array_values($filtered);

            DB::table('widgets')->where('id', $row->id)->update([
                'data' => json_encode($data, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE),
                'updated_at' => now(),
            ]);
        }
    }
};
