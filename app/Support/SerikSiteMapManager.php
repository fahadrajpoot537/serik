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

    public function addSitemap(string $url, ?string $date = null): self
    {
        $path = trim((string) parse_url($url, PHP_URL_PATH), '/');
        $key = basename($path);

        // Exclude monthly individual-property sitemap chunks.
        if (preg_match('/^properties-\d{4}-\d{2}$/', $key)) {
            return $this;
        }

        return parent::addSitemap($url, $date);
    }
}
