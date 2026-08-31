<?php

namespace App\Support;

/**
 * Structured Canadian/Ontario address parsing and ranking for MLS search.
 *
 * Used at query time (and tests) so civic-number searches require every
 * significant street-name token. Display addresses are never rewritten.
 */
final class AddressNormalizer
{
    public const MAX_QUERY_LENGTH = 200;

    /** @var array<string, string> lowercase alias → canonical suffix */
    private const SUFFIX_ALIASES = [
        'street' => 'street',
        'st' => 'street',
        'road' => 'road',
        'rd' => 'road',
        'avenue' => 'avenue',
        'ave' => 'avenue',
        'boulevard' => 'boulevard',
        'blvd' => 'boulevard',
        'drive' => 'drive',
        'dr' => 'drive',
        'lane' => 'lane',
        'ln' => 'lane',
        'court' => 'court',
        'ct' => 'court',
        'place' => 'place',
        'pl' => 'place',
        'terrace' => 'terrace',
        'terr' => 'terrace',
        'ter' => 'terrace',
        'trail' => 'trail',
        'trl' => 'trail',
        'highway' => 'highway',
        'hwy' => 'highway',
        'crescent' => 'crescent',
        'cres' => 'crescent',
        'cr' => 'crescent',
        'circle' => 'circle',
        'cir' => 'circle',
        'parkway' => 'parkway',
        'pkwy' => 'parkway',
        'grove' => 'grove',
        'gate' => 'gate',
        'gardens' => 'gardens',
        'square' => 'square',
        'sq' => 'square',
        'close' => 'close',
        'mews' => 'mews',
        'row' => 'row',
        'path' => 'path',
        'passage' => 'passage',
        'way' => 'way',
    ];

    /** @var array<string, string> */
    private const SUFFIX_DISPLAY = [
        'street' => 'Street',
        'road' => 'Road',
        'avenue' => 'Avenue',
        'boulevard' => 'Boulevard',
        'drive' => 'Drive',
        'lane' => 'Lane',
        'court' => 'Court',
        'place' => 'Place',
        'terrace' => 'Terrace',
        'trail' => 'Trail',
        'highway' => 'Highway',
        'crescent' => 'Crescent',
        'circle' => 'Circle',
        'parkway' => 'Parkway',
        'grove' => 'Grove',
        'gate' => 'Gate',
        'gardens' => 'Gardens',
        'square' => 'Square',
        'close' => 'Close',
        'mews' => 'Mews',
        'row' => 'Row',
        'path' => 'Path',
        'passage' => 'Passage',
        'way' => 'Way',
    ];

    /** @var list<string> */
    private const DIRECTIONALS = [
        'n', 's', 'e', 'w', 'ne', 'nw', 'se', 'sw',
        'north', 'south', 'east', 'west',
        'northeast', 'northwest', 'southeast', 'southwest',
    ];

    /** @var list<string> */
    private const PROVINCES = [
        'on', 'ontario', 'qc', 'quebec', 'bc', 'ab', 'mb', 'sk',
        'ns', 'nb', 'nl', 'pe', 'yt', 'nt', 'nu',
    ];

