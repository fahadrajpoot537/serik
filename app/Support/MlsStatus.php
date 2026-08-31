<?php

namespace App\Support;

use Carbon\Carbon;
use Carbon\CarbonInterface;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Single source of truth for AMPRE/TRREB MlsStatus → public listing labels.
 *
 * Raw field: Property.MlsStatus (AMPRE OData `MlsStatus`).
 * Do not infer status from price, photos, cache, or moderation.
 */
final class MlsStatus
{
    public const EXPIRED = 'Expired';

    public const SUSPENDED = 'Suspended';

    public const CANCELLED = 'Cancelled';

    public const TERMINATED = 'Terminated';

    public const WITHDRAWN = 'Withdrawn';

    public const UNAVAILABLE = 'Unavailable';

    public const SOLD = 'Sold';

    public const LEASED = 'Leased';

    /**
     * Verified AMPRE/TRREB MlsStatus tokens (lowercase key → display label).
     *
     * @var array<string, string>
     */
    private const RAW_MAP = [
        'expired' => self::EXPIRED,
        'suspended' => self::SUSPENDED,
        'cancelled' => self::CANCELLED,
        'canceled' => self::CANCELLED,
        'terminated' => self::TERMINATED,
        'withdrawn' => self::WITHDRAWN,
    ];

    /**
     * @var list<string>
     */
    private const ACTIVE = [
        'New',
        'Price Change',
        'Extension',
        'Ext',
        'Previous Status',
        'Active',
        'Active Under Contract',
    ];

    /**
     * AMPRE sold / sold-conditional tokens. Name must not start with SOLD
     * (Intelephense treats that as a second App\Support\MlsStatus::SOLD).
     *
     * @var list<string>
     */
    private const CLOSED_SALE_VALUES = [
        'Sold',
        'Sold Conditional',
        'Sold Conditional Escape',
    ];

    /**
     * AMPRE leased / leased-conditional tokens. Name must not start with LEASED.
     *
     * @var list<string>
     */
    private const CLOSED_LEASE_VALUES = [
        'Leased',
        'Leased Conditional',
    ];

    /**
     * DB / filter values for de-listed listings (includes spelling variants).
     *
     * @return list<string>
     */
    public static function delistedQueryValues(): array
    {
        return [
            self::EXPIRED,
            self::SUSPENDED,
            self::CANCELLED,
            'Canceled',
            self::TERMINATED,
            self::WITHDRAWN,
        ];
    }

    /**
     * @return list<string>
     */
    public static function soldQueryValues(): array
    {
        return array_merge(self::CLOSED_SALE_VALUES, self::CLOSED_LEASE_VALUES);
    }

    /**
     * @return list<string>
     */
    public static function activeQueryValues(): array
    {
        return self::ACTIVE;
    }

    /**
     * @return list<string>
     */
    public static function inactiveQueryValues(): array
    {
        return array_values(array_unique(array_merge(
            self::soldQueryValues(),
            self::delistedQueryValues()
        )));
    }

    public static function key(?string $raw): string
    {
        $value = strtolower(trim(preg_replace('/\s+/', ' ', (string) $raw) ?? ''));

        return $value;
    }

    /**
     * @param  array{expire_date?: mixed, transaction_type?: string|null}  $context
     * @return array{
     *   raw_status: string,
     *   normalized_status: string,
     *   display_label: string,
     *   is_active: bool,
     *   is_sold: bool,
     *   is_leased: bool,
     *   is_delisted: bool,
     *   status_date: ?string,
     *   status_date_label: ?string,
     *   status_date_field: ?string,
     *   compact_label: string,
     *   badge_variant: string,
     *   strike_price: bool
     * }
     */
    public static function resolve(?string $raw, array $context = []): array
    {
        $rawStatus = trim((string) $raw);
        $key = self::key($rawStatus);
        $transactionType = trim((string) ($context['transaction_type'] ?? ''));

        $isSold = in_array($rawStatus, self::CLOSED_SALE_VALUES, true) || str_starts_with($key, 'sold');
        $isLeased = in_array($rawStatus, self::CLOSED_LEASE_VALUES, true);
        $isActive = in_array($rawStatus, self::ACTIVE, true)
            || in_array($key, ['new', 'price change', 'extension', 'ext', 'previous status', 'active', 'active under contract'], true);

        $normalized = self::RAW_MAP[$key] ?? null;
        $isDelisted = $normalized !== null;

        if ($normalized === null && ! $isSold && ! $isLeased && ! $isActive && $rawStatus !== '') {
            self::noteUnknown($rawStatus);
            $normalized = self::UNAVAILABLE;
            $isDelisted = true;
            $isActive = false;
        }

        if ($normalized === null) {
            if ($isLeased) {
                $normalized = self::LEASED;
            } elseif ($isSold) {
                $normalized = self::SOLD;
            } elseif ($isActive) {
                $normalized = $transactionType === 'For Lease' || $transactionType === 'For Sub-Lease'
                    ? 'For Lease'
                    : 'For Sale';
            } else {
                $normalized = $rawStatus !== '' ? $rawStatus : 'For Sale';
            }
        }

        $display = $normalized;
        if ($isActive && ! $isDelisted && ! $isSold && ! $isLeased) {
            $display = ($transactionType === 'For Lease' || $transactionType === 'For Sub-Lease')
                ? 'For Lease'
                : 'For Sale';
            $normalized = $display;
        }

        $statusDate = $isDelisted
            ? self::publicDate($normalized, $context['expire_date'] ?? null)
            : null;
        $dateLabel = $statusDate ? $statusDate->timezone(self::timezoneId())->format('M j, Y') : null;
        $compact = $dateLabel ? ($display . ' · ' . $dateLabel) : $display;

        $variant = match (true) {
            $isLeased => 'leased',
            $isSold => 'sold',
            $isDelisted && $normalized === self::EXPIRED => 'expired',
            $isDelisted && $normalized === self::SUSPENDED => 'suspended',
            $isDelisted && $normalized === self::CANCELLED => 'cancelled',
            $isDelisted && $normalized === self::TERMINATED => 'terminated',
            $isDelisted && $normalized === self::WITHDRAWN => 'withdrawn',
            $isDelisted => 'unavailable',
            $display === 'For Lease' => 'for-lease',
            default => 'for-sale',
        };

        return [
            'raw_status' => $rawStatus,
            'normalized_status' => $normalized,
            'display_label' => $display,
            'is_active' => $isActive && ! $isDelisted && ! $isSold && ! $isLeased,
            'is_sold' => $isSold,
            'is_leased' => $isLeased,
            'is_delisted' => $isDelisted,
            'status_date' => $statusDate?->toDateString(),
            'status_date_label' => $dateLabel,
            'status_date_field' => $statusDate ? 'expire_date' : null,
            'compact_label' => $compact,
            'badge_variant' => $variant,
            'strike_price' => $isDelisted,
        ];
    }

