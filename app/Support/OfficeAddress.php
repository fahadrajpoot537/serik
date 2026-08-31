<?php

namespace App\Support;

/**
 * Official Serik Realty office address from existing site settings / footer CMS.
 *
 * Does not invent an address. Prefers a configured Google Maps place URL,
 * otherwise builds a search URL from the official address text.
 */
final class OfficeAddress
{
    public const MAPS_LABEL = 'Open Serik Realty office in Google Maps';

    public static function display(): string
    {
        $configured = trim((string) config('serik.office.address', ''));
        if ($configured !== '') {
            return self::plainText($configured);
        }

        $fromWidget = self::fromFooterWidget();
        if ($fromWidget !== '') {
            return $fromWidget;
        }

        try {
            $theme = trim((string) theme_option('address', ''));
            if ($theme !== '') {
                return self::plainText($theme);
            }
        } catch (\Throwable) {
            // Theme options may be unavailable in console/tests.
        }

        $invoice = trim((string) setting('real_estate_company_address_for_invoicing', ''));

        return $invoice !== '' ? self::plainText($invoice) : '';
    }

    public static function mapsUrl(): string
    {
        $place = trim((string) config('serik.office.maps_place_url', ''));
        if ($place !== '' && self::isGoogleMapsUrl($place)) {
            return $place;
        }

        $address = self::display();
        if ($address === '') {
            return '';
        }

        return 'https://www.google.com/maps/search/?api=1&query=' . rawurlencode($address);
    }

    public static function isAddressItem(string $icon, string $text): bool
    {
        $icon = mb_strtolower($icon);
        $looksLikeIcon = str_contains($icon, 'map-pin')
            || str_contains($icon, 'map-pin-filled')
            || str_contains($icon, 'ti-map')
            || str_contains($icon, 'location');

        if ($looksLikeIcon) {
            return true;
        }

        $official = self::display();

        return $official !== '' && strcasecmp(self::plainText($text), $official) === 0;
    }

    public static function isGoogleMapsUrl(string $url): bool
    {
        $host = parse_url($url, PHP_URL_HOST);

        if (! is_string($host) || $host === '') {
            return false;
        }

        $host = mb_strtolower($host);

        return $host === 'maps.google.com'
            || $host === 'www.google.com'
            || str_ends_with($host, '.google.com')
            || $host === 'maps.app.goo.gl'
            || $host === 'goo.gl';
    }

    public static function plainText(string $value): string
    {
        $value = html_entity_decode(strip_tags($value), ENT_QUOTES, 'UTF-8');
        $value = preg_replace('/\s+/u', ' ', str_replace(["\r", "\n"], ' ', $value)) ?? $value;

        return trim($value);
    }

    private static function fromFooterWidget(): string
    {
        try {
            if (! class_exists(\Botble\Widget\Models\Widget::class)) {
                return '';
            }

            $widgets = \Botble\Widget\Models\Widget::query()
                ->where('widget_id', 'SiteInformationWidget')
                ->get();

            foreach ($widgets as $widget) {
                $data = $widget->data;
                if (is_string($data)) {
                    $data = json_decode($data, true);
                }
                if (! is_array($data)) {
                    continue;
                }

                foreach ($data['items'] ?? [] as $item) {
                    $icon = '';
                    $text = '';
                    if (is_array($item) && isset($item[0]) && is_array($item[0])) {
                        foreach ($item as $field) {
                            if (($field['key'] ?? '') === 'icon') {
                                $icon = (string) ($field['value'] ?? '');
                            }
                            if (($field['key'] ?? '') === 'text') {
                                $text = (string) ($field['value'] ?? '');
                            }
                        }
                    } elseif (is_array($item)) {
                        $icon = (string) ($item['icon'] ?? '');
                        $text = (string) ($item['text'] ?? '');
                    }

                    $plain = self::plainText($text);
                    if ($plain === '') {
                        continue;
                    }

                    $iconLower = mb_strtolower($icon);
                    if (str_contains($iconLower, 'map-pin') || str_contains($iconLower, 'ti-map')) {
                        return $plain;
                    }
                }
            }
        } catch (\Throwable) {
            return '';
        }

        return '';
    }
}