    /**
     * @return array{
     *   street_number: string,
     *   street_name: string,
     *   street_part: string,
     *   street_suffix: string,
     *   street_suffix_normalized: string,
     *   street_direction: string,
     *   municipality: string,
     *   province: string,
     *   postal_code: string,
     *   unit: string,
     *   unit_number: string,
     *   significant_tokens: list<string>
     * }|null
     */
    public static function parseQuery(string $keyword): ?array
    {
        $keyword = self::normalizeWhitespace($keyword);
        if ($keyword === '') {
            return null;
        }

        if (mb_strlen($keyword) > self::MAX_QUERY_LENGTH) {
            $keyword = mb_substr($keyword, 0, self::MAX_QUERY_LENGTH);
        }

        if (preg_match('/^[a-z]{1,2}\d{5,}$/i', $keyword)) {
            return null;
        }

        $compactPostal = strtoupper(preg_replace('/\s+/', '', $keyword) ?? '');
        if (preg_match('/^[A-Z]\d[A-Z]\d[A-Z]\d$/', $compactPostal) || preg_match('/^[A-Z]\d[A-Z]$/', $compactPostal)) {
            return null;
        }

        $postalCode = '';
        if (preg_match('/\b([A-Za-z]\d[A-Za-z])\s?(\d[A-Za-z]\d)\b/', $keyword, $pm)) {
            $postalCode = strtoupper($pm[1] . ' ' . $pm[2]);
            $keyword = trim(str_replace($pm[0], ' ', $keyword));
            $keyword = self::normalizeWhitespace($keyword);
        }

        $municipality = '';
        $province = '';
        $head = $keyword;

        if (str_contains($keyword, ',')) {
            $parts = array_values(array_filter(array_map('trim', explode(',', $keyword))));
            $head = $parts[0] ?? $keyword;
            foreach (array_slice($parts, 1) as $segment) {
                $segNorm = self::fold($segment);
                if (in_array($segNorm, self::PROVINCES, true) || self::isPostalToken($segNorm)) {
                    if (in_array($segNorm, self::PROVINCES, true) && $province === '') {
                        $province = strtoupper($segNorm === 'ontario' ? 'ON' : $segment);
                    }
                    continue;
                }
                if ($municipality === '') {
                    $municipality = self::titleCaseMunicipality($segment);
                }
            }
        }

        if (str_contains($head, ' - ')) {
            $head = trim(explode(' - ', $head, 2)[0]);
        }

        $head = trim(preg_replace('/\b(ON|Ontario|QC|Quebec|BC|AB|MB|SK|NS|NB|NL|PE|YT|NT|NU)\b.*$/i', '', $head) ?? $head);
        $head = self::normalizeWhitespace($head);

        $unitNumber = '';
        if (preg_match('/^(\d{1,5}[A-Za-z]?|PH\d+|TH\d+|[A-Za-z]{1,3}-?\d+)\s+(\d+[A-Za-z]?)\s+(.+)$/i', $head, $unitMatch)) {
            $unitNumber = \Theme\homzen\Supports\TrebPropertyHelper::normalizeUnitToken($unitMatch[1]);
            $head = trim($unitMatch[2] . ' ' . $unitMatch[3]);
        }

        if (! preg_match('/^(\d+[A-Za-z]?)\s+(.+)$/u', $head, $matches)) {
            return null;
        }

        $streetNumber = $matches[1];
        $tokens = preg_split('/\s+/', self::tokenizeForParse($matches[2])) ?: [];
        $tokens = array_values(array_filter($tokens, static fn ($t) => $t !== ''));

        if ($tokens === []) {
            return null;
        }

        $nameTokens = [];
        $suffix = '';
        $suffixNorm = '';
        $direction = '';
        $leftover = [];
        $seenSuffix = false;

        foreach ($tokens as $token) {
            $folded = self::fold($token);

            if (! $seenSuffix) {
                $canonical = self::canonicalSuffix($folded);
                if ($canonical !== null && $nameTokens !== []) {
                    $seenSuffix = true;
                    $suffixNorm = $canonical;
                    $suffix = self::SUFFIX_DISPLAY[$canonical] ?? ucfirst($canonical);
                    continue;
                }
                $nameTokens[] = $token;
                continue;
            }

            if ($direction === '' && in_array($folded, self::DIRECTIONALS, true)) {
                $direction = $token;
                continue;
            }

            if (in_array($folded, self::PROVINCES, true) || self::isPostalToken($folded)) {
                continue;
            }

            $leftover[] = $token;
        }

        if ($nameTokens === []) {
            return null;
        }

        if ($municipality === '' && $leftover !== []) {
            $municipality = self::titleCaseMunicipality(implode(' ', $leftover));
        }

        $streetName = implode(' ', $nameTokens);
        $significant = [];
        foreach ($nameTokens as $token) {
            $folded = self::fold($token);
            if ($folded === '' || in_array($folded, self::DIRECTIONALS, true)) {
                continue;
            }
            $significant[] = $folded;
        }

        if ($significant === []) {
            return null;
        }

        $streetPart = trim($streetName . ($suffix !== '' ? ' ' . $suffix : ''));

        return [
            'street_number' => $streetNumber,
            'street_name' => $streetName,
            'street_part' => $streetPart,
            'street_suffix' => $suffix,
            'street_suffix_normalized' => $suffixNorm,
            'street_direction' => $direction,
            'municipality' => $municipality,
            'province' => $province,
            'postal_code' => $postalCode,
            'unit' => $unitNumber,
            'unit_number' => $unitNumber,
            'significant_tokens' => $significant,
        ];
    }

