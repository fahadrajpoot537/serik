<?php

namespace Botble\RealEstate\Listeners;

use App\Support\SerikSitemap;
use Botble\Base\Enums\BaseStatusEnum;
use Botble\RealEstate\Facades\RealEstateHelper;
use Botble\RealEstate\Models\Account;
use Botble\RealEstate\Models\Category;
use Botble\Theme\Events\RenderingSiteMapEvent;
use Botble\Theme\Facades\SiteMapManager;

class AddSitemapListener
{
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

                case 'featured-properties':
                    foreach (SerikSitemap::featuredListingUrls() as $url) {
                        SiteMapManager::add($url, now(), '0.8', 'daily');
                    }

                    break;

                case 'properties-city':
                case 'projects-city':
                    // Intentionally empty: individual/city archives are not indexed.
                    break;
            }

            return;
        }

        if (! RealEstateHelper::isDisabledPublicProfile()) {
            $agentLastUpdated = Account::query()
                ->latest('updated_at')
                ->value('updated_at');

            SiteMapManager::addSitemap(SiteMapManager::route('agents'), $agentLastUpdated);
        }

        SiteMapManager::addSitemap(SiteMapManager::route('featured-properties'), now());
    }
}
