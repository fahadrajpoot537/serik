<?php

namespace Botble\RealEstate\Listeners;

use Botble\Base\Enums\BaseStatusEnum;
use Botble\Location\Models\City;
use Botble\RealEstate\Facades\RealEstateHelper;
use Botble\RealEstate\Models\Account;
use Botble\RealEstate\Models\Category;
use Botble\RealEstate\Models\Project;
use Botble\RealEstate\Models\Property;
use Botble\Theme\Events\RenderingSiteMapEvent;
use Botble\Theme\Facades\SiteMapManager;
use Carbon\Carbon;
use Illuminate\Support\Arr;
use Theme\homzen\Supports\TrebPropertyHelper;

class AddSitemapListener
{
    private const PROPERTY_SITEMAP_MONTHS = 24;

    private const PROPERTY_SITEMAP_MAX_URLS = 4000;

    /**
     * Property subtypes excluded from properties-*.xml sitemaps.
     *
     * @return array<int, string>
     */
    private function excludedPropertySubTypes(): array
    {
        return array_values(array_unique(array_merge(
            TrebPropertyHelper::excludedCommercialSubTypes(),
            [
                'Investment',
                'MobileTrailer',
                'Modular Home',
                'Lower Level',
            ]
        )));
    }

    public function handle(RenderingSiteMapEvent $event): void
    {
        if ($key = $event->key) {
            switch ($key) {
                case 'agents':

                    if (RealEstateHelper::isDisabledPublicProfile()) {
                        break;
                    }

                    $agentLastUpdated = Account::query()
                        ->latest('updated_at')
                        ->value('updated_at');

                    SiteMapManager::add(route('public.agents'), $agentLastUpdated, '0.4', 'monthly');

                    Account::query()
                        ->select(['id', 'first_name', 'last_name', 'username', 'updated_at', 'created_at'])
                        ->with(['slugable'])
                        ->orderBy('id')
                        ->chunkById(200, function ($accounts): void {
                            foreach ($accounts as $item) {
                                if (! $item->slugable) {
                                    continue;
                                }

                                SiteMapManager::add($item->url, $item->updated_at, '0.8');
                            }
                        });

                    break;

                case 'property-categories':

                    $items = Category::query()
                        ->with('slugable')
                        ->where('status', BaseStatusEnum::PUBLISHED)
                        ->latest('created_at')
                        ->get();

                    foreach ($items as $item) {
                        if (! $item->slugable) {
                            continue;
                        }

                        SiteMapManager::add($item->url, $item->updated_at, '0.8');
                    }

                    break;

                case 'properties-city':

                    $items = City::query()
                        ->where('status', BaseStatusEnum::PUBLISHED)
                        ->latest('updated_at')
                        ->get();

                    foreach ($items as $item) {
                        if (! $item->slug) {
                            continue;
                        }

                        SiteMapManager::add(route('public.properties-by-city', $item->slug), $item->updated_at, '0.8');
                    }

                    break;

                case 'projects-city':
                    if (! RealEstateHelper::isEnabledProjects()) {
                        break;
                    }

                    $items = City::query()
                        ->where('status', BaseStatusEnum::PUBLISHED)
                        ->latest('updated_at')
                        ->get();

                    foreach ($items as $item) {
                        if (! $item->slug) {
                            continue;
                        }

                        SiteMapManager::add(route('public.projects-by-city', $item->slug), $item->updated_at, '0.8');
                    }

                    break;
            }

            if (preg_match('/^properties-((?:19|20|21|22)\d{2})-(0?[1-9]|1[012])$/', $key, $matches)) {
                if (($year = Arr::get($matches, 1)) && ($month = Arr::get($matches, 2))) {
                    $this->addPropertyMonthUrls((int) $year, (int) $month);
                }
            }

            if (RealEstateHelper::isEnabledProjects()) {
                if (preg_match('/^projects-((?:19|20|21|22)\d{2})-(0?[1-9]|1[012])$/', $key, $matches)) {
                    if (($year = Arr::get($matches, 1)) && ($month = Arr::get($matches, 2))) {
                        $start = Carbon::create((int) $year, (int) $month, 1)->startOfMonth();
                        $end = $start->copy()->endOfMonth();

                        Project::query()
                            ->active()
                            ->whereBetween('updated_at', [$start, $end])
                            ->select(['id', 'name', 'updated_at'])
                            ->with(['slugable'])
                            ->orderBy('id')
                            ->chunkById(250, function ($projects): void {
                                foreach ($projects as $project) {
                                    if (! $project->slugable) {
                                        continue;
                                    }

                                    SiteMapManager::add($project->url, $project->updated_at, '0.8');
                                }
                            });
                    }
                }
            }

            return;
        }

        if (! RealEstateHelper::isDisabledPublicProfile()) {
            $agentLastUpdated = Account::query()
                ->latest('updated_at')
                ->value('updated_at');

            SiteMapManager::addSitemap(SiteMapManager::route('agents'), $agentLastUpdated);
        }

        $cityLastUpdated = City::query()
            ->where('status', BaseStatusEnum::PUBLISHED)
            ->latest('updated_at')
            ->value('updated_at');

        SiteMapManager::addSitemap(SiteMapManager::route('properties-city'), $cityLastUpdated);

        if (RealEstateHelper::isEnabledProjects()) {
            SiteMapManager::addSitemap(SiteMapManager::route('projects-city'), $cityLastUpdated);
        }

        $this->addPropertyMonthIndexes();
    }

    private function addPropertyMonthIndexes(): void
    {
        $cursor = now()->startOfMonth();
        $oldest = now()->startOfMonth()->subMonths(self::PROPERTY_SITEMAP_MONTHS - 1);

        while ($cursor->gte($oldest)) {
            $key = sprintf('properties-%s', $cursor->format('Y-m'));
            SiteMapManager::addSitemap(
                SiteMapManager::route($key),
                $cursor->copy()->endOfMonth()->toDateTimeString()
            );
            $cursor->subMonth();
        }
    }

    private function addPropertyMonthUrls(int $year, int $month): void
    {
        $start = Carbon::create($year, $month, 1)->startOfMonth();
        $end = $start->copy()->endOfMonth();
        $added = 0;
        $max = self::PROPERTY_SITEMAP_MAX_URLS;

        Property::query()
            ->active()
            ->whereBetween('updated_at', [$start, $end])
            ->where(function ($query): void {
                $query->whereNull('PropertySubType')
                    ->orWhereNotIn('PropertySubType', $this->excludedPropertySubTypes());
            })
            ->select(['id', 'name', 'updated_at'])
            ->with(['slugable'])
            ->orderBy('id')
            ->chunkById(250, function ($properties) use (&$added, $max): bool {
                foreach ($properties as $property) {
                    if (! $property->slugable) {
                        continue;
                    }

                    SiteMapManager::add($property->url, $property->updated_at, '0.8');
                    $added++;

                    if ($added >= $max) {
                        return false;
                    }
                }

                return true;
            });
    }
}