    /**
     * Meilisearch query string: civic + full street name + canonical suffix + city.
     */
    public static function meiliQuery(array $parsed): string
    {
        $parts = [
            (string) ($parsed['street_number'] ?? ''),
            (string) ($parsed['street_name'] ?? ''),
            (string) ($parsed['street_suffix'] ?? ''),
            (string) ($parsed['municipality'] ?? ''),
        ];

        return self::normalizeWhitespace(implode(' ', $parts));
    }

    public static function addressMatches(string $address, array $parsed): bool
    {
        return self::scoreAddress($address, $parsed) >= 4000;
    }

    /**
     * Deterministic address score. Missing significant street tokens score 0
     * so they cannot occupy high ranking tiers.
     */
    public static function scoreAddress(string $address, array $parsed, ?string $mlsStatus = null): int
    {
        $hay = ' ' . self::fold($address) . ' ';
        $number = self::fold((string) ($parsed['street_number'] ?? ''));
        $tokens = $parsed['significant_tokens'] ?? [];
        if ($number === '' || $tokens === []) {
            return 0;
        }

        if (! preg_match('/\b' . preg_quote($number, '/') . '\b/', $hay)) {
            return 0;
        }

        $missing = 0;
        $fuzzy = 0;
        $exactTokens = 0;
        foreach ($tokens as $token) {
            if (preg_match('/\b' . preg_quote($token, '/') . '\b/', $hay)) {
                $exactTokens++;
                continue;
            }
            if (self::fuzzyTokenInHaystack($token, $hay)) {
                $fuzzy++;
                continue;
            }
            $missing++;
        }

        if ($missing > 0) {
            return 0;
        }

        $suffixNorm = (string) ($parsed['street_suffix_normalized'] ?? '');
        $suffixHit = $suffixNorm === '' || self::addressHasSuffix($hay, $suffixNorm);

        $municipality = self::fold((string) ($parsed['municipality'] ?? ''));
        $cityHit = $municipality === '' || preg_match('/\b' . preg_quote($municipality, '/') . '\b/', $hay);

        $unit = self::fold((string) ($parsed['unit_number'] ?? $parsed['unit'] ?? ''));
        $unitHit = $unit === '' || preg_match('/\b' . preg_quote($unit, '/') . '\b/', $hay);
        if ($unit !== '' && ! $unitHit) {
            return 0;
        }

        $phrase = self::fold(trim(($parsed['street_number'] ?? '') . ' ' . ($parsed['street_name'] ?? '')));
        $phraseHit = $phrase !== '' && str_contains($hay, $phrase);

        $score = 0;
        if ($phraseHit && $suffixHit && $cityHit && $unitHit && $fuzzy === 0) {
            $score = 10000;
        } elseif ($exactTokens === count($tokens) && $cityHit && $fuzzy === 0) {
            $score = 8000;
        } elseif ($exactTokens === count($tokens) && $fuzzy === 0) {
            $score = 7000;
        } elseif ($fuzzy > 0 && $missing === 0) {
            $score = 5000;
        } else {
            $score = 4000;
        }

        if ($suffixHit) {
            $score += 80;
        }
        if ($cityHit && $municipality !== '') {
            $score += 120;
        } elseif ($municipality !== '' && ! $cityHit) {
            $score -= 400;
        }
        if ($unitHit && $unit !== '') {
            $score += 40;
        }
        if ($phraseHit) {
            $score += 60;
        }

        $status = trim((string) $mlsStatus);
        $score += match (true) {
            in_array($status, ['New', 'Active', 'Active Under Contract', 'Price Change', 'Extension', 'Ext', 'Previous Status'], true) => 12,
            str_contains($status, 'Sold') || str_contains($status, 'Leased') => 4,
            default => 1,
        };

        return $score;
    }

