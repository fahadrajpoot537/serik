<?php

namespace App\Support;

use Botble\Theme\Supports\SiteMapManager;

class SerikSiteMapManager extends SiteMapManager
{
    public function add(string $url, ?string $date = null, string $priority = '1.0', string $sequence = 'daily'): self
    {
        if (! SerikSitemap::shouldInclude($url)) {
            return $this;
        }

        return parent::add($url, $date, $priority, $sequence);
    }
}
