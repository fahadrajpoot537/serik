<?php

namespace Tests\Unit;

use App\Support\SerikSitemap;
use Tests\TestCase;

class SerikSitemapPolicyTest extends TestCase
{
    public function test_index_excludes_blog_tags_and_property_archives(): void
    {
        $this->assertFalse(SerikSitemap::shouldIncludeIndex('https://serik.ca/blog-tags.xml'));
        $this->assertFalse(SerikSitemap::shouldIncludeIndex('https://serik.ca/properties-city.xml'));
        $this->assertFalse(SerikSitemap::shouldIncludeIndex('https://serik.ca/properties-2026-08.xml'));
        $this->assertFalse(SerikSitemap::shouldIncludeIndex('https://serik.ca/projects-city.xml'));
        $this->assertTrue(SerikSitemap::shouldIncludeIndex('https://serik.ca/pages.xml'));
        $this->assertTrue(SerikSitemap::shouldIncludeIndex('https://serik.ca/featured-properties.xml'));
    }

    public function test_urls_exclude_individual_listings_and_fthb(): void
    {
        $this->assertFalse(SerikSitemap::shouldInclude('https://serik.ca/fthb'));
        $this->assertFalse(SerikSitemap::shouldInclude('https://serik.ca/properties/some-listing-w123'));
        $this->assertFalse(SerikSitemap::shouldInclude('https://serik.ca/properties/city/toronto'));
        $this->assertFalse(SerikSitemap::shouldInclude('https://serik.ca/projects/city/toronto'));
        $this->assertFalse(SerikSitemap::shouldInclude('https://serik.ca/on/houses-for-sale-in-toronto/map'));
        $this->assertFalse(SerikSitemap::shouldInclude('https://serik.ca/tag/tips'));
        $this->assertTrue(SerikSitemap::shouldInclude('https://serik.ca/first-time-house-buyer'));
        $this->assertTrue(SerikSitemap::shouldInclude('https://serik.ca/ontario/toronto-houses-for-sale'));
        $this->assertTrue(SerikSitemap::shouldInclude('https://serik.ca/properties'));
    }

    public function test_featured_listing_urls_include_toronto_houses_for_sale(): void
    {
        $urls = SerikSitemap::featuredListingUrls();
        $paths = array_map(static fn (string $url) => parse_url($url, PHP_URL_PATH), $urls);

        $this->assertContains('/ontario/toronto-houses-for-sale', $paths);
        $this->assertContains('/ontario/brampton-condos-for-sale', $paths);
        $this->assertContains('/ontario/ottawa-houses-for-lease', $paths);
        $this->assertNotEmpty($urls);
    }
}