    public static function forProperty(object $property): array
    {
        return self::resolve(
            isset($property->MlsStatus) ? (string) $property->MlsStatus : '',
            [
                'expire_date' => $property->expire_date ?? null,
                'transaction_type' => isset($property->TransactionType) ? (string) $property->TransactionType : '',
            ]
        );
    }

    /**
     * Y-m-d for APIs, or null when the source date is missing/unreliable.
     */
    public static function publicDateString(?string $raw, mixed $expireDate): ?string
    {
        $resolved = self::resolve($raw, ['expire_date' => $expireDate]);

        return $resolved['is_delisted'] ? $resolved['status_date'] : null;
    }

    public static function detailLine(array $resolved): string
    {
        $label = (string) $resolved['display_label'];
        $date = $resolved['status_date'] ?? null;
        if (! $resolved['is_delisted'] || ! is_string($date) || $date === '') {
            return $label;
        }

        try {
            $formatted = Carbon::parse($date, self::timezoneId())->startOfDay()->format('F j, Y');
        } catch (Throwable) {
            return $label;
        }

        return $label . ' on ' . $formatted;
    }

    /**
     * @return array<string, mixed>
     */
    public static function frontendConfig(): array
    {
        return [
            'map' => self::RAW_MAP,
            'delisted' => [
                self::EXPIRED,
                self::SUSPENDED,
                self::CANCELLED,
                self::TERMINATED,
                self::WITHDRAWN,
                self::UNAVAILABLE,
            ],
            'delisted_query' => self::delistedQueryValues(),
            'sold' => self::CLOSED_SALE_VALUES,
            'leased' => self::CLOSED_LEASE_VALUES,
            'active' => self::ACTIVE,
            'unavailable' => self::UNAVAILABLE,
        ];
    }

    public static function timezoneId(): string
    {
        $configured = trim((string) config('serik.appointment.timezone', ''));
        if ($configured !== '' && in_array($configured, timezone_identifiers_list(), true)) {
            return $configured;
        }

        return 'America/Toronto';
    }

    private static function publicDate(string $normalized, mixed $expireDate): ?CarbonInterface
    {
        // Only ExpirationDate (stored as expire_date) is persisted from AMP for
        // de-listed listings. Use it solely for Expired — never CloseDate /
        // updated_at / created_at, and never a future placeholder date.
        if ($normalized !== self::EXPIRED) {
            return null;
        }

        if ($expireDate === null || $expireDate === '') {
            return null;
        }

        try {
            $date = $expireDate instanceof CarbonInterface
                ? $expireDate->copy()->timezone(self::timezoneId())->startOfDay()
                : Carbon::parse((string) $expireDate, self::timezoneId())->startOfDay();
        } catch (Throwable) {
            return null;
        }

        $year = (int) $date->year;
        $now = Carbon::now(self::timezoneId())->startOfDay();
        if ($year < 1990 || $year > ((int) $now->year + 1)) {
            return null;
        }
        if ($date->greaterThan($now)) {
            return null;
        }

        return $date;
    }

    private static function noteUnknown(string $raw): void
    {
        $key = 'mls_status_unknown:' . sha1(self::key($raw));
        if (! Cache::add($key, 1, 86400)) {
            return;
        }

        Log::info('mls_status_unknown', [
            'raw_status' => mb_substr($raw, 0, 80),
        ]);
    }
}
