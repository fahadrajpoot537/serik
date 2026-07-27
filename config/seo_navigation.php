<?php

return [
    'cache_ttl' => (int) env('SEO_NAV_CACHE_TTL', 3600),

    'major_city_limit' => 24,

    'nearby_city_limit' => 10,

    'neighborhood_limit' => 10,

    'popular_city_limit' => 10,

    'default_home_city_slug' => 'toronto',

    'active_mls_statuses' => [
        'New',
        'Price Change',
        'Extension',
        'Previous Status',
    ],

    /** Homepage "Active Ontario" column — house-for-sale links. */
    'ontario_active_cities' => [
        'toronto',
        'north-york',
        'mississauga',
        'brampton',
        'scarborough',
        'etobicoke',
        'london',
        'vaughan',
        'markham',
        'hamilton',
        'niagara-falls',
    ],

    /** Homepage "Sold" column — sold house links. */
    'ontario_sold_cities' => [
        'toronto',
        'north-york',
        'brampton',
        'mississauga',
        'barrie',
        'markham',
        'hamilton',
        'vaughan',
        'ottawa',
    ],

    /**
     * Former Toronto municipalities are stored in MLS as district codes
     * (e.g. "Toronto C15"), not as "North York". Used when Meili city facet
     * has no exact match.
     */
    'treb_city_districts' => [
        'north-york' => [
            'Toronto C06', 'Toronto C07', 'Toronto C09', 'Toronto C10',
            'Toronto C11', 'Toronto C12', 'Toronto C13', 'Toronto C14', 'Toronto C15',
        ],
        'scarborough' => [
            'Toronto E01', 'Toronto E02', 'Toronto E03', 'Toronto E04', 'Toronto E05',
            'Toronto E06', 'Toronto E07', 'Toronto E08', 'Toronto E09', 'Toronto E10', 'Toronto E11',
        ],
        'etobicoke' => [
            'Toronto W01', 'Toronto W02', 'Toronto W03', 'Toronto W04', 'Toronto W05',
            'Toronto W06', 'Toronto W07', 'Toronto W08', 'Toronto W09', 'Toronto W10',
        ],
        'east-york' => [
            'Toronto C10', 'Toronto C11', 'Toronto C29',
        ],
        'york' => [
            'Toronto W03', 'Toronto W04',
        ],
    ],

    'major_city_slugs' => [
        'toronto',
        'mississauga',
        'brampton',
        'vaughan',
        'markham',
        'hamilton',
        'london',
        'ottawa',
        'kitchener',
        'waterloo',
        'oakville',
        'burlington',
        'richmond-hill',
        'oshawa',
        'barrie',
        'guelph',
        'cambridge',
        'whitby',
        'ajax',
        'pickering',
        'milton',
        'niagara-falls',
        'st-catharines',
        'newmarket',
    ],
];
