<?php

namespace App\Support;

use Botble\Theme\Supports\SiteMapManager;
use DateTimeInterface;

class SerikSiteMapManager extends SiteMapManager
{
    public function add(string $url, mixed $date = null, string $priority = '1.0', string $sequence = 'daily'): self
    {
        if (! SerikSitemap::shouldInclude($url)) {
            return $this;
        }

        return parent::add($url, $this->toSitemapDate($date), $priority, $sequence);
    }

    public function addSitemap(string $url, mixed $date = null): self
    {
        if (! SerikSitemap::shouldIncludeIndex($url)) {
            return $this;
        }

        return parent::addSitemap($url, $this->toSitemapDate($date));
    }

    protected function toSitemapDate(mixed $date): ?string
    {
        if ($date instanceof DateTimeInterface) {
            return $date->format('Y-m-d H:i:s');
        }

        if (is_string($date) && $date !== '') {
            return $date;
        }

        return null;
    }
}
