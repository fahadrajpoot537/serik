<?php

namespace Botble\RealEstate\Supports;

use Botble\RealEstate\Models\Account;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;

class AccountRegistrationExpiry
{
    public const EXPIRY_DAYS = 90;

    public static function anchorDate(Account $account): Carbon
    {
        return ($account->password_expire ?? $account->created_at)->copy();
    }

    public static function expiresAt(Account $account): Carbon
    {
        return self::anchorDate($account)->addDays(self::EXPIRY_DAYS);
    }

    public static function isExpired(Account $account): bool
    {
        return now()->greaterThanOrEqualTo(self::expiresAt($account));
    }

    public static function deleteIfExpired(Account $account): bool
    {
        if (! self::isExpired($account)) {
            return false;
        }

        $account->delete();

        return true;
    }

    public static function expiredQuery(): Builder
    {
        $cutoff = now()->subDays(self::EXPIRY_DAYS);

        return Account::query()->where(function (Builder $query) use ($cutoff): void {
            $query
                ->where(function (Builder $query) use ($cutoff): void {
                    $query
                        ->whereNotNull('password_expire')
                        ->where('password_expire', '<=', $cutoff);
                })
                ->orWhere(function (Builder $query) use ($cutoff): void {
                    $query
                        ->whereNull('password_expire')
                        ->where('created_at', '<=', $cutoff);
                });
        });
    }

    public static function purgeExpiredAccounts(): int
    {
        $count = 0;

        self::expiredQuery()->chunkById(100, function ($accounts) use (&$count): void {
            foreach ($accounts as $account) {
                if (self::isExpired($account)) {
                    $account->delete();
                    $count++;
                }
            }
        });

        return $count;
    }

    public static function expiredMessage(): string
    {
        return 'Your registration has expired after 90 days. Please register again to continue using the site.';
    }
}
