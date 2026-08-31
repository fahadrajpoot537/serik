<?php

namespace App\Support;

use App\Models\AccountWishlist as AccountWishlistModel;
use Botble\RealEstate\Enums\ModerationStatusEnum;
use Botble\RealEstate\Models\Property;
use Illuminate\Support\Facades\Schema;
use Throwable;

final class AccountWishlist
{
    public const TYPE_PROPERTY = 'property';

    public static function tableReady(): bool
    {
        try {
            return Schema::hasTable('re_account_wishlists');
        } catch (Throwable) {
            return false;
        }
    }

    public static function countFor(?int $accountId): int
    {
        if (! $accountId || ! self::tableReady()) {
            return 0;
        }

        return (int) AccountWishlistModel::query()
            ->where('account_id', $accountId)
            ->where('item_type', self::TYPE_PROPERTY)
            ->count();
    }

    /**
     * @return list<int>
     */
    public static function propertyIdsFor(?int $accountId): array
    {
        if (! $accountId || ! self::tableReady()) {
            return [];
        }

        return AccountWishlistModel::query()
            ->where('account_id', $accountId)
            ->where('item_type', self::TYPE_PROPERTY)
            ->orderByDesc('id')
            ->pluck('item_id')
            ->map(static fn ($id) => (int) $id)
            ->all();
    }

    public static function has(?int $accountId, int $propertyId): bool
    {
        if (! $accountId || $propertyId < 1 || ! self::tableReady()) {
            return false;
        }

        return AccountWishlistModel::query()
            ->where('account_id', $accountId)
            ->where('item_type', self::TYPE_PROPERTY)
            ->where('item_id', $propertyId)
            ->exists();
    }

    /**
     * @return array{saved: bool, count: int, created: bool}
     */
    public static function add(int $accountId, int $propertyId): array
    {
        if (! self::tableReady()) {
            return ['saved' => false, 'count' => 0, 'created' => false];
        }

        $created = false;

        if (! self::has($accountId, $propertyId)) {
            try {
                AccountWishlistModel::query()->create([
                    'account_id' => $accountId,
                    'item_type' => self::TYPE_PROPERTY,
                    'item_id' => $propertyId,
                ]);
                $created = true;
            } catch (Throwable) {
                if (! self::has($accountId, $propertyId)) {
                    return [
                        'saved' => false,
                        'count' => self::countFor($accountId),
                        'created' => false,
                        'failed' => true,
                    ];
                }
            }
        }

        return [
            'saved' => true,
            'count' => self::countFor($accountId),
            'created' => $created,
        ];
    }

    /**
     * @return array{saved: bool, count: int}
     */
    public static function remove(int $accountId, int $propertyId): array
    {
        if (self::tableReady()) {
            AccountWishlistModel::query()
                ->where('account_id', $accountId)
                ->where('item_type', self::TYPE_PROPERTY)
                ->where('item_id', $propertyId)
                ->delete();
        }

        return [
            'saved' => false,
            'count' => self::countFor($accountId),
        ];
    }

    /**
     * @return array{saved: bool, count: int, created: bool}
     */
    public static function toggle(int $accountId, int $propertyId): array
    {
        if (self::has($accountId, $propertyId)) {
            $removed = self::remove($accountId, $propertyId);

            return [
                'saved' => false,
                'count' => $removed['count'],
                'created' => false,
            ];
        }

        return self::add($accountId, $propertyId);
    }

    public static function isEligibleProperty(int $propertyId): bool
    {
        if ($propertyId < 1 || ! class_exists(Property::class)) {
            return false;
        }

        try {
            return Property::query()
                ->whereKey($propertyId)
                ->where('moderation_status', ModerationStatusEnum::APPROVED)
                ->exists();
        } catch (Throwable) {
            return false;
        }
    }

    /**
     * One-time import of legacy cookie IDs for the authenticated account.
     *
     * @param  list<int|string>  $ids
     */
    public static function importPropertyIds(int $accountId, array $ids): void
    {
        if (! self::tableReady()) {
            return;
        }

        foreach ($ids as $id) {
            $propertyId = (int) $id;
            if ($propertyId < 1 || ! self::isEligibleProperty($propertyId)) {
                continue;
            }

            self::add($accountId, $propertyId);
        }
    }
}