    /**
     * @param  array<int, array<string, mixed>>  $rows
     * @return array<int, array<string, mixed>>
     */
    public static function sortRows(array $rows, array $parsed): array
    {
        usort($rows, static function ($a, $b) use ($parsed) {
            $addrA = (string) ($a['UnparsedAddress'] ?? $a['name'] ?? '');
            $addrB = (string) ($b['UnparsedAddress'] ?? $b['name'] ?? '');
            $cmp = self::scoreAddress($addrB, $parsed, $b['MlsStatus'] ?? null)
                <=> self::scoreAddress($addrA, $parsed, $a['MlsStatus'] ?? null);
            if ($cmp !== 0) {
                return $cmp;
            }

            return strcasecmp((string) ($a['ListingKey'] ?? ''), (string) ($b['ListingKey'] ?? ''));
        });

        return array_values($rows);
    }

    public static function isStreetSuffix(string $token): bool
    {
        return self::canonicalSuffix(self::fold($token)) !== null;
    }

    public static function canonicalSuffix(string $folded): ?string
    {
        $folded = trim($folded, '.');
        if ($folded === '') {
            return null;
        }

        if (isset(self::SUFFIX_ALIASES[$folded])) {
            return self::SUFFIX_ALIASES[$folded];
        }

        foreach (self::SUFFIX_ALIASES as $alias => $canonical) {
            if (mb_strlen($folded) >= 6 && mb_strlen($alias) >= 6 && levenshtein($folded, $alias) === 1) {
                return $canonical;
            }
        }

        return null;
    }

    public static function normalizeWhitespace(string $value): string
    {
        $value = trim($value);
        if ($value === '') {
            return '';
        }

        if (class_exists(\Normalizer::class)) {
            $normalized = \Normalizer::normalize($value, \Normalizer::FORM_C);
            if (is_string($normalized) && $normalized !== '') {
                $value = $normalized;
            }
        }

        $value = str_replace(["\u{00A0}", "\u{2019}", "\u{2018}"], [' ', "'", "'"], $value);
        $value = preg_replace('/\s+/u', ' ', $value) ?? $value;

        return trim($value);
    }

    public static function fold(string $value): string
    {
        $value = mb_strtolower(self::normalizeWhitespace($value));
        $value = str_replace(['.', ',', '#', "'"], ' ', $value);
        $value = preg_replace('/\s+/u', ' ', $value) ?? $value;

        return trim($value);
    }

    private static function tokenizeForParse(string $rest): string
    {
        $rest = str_replace(['.', ',', '#'], ' ', $rest);

        return self::normalizeWhitespace($rest);
    }

    private static function isPostalToken(string $folded): bool
    {
        $compact = strtoupper(str_replace(' ', '', $folded));

        return (bool) preg_match('/^[A-Z]\d[A-Z]\d[A-Z]\d$/', $compact);
    }

    private static function titleCaseMunicipality(string $value): string
    {
        $value = trim($value, " \t\n\r\0\x0B,.");
        if ($value === '') {
            return '';
        }

        return mb_convert_case($value, MB_CASE_TITLE, 'UTF-8');
    }

    private static function addressHasSuffix(string $foldedHaystack, string $canonical): bool
    {
        foreach (self::SUFFIX_ALIASES as $alias => $target) {
            if ($target !== $canonical) {
                continue;
            }
            if (preg_match('/\b' . preg_quote($alias, '/') . '\b/', $foldedHaystack)) {
                return true;
            }
        }

        return false;
    }

    private static function fuzzyTokenInHaystack(string $token, string $foldedHaystack): bool
    {
        if (mb_strlen($token) < 6) {
            return false;
        }

        preg_match_all('/[a-z0-9]+/u', $foldedHaystack, $matches);
        foreach ($matches[0] ?? [] as $word) {
            if (mb_strlen($word) < 6) {
                continue;
            }
            if (levenshtein($token, $word) === 1) {
                return true;
            }
        }

        return false;
    }
}
