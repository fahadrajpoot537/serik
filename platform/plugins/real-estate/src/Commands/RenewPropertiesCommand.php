<?php

namespace Botble\RealEstate\Commands;

use Botble\RealEstate\Facades\RealEstateHelper;
use Botble\RealEstate\Models\Account;
use Botble\RealEstate\Models\Property;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Symfony\Component\Console\Attribute\AsCommand;

#[AsCommand('cms:properties:renew', 'Renew properties')]
class RenewPropertiesCommand extends Command
{
    public function handle(): int
    {
        Log::info('[Property Renewal] Starting property renewal process at ' . Carbon::now()->toDateTimeString());

        if (! RealEstateHelper::isEnabledAutoRenew()) {
            $this->components->info('Auto-renew feature is not enabled in settings.');
            Log::info('[Property Renewal] Auto-renew feature is disabled. Exiting.');

            return self::SUCCESS;
        }

        $daysBefore = RealEstateHelper::getRenewBeforeExpiredDays();
        Log::info('[Property Renewal] Renew before expired days setting: ' . $daysBefore);

        $this->components->info('Checking for properties to renew...');

        if ($daysBefore > 0) {
            $this->components->info(sprintf('Looking for properties expiring within %d days', $daysBefore));
        } else {
            $this->components->info('Looking for expired properties');
        }

        $properties = Property::query()
            ->expiringSoon($daysBefore)
            ->where(RealEstateHelper::getPropertyDisplayQueryConditions())
            ->where('auto_renew', 1);

        if (RealEstateHelper::isEnabledCreditsSystem()) {
            $properties = $properties
                ->where('author_type', Account::class)
                ->join('re_accounts', 're_accounts.id', '=', 're_properties.author_id')
                ->where('credits', '>', 0);
        }

        $properties = $properties
            ->with(['author'])
            ->select('re_properties.*')
            ->get();

        if ($properties->isEmpty()) {
            $this->components->info('No properties found that need renewal.');
            Log::info('[Property Renewal] No properties found that need renewal.');

            return self::SUCCESS;
        }

        $this->components->info(sprintf('Found %d properties to renew', $properties->count()));
        $this->newLine();

        Log::info('[Property Renewal] Found ' . $properties->count() . ' properties to process');

        $renewedCount = 0;
        $skippedCount = 0;
        $renewedProperties = [];
        $skippedProperties = [];

        foreach ($properties as $property) {
            $oldExpireDate = $property->expire_date;

            if (RealEstateHelper::isEnabledCreditsSystem() && $property->author->credits <= 0) {
                $this->components->warn(sprintf(
                    '⚠ Skipped: Property #%d "%s" - Author has insufficient credits',
                    $property->id,
                    $property->name
                ));

                Log::warning('[Property Renewal] Skipped property #' . $property->id . ' "' . $property->name . '" - Author "' . ($property->author->name ?? 'N/A') . '" has insufficient credits');

                $skippedProperties[] = [
                    'id' => $property->id,
                    'name' => $property->name,
                    'author' => $property->author->name ?? 'N/A',
                    'reason' => 'Insufficient credits',
                ];

                $skippedCount++;

                continue;
            }

            $baseDate = $oldExpireDate && $oldExpireDate->isFuture()
                ? $oldExpireDate
                : Carbon::today();

            $newExpireDate = $baseDate->copy()->addDays(RealEstateHelper::propertyExpiredDays());
            $property->expire_date = $newExpireDate;
            $property->save();

            if (RealEstateHelper::isEnabledCreditsSystem()) {
                $property->author->credits--;
                $property->author->save();
            }

            $renewedProperties[] = [
                'id' => $property->id,
                'name' => $property->name,
                'author' => $property->author->name ?? 'N/A',
                'old_expire_date' => $oldExpireDate ? $oldExpireDate->toDateTimeString() : 'N/A',
                'new_expire_date' => $newExpireDate->toDateTimeString(),
                'credits_remaining' => RealEstateHelper::isEnabledCreditsSystem() ? $property->author->credits : 'N/A',
            ];

            Log::info('[Property Renewal] Successfully renewed property #' . $property->id . ' "' . $property->name . '"', [
                'property_id' => $property->id,
                'property_name' => $property->name,
                'author' => $property->author->name ?? 'N/A',
                'old_expire_date' => $oldExpireDate ? $oldExpireDate->toDateTimeString() : null,
                'new_expire_date' => $newExpireDate->toDateTimeString(),
                'extended_days' => RealEstateHelper::propertyExpiredDays(),
                'credits_remaining' => RealEstateHelper::isEnabledCreditsSystem() ? $property->author->credits : null,
            ]);

            $this->components->info(sprintf(
                '✓ Renewed: Property #%d "%s"',
                $property->id,
                $property->name
            ));

            $daysAdded = RealEstateHelper::propertyExpiredDays();
            $actualDaysExtended = $oldExpireDate ? $oldExpireDate->diffInDays($newExpireDate, false) : $daysAdded;

            $bulletPoints = [
                sprintf('Author: %s', $property->author->name ?? 'N/A'),
                sprintf('Old expire date: %s', $oldExpireDate ? $oldExpireDate->format('Y-m-d H:i:s') : 'N/A'),
                sprintf('New expire date: %s', $newExpireDate->format('Y-m-d H:i:s')),
                sprintf('Extended by: %d day(s)', $actualDaysExtended),
            ];

            if (RealEstateHelper::isEnabledCreditsSystem()) {
                $bulletPoints[] = sprintf('Credits remaining: %d', $property->author->credits);
            }

            $this->components->bulletList($bulletPoints);

            $renewedCount++;
        }

        $this->newLine();

        if ($renewedCount > 0) {
            $this->components->success(sprintf(
                'Successfully renewed %d %s!',
                $renewedCount,
                $renewedCount === 1 ? 'property' : 'properties'
            ));
        }

        if ($skippedCount > 0) {
            $this->components->warn(sprintf(
                'Skipped %d %s due to insufficient credits.',
                $skippedCount,
                $skippedCount === 1 ? 'property' : 'properties'
            ));
        }

        Log::info('[Property Renewal] Renewal process completed', [
            'total_processed' => $properties->count(),
            'renewed_count' => $renewedCount,
            'skipped_count' => $skippedCount,
            'renewed_properties' => $renewedProperties,
            'skipped_properties' => $skippedProperties,
            'completed_at' => Carbon::now()->toDateTimeString(),
        ]);

        return self::SUCCESS;
    }
}
